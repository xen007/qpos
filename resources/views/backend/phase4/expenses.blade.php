@extends('backend.master-tailwind')
@section('title', __('Expenses'))
@section('content')
@php($activeCategories=$categories->where('is_active',true))
<div class="space-y-6 qpos-touch-workspace">
@can('expense_manage')
<x-backend.card :title="__('Record expense')">
@if($activeCategories->isEmpty())
<p role="status" class="mb-4">@if($canManageCategories){{ __('Create or reactivate an expense category below before recording an expense.') }} <a class="qpos-button qpos-button-md qpos-button-secondary" href="#expense-categories">{{ __('Expense categories') }}</a>@else{{ __('Ask the global administrator to create or reactivate an expense category.') }}@endif</p>
@endif
<form method="POST" action="{{ route('backend.admin.expenses.store') }}" class="grid gap-4">@csrf @include('backend.phase4.context')
<x-backend.select name="expense_category_id" :label="__('Category')" :options="$activeCategories->pluck('name','id')->all()" :placeholder="__('Select category')" :disabled="$activeCategories->isEmpty()" required />
<x-backend.input name="amount" :label="__('Amount')" type="number" min="1" step="1" required />
<x-backend.select name="method" :label="__('Payment method')" :options="collect($methods)->map(fn($label)=>__($label))->all()" />
<x-backend.input name="external_reference" :label="__('External reference')" maxlength="128" />
<p class="text-sm text-qpos-muted">{{ __('An open cash session is required. Only cash reduces physical cash; other enabled methods require a transaction reference.') }}</p>
<x-backend.input name="description" :label="__('Description')" maxlength="500" required /><button @disabled($activeCategories->isEmpty()) class="qpos-button qpos-button-md qpos-button-primary">{{ __('Record expense') }}</button></form></x-backend.card>
@if($canManageCategories)
<section id="expense-categories"><x-backend.card :title="__('Expense categories')">
<p class="text-sm mb-3">{{ __('Expense categories are shared by all stores. Deactivation preserves past expenses.') }}</p>
<form method="POST" action="{{ route('backend.admin.expense-categories.store') }}" class="grid gap-3 md:grid-cols-2">@csrf @include('backend.phase4.context')<x-backend.input name="name" :label="__('Name')" maxlength="255" required /><button class="qpos-button qpos-button-md qpos-button-secondary self-end">{{ __('Add category') }}</button></form>
@foreach($categories as $c)<form method="POST" action="{{ route('backend.admin.expense-categories.store') }}" class="grid gap-3 mt-3 md:grid-cols-3">@csrf @include('backend.phase4.context')<input type="hidden" name="id" value="{{ $c->id }}"><x-backend.input :name="'name'" :id="'category-name-'.$c->id" :label="__('Name')" :value="$c->name" :restoreOld="false" maxlength="255" required /><label class="flex items-center gap-2"><input name="is_active" type="checkbox" value="1" @checked($c->is_active)>{{ __('Active') }}</label><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Save') }}</button></form>@endforeach
</x-backend.card></section>
@endif
@endcan
<x-backend.card :title="__('Expense history')"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Date') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Payment method') }}</th><th>{{ __('Description') }}</th><th>{{ __('Cash session') }}</th></tr></thead><tbody>
@forelse($expenses as $e)<tr><td>{{ $e->id }}</td><td>{{ Illuminate\Support\Carbon::parse($e->occurred_at,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($e->amount) }} XAF</td><td>{{ __(App\Services\PaymentMethodService::LABELS[$e->method] ?? $e->method) }}</td><td>{{ $e->description }}</td><td>#{{ $e->cash_session_id }}</td></tr>@empty<tr><td colspan="6">{{ __('No expenses in this store.') }}</td></tr>@endforelse</tbody></table></div>{{ $expenses->links() }}</x-backend.card>
</div>
@endsection
