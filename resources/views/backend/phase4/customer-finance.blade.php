@extends('backend.master-tailwind')
@section('title', __('Customer balances'))
@section('content')
<div class="space-y-6"><x-backend.card :title="$client->name">
@if($client->isWalking())<p>{{ __('Cash only: no debt or credit.') }}</p>@endif
@if($legacy)<p>{{ __('Historical balances have an unknown currency and require reconciliation.') }} ({{ $legacy }})</p>@endif
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.customers.orders',$client->id) }}">{{ __('Sales') }}</a>
</x-backend.card>
<x-backend.card :title="__('Outstanding sales')">
@foreach($debts as $o)<div class="border-b py-3"><p>#{{ $o->id }} · {{ $o->due }} {{ $o->currency_code ?? __('Unknown') }} · {{ __('Due date') }}: {{ $o->due_date ?? '—' }}</p>
@can('sale_collect')<a href="{{ route('backend.admin.due.collection',['id'=>$o->id,'operation_point_of_sale_id'=>$o->point_of_sale_id]) }}">{{ __('Collect Due') }}</a>@endcan
@can('customer_credit_manage')<form method="POST" action="{{ route('backend.admin.sales.due-date',$o->id) }}" class="flex gap-3 mt-2">@csrf @include('backend.phase4.context')<input class="qpos-control" type="date" name="due_date" value="{{ $o->due_date }}" required><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Save due date') }}</button></form>@endcan
@can('customer_credit_manage')<form method="POST" action="{{ route('backend.admin.sales.reminder',$o->id) }}" class="grid gap-3 mt-3">@csrf @include('backend.phase4.context')<x-backend.select name="method" :id="'reminder-method-'.$o->id" :label="__('Reminder method')" :options="['phone'=>__('Phone call'),'visit'=>__('Visit'),'note'=>__('Internal note')]" /><x-backend.input name="note" :id="'reminder-note-'.$o->id" :label="__('Note')" maxlength="255" required /><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Record reminder') }}</button></form>@endcan
</div>@endforeach {{ $debts->links() }}
</x-backend.card>
<x-backend.card :title="__('Store credit')">@foreach($credits as $c)<p class="py-2">#{{ $c->id }} · {{ __('Issued') }}: {{ $c->amount }} · {{ __('Available') }}: {{ $c->remaining_amount }} XAF · {{ __('Expiry') }}: {{ $c->expires_on ?? __('None') }}</p>@endforeach {{ $credits->links() }}</x-backend.card>
<x-backend.card :title="__('Credit usage history')">@foreach($uses as $u)<p class="py-2">{{ __('Store credit') }} #{{ $u->credit_note_id }} · {{ __('Sale') }} #{{ $u->order_id }} · {{ $u->amount }} XAF</p>@endforeach {{ $uses->links() }}</x-backend.card></div>
<x-backend.card :title="__('Debt follow-up history')">@foreach($events as $event)@php($payload=json_decode($event->payload,true))<p class="py-2">#{{ $event->order_id }} · {{ Illuminate\Support\Carbon::parse($event->occurred_at,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }} · {{ __($event->kind) }} · {{ $payload['note'] ?? (($payload['before']??'—').' → '.($payload['after']??'—')) }}</p>@endforeach {{ $events->links() }}</x-backend.card>
@endsection
