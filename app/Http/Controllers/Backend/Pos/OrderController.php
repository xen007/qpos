<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Services\SaleService;
use App\Support\StockContext;
use App\Support\StockDocumentAccess;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        return $this->listing($r, null);
    }

    public function customerOrders(Request $r, Customer $customer)
    {
        return $this->listing($r, $customer);
    }

    private function listing(Request $r, ?Customer $customer)
    {
        if (! $r->ajax()) {
            return view('backend.orders.index', ['customer' => $customer]);
        }
        $orders = StockDocumentAccess::query(Order::query(), $r)->select('orders.*')->with('customer')->withSum('products as item_quantity_sum', 'quantity')->withCount('products');
        if ($customer) {
            $orders->where('customer_id', $customer->id);
        }

        return DataTables::of($orders)->addIndexColumn()->addColumn('saleId', fn ($o) => '#'.$o->id)
            ->addColumn('is_paid', fn ($o) => (bool) $o->status)
            ->addColumn('customer', fn ($o) => $o->customer?->name ?? '-')
            ->addColumn('item', fn ($o) => $o->item_quantity_sum ?? '0')
            ->addColumn('currency_code', fn ($o) => $o->currency_code ?? __('Unknown historical currency'))
            ->addColumn('action', fn ($o) => '')->toJson();
    }

    public function store(Request $r)
    {
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'cart_id' => 'required|string|max:64', 'quote_hash' => 'required|string|size:64', 'customer_id' => 'required|integer|exists:customers,id', 'order_discount' => 'nullable|string', 'credit_amount' => 'nullable|string', 'due_date' => 'nullable|date_format:Y-m-d', 'payments' => 'present|array|max:10', 'payments.*.method' => 'required|in:cash,card', 'payments.*.amount' => 'required|string', 'payments.*.external_reference' => 'nullable|string|max:128', 'confirm_expired_sale' => 'nullable|boolean', 'expired_sale_reason' => 'nullable|string|max:255']);
        $order = app(SaleService::class)->checkout($r->user()->id, StockContext::shop($r)->id, $data);

        return response()->json(['message' => __('Order completed successfully'), 'order' => $order]);
    }

    private function document(int $id): Order
    {
        return StockDocumentAccess::query(Order::query(), request())->with(['customer', 'products.product', 'paymentAllocations.payment'])->findOrFail($id);
    }

    public function invoice($id)
    {
        $order = $this->document($id);

        return $order->currency_code ? view('backend.phase4.sale-document', ['order' => $order, 'ticket' => false]) : view('backend.orders.print-invoice', compact('order'));
    }

    public function posInvoice($id)
    {
        $order = $this->document($id);

        return $order->currency_code ? view('backend.phase4.sale-document', ['order' => $order, 'ticket' => true]) : view('backend.orders.pos-invoice', ['order' => $order, 'maxWidth' => '80mm']);
    }

    public function collection(Request $r, $id)
    {
        $order = $this->document($id);
        if (! $r->isMethod('post')) {
            return view('backend.phase4.collection', compact('order'));
        }
        $data = $r->validate(['operation_key' => 'required|string|max:64', 'amount' => 'required|string', 'method' => 'required|in:cash,card', 'external_reference' => 'nullable|string|max:128']);
        $p = app(SaleService::class)->collect($r->user()->id, StockContext::shop($r)->id, (int) $id, $data);

        return to_route('backend.admin.payments.receipt', $p->id);
    }

    public function collectionInvoice($id)
    {
        $transaction = OrderTransaction::findOrFail($id);
        $order = $this->document($transaction->order_id);
        $collection_amount = $transaction->amount;

        return view('backend.orders.collection.invoice', compact('order', 'collection_amount', 'transaction'));
    }

    public function transactions($id)
    {
        $order = $this->document($id);

        return $order->currency_code ? view('backend.phase4.payments', compact('order')) : view('backend.orders.collection.index', compact('order'));
    }
}
