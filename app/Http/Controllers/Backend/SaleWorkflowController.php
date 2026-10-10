<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PointOfSale;
use App\Models\User;
use App\Services\CashService;
use App\Services\CustomerDebtService;
use App\Services\SaleCorrectionService;
use App\Support\SaleOperation as Op;
use App\Support\StockContext;
use App\Support\StockDocumentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleWorkflowController extends Controller
{
    public function cash(Request $r)
    {
        $shop = StockContext::shop($r);
        $sessions = CashSession::where('point_of_sale_id', $shop->id)->where('user_id', $r->user()->id)->latest()->paginate(20);
        $active = CashSession::where('point_of_sale_id', $shop->id)->where('active_user_id', $r->user()->id)->first();
        $movements = $active ? CashMovement::where('cash_session_id', $active->id)->latest()->paginate(30) : collect();
        $handovers = CashSession::where('point_of_sale_id', $shop->id)->where('handover_to_user_id', $r->user()->id)->where('state', 'closed')->whereNotIn('id', CashSession::whereNotNull('handover_from_id')->select('handover_from_id'))->get();
        $cashiers = User::where('is_suspended', false)->get()->filter(fn ($u) => $u->can('cash_session_manage') && PointOfSale::accessibleBy($u)->whereKey($shop->id)->exists());

        return view('backend.phase4.cash', compact('shop', 'sessions', 'active', 'movements', 'handovers', 'cashiers'));
    }

    public function cashState(Request $r)
    {
        $shop = StockContext::shop($r)->id;
        $s = CashSession::where('active_user_id', $r->user()->id)->where('point_of_sale_id', $shop)->first();

        return response()->json(['user_id' => $r->user()->id, 'session' => $s, 'expected' => $s ? (string) app(CashService::class)->expected($s) : null, 'payment_methods'=>app(\App\Services\PaymentMethodService::class)->choices($shop), 'workflow'=>app(\App\Services\ShopWorkflowSettings::class)->get($shop), 'can_prepare'=>$r->user()->can('pending_sale_prepare'), 'can_collect'=>$r->user()->can('pending_sale_collect')]);
    }

    public function open(Request $r)
    {
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'opening_amount' => 'required|string', 'handover_from_id' => 'nullable|integer']);
        app(CashService::class)->open($r->user()->id, StockContext::shop($r)->id, $data);

        return back()->with('success', __('Cash session opened'));
    }

    public function close(Request $r, int $session)
    {
        $data = $r->validate(['counted_amount' => 'required|string', 'handover_to_user_id' => 'nullable|integer', 'reason' => 'nullable|string|max:500']);
        app(CashService::class)->close($r->user()->id, StockContext::shop($r)->id, $session, $data);

        return back()->with('success', __('Cash session closed'));
    }

    public function movement(Request $r)
    {
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'direction' => 'required|in:in,out', 'amount' => 'required|string', 'reason' => 'required|string|max:500']);
        DB::transaction(function () use ($r, $data) {
            $cash = app(CashService::class);
            $s = $cash->active($r->user()->id, StockContext::shop($r)->id);
            $cash->movement($s, $r->user()->id, $data['direction'], $data['amount'], 'manual', Op::key($data), $data['reason']);
        }, 3);

        return back()->with('success', __('Cash movement recorded'));
    }

    public function finance(Request $r, int $customer)
    {
        $client = Customer::findOrFail($customer);
        $shop = StockContext::shop($r)->id;
        $debts = Order::where('customer_id', $customer)->where('point_of_sale_id', $shop)->where('due', '>', 0)->latest()->paginate(30);
        $credits = DB::table('credit_notes')->where('customer_id', $customer)->where('point_of_sale_id', $shop)->latest()->paginate(30);
        $uses = DB::table('credit_note_uses')->join('credit_notes', 'credit_notes.id', '=', 'credit_note_uses.credit_note_id')->where('credit_notes.customer_id', $customer)->where('credit_notes.point_of_sale_id', $shop)->select('credit_note_uses.*')->latest('credit_note_uses.id')->paginate(30, ['*'], 'uses');
        $legacy = Order::where('customer_id', $customer)->whereNull('currency_code')->where('due', '>', 0)->count();

        $events = DB::table('customer_debt_events')->join('orders', 'orders.id', '=', 'customer_debt_events.order_id')->where('orders.customer_id', $customer)->where('customer_debt_events.point_of_sale_id', $shop)->select('customer_debt_events.*')->latest('customer_debt_events.id')->paginate(30, ['*'], 'events');

        return view('backend.phase4.customer-finance', compact('client', 'debts', 'credits', 'uses', 'legacy', 'events'));
    }

    public function dueDate(Request $r, int $order)
    {
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'due_date' => 'required|date_format:Y-m-d']);
        app(CustomerDebtService::class)->record($r->user()->id, StockContext::shop($r)->id, $order, 'due_date_change', $data);

        return back()->with('success', __('Due date updated'));
    }

    public function reminder(Request $r, int $order)
    {
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'method' => 'required|in:phone,visit,note', 'note' => 'required|string|max:255']);
        app(CustomerDebtService::class)->record($r->user()->id, StockContext::shop($r)->id, $order, 'reminder', $data);

        return back()->with('success', __('Reminder recorded'));
    }

    public function receipt(Request $r, int $payment)
    {
        $p = Payment::with('allocations')->whereKey($payment)->whereNotNull('customer_id')->firstOrFail();
        abort_unless($p->allocations->count() === 1, 404);
        $order = StockDocumentAccess::query(Order::query(), $r)->findOrFail($p->allocations->first()->order_id);
        abort_unless($p->receipt_snapshot && (int) $p->receipt_snapshot['order_id'] === $order->id, 404);

        return view('backend.phase4.receipt', ['payment' => $p, 'snapshot' => $p->receipt_snapshot]);
    }

    public function returns(Request $r, int $order)
    {
        $o = StockDocumentAccess::query(Order::query(), $r)->with(['products.product', 'customer'])->findOrFail($order);
        $corrections = DB::table('sale_corrections')->where('order_id', $order)->latest()->get();

        return view('backend.phase4.returns', ['order' => $o, 'corrections' => $corrections]);
    }

    public function correct(Request $r, int $order)
    {
        $r->validate(['exchange_sale.cash_received'=>'sometimes|required|string']);
        if ($r->input('kind') === 'exchange') {
            abort_unless($r->user()->can('sale_create'), 403);
        }
        $data = $r->validate(['return_quote_hash' => 'nullable|string|size:64', 'operation_key' => 'required|string|max:64', 'kind' => 'required|in:refund,exchange,credit_note,cancel', 'reason' => 'required|string|max:255', 'method' => 'nullable|string|max:24', 'external_reference' => 'nullable|string|max:128', 'saleable' => 'nullable|boolean', 'items' => 'nullable|array|max:200', 'items.*.order_product_id' => 'required|integer', 'items.*.quantity' => 'required|string', 'items.*.saleable' => 'nullable|boolean', 'exchange_sale' => 'nullable|array']);
        if (! empty($data['exchange_sale'])) {
            $data['exchange_sale'] = $r->validate(['exchange_sale.operation_key' => 'required|string|max:64', 'exchange_sale.cart_id' => 'required|string|max:64', 'exchange_sale.customer_id' => 'required|integer', 'exchange_sale.quote_hash' => 'required|string|size:64', 'exchange_sale.order_discount' => 'nullable|string', 'exchange_sale.credit_amount' => 'nullable|string', 'exchange_sale.payments' => 'present|array|max:10', 'exchange_sale.payments.*.method' => 'required|string|max:24', 'exchange_sale.payments.*.amount' => 'required|string', 'exchange_sale.payments.*.external_reference' => 'nullable|string|max:128', 'exchange_sale.due_date' => 'nullable|date_format:Y-m-d', 'exchange_sale.confirm_expired_sale' => 'nullable|boolean', 'exchange_sale.expired_sale_reason' => 'nullable|string|max:255'])['exchange_sale'];
        }
        if(isset($data['exchange_sale'])&&$r->has('exchange_sale.cash_received'))$data['exchange_sale']['cash_received']=$r->input('exchange_sale.cash_received');
        $doc = app(SaleCorrectionService::class)->correct($r->user()->id, StockContext::shop($r)->id, $order, $data);

        return $r->wantsJson() ? response()->json(['correction' => $doc, 'order' => $doc->exchange_order_id ? Order::find($doc->exchange_order_id) : null]) : to_route('backend.admin.corrections.document', $doc->id);
    }

    public function correctionDocument(Request $r, int $correction)
    {
        $doc = DB::table('sale_corrections')->find($correction);
        abort_unless($doc, 404);
        $order = StockDocumentAccess::query(Order::query(), $r)->findOrFail($doc->order_id);
        $items = DB::table('return_items')->join('order_products', 'order_products.id', '=', 'return_items.order_product_id')->where('sale_correction_id', $doc->id)->select('return_items.*', 'order_products.product_label_snapshot')->get();

        return view('backend.phase4.correction-document', compact('doc', 'order', 'items'));
    }

    public function exchangePreview(Request $r, int $order)
    {
        $data = $r->validate(['items' => 'required|array|min:1|max:200', 'items.*.order_product_id' => 'required|integer', 'items.*.quantity' => 'required|string', 'items.*.saleable' => 'nullable|boolean']);

        return response()->json(app(SaleCorrectionService::class)->preview($r->user()->id, StockContext::shop($r)->id, $order, $data['items']));
    }

    public function expenses(Request $r)
    {
        $shop = StockContext::shop($r)->id;
        $categories = DB::table('expense_categories')->orderBy('name')->get();
        $expenses = DB::table('expenses')->where('point_of_sale_id', $shop)->latest()->paginate(30);

        return view('backend.phase4.expenses', compact('categories', 'expenses'));
    }

    public function category(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:255', 'id' => 'nullable|integer', 'is_active' => 'nullable|boolean']);
        if (! empty($data['id'])) {
            DB::table('expense_categories')->where('id', $data['id'])->update(['name' => $data['name'], 'is_active' => (bool) ($data['is_active'] ?? false), 'updated_at' => now('UTC')]);
        } else {
            DB::table('expense_categories')->insert(['name' => $data['name'], 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        }

        return back()->with('success', __('Expense category saved'));
    }

    public function expense(Request $r)
    {
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'expense_category_id' => 'required|integer', 'amount' => 'required|string', 'method' => 'required|string|max:24,transfer', 'description' => 'required|string|max:500', 'external_reference' => 'nullable|string|max:128']);
        $shop = StockContext::shop($r)->id;
        $user = $r->user()->id;
        DB::transaction(function () use ($data, $shop, $user) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            $key = Op::key($data);
            $hash = Op::hash($user, $shop, $data);
            if ($old = DB::table('expenses')->where('operation_key', $key)->first()) {
                Op::replay($old, $hash);

                return;
            }
            $s = app(CashService::class)->active($user, $shop);
            DB::table('expense_categories')->where('id', $data['expense_category_id'])->where('is_active', true)->firstOrFail();
            $v = Op::money($data['amount']);
            if (! $v->isPositive()) {
                Op::fail('amount', 'Enter a positive amount.');
            }
            if ($data['method'] !== 'cash' && empty(trim($data['external_reference'] ?? ''))) {
                Op::fail('external_reference', 'Enter the external payment reference.');
            }
            $move = $data['method'] === 'cash' ? app(CashService::class)->movement($s, $user, 'out', (string) $v, 'expense', 'expense-'.$key, $data['description']) : null;
            DB::table('expenses')->insert(['point_of_sale_id' => $shop, 'user_id' => $user, 'cash_session_id' => $s->id, 'expense_category_id' => $data['expense_category_id'], 'amount' => (string) $v, 'method' => $data['method'], 'description' => $data['description'], 'external_reference' => $data['external_reference'] ?? null, 'cash_movement_id' => $move?->id, 'operation_key' => $key, 'request_hash' => $hash, 'occurred_at' => now('UTC'), 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        }, 3);

        return back()->with('success', __('Expense recorded'));
    }
}
