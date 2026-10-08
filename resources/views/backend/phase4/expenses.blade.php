@extends('backend.master-tailwind')
@section('title', __('Expenses'))
@section('content')
<div class="space-y-6">
@can('expense_manage')
<x-backend.card :title="__('Record expense')"><form method="POST" action="{{ route('backend.admin.expenses.store') }}" class="grid gap-4">@csrf @include('backend.phase4.context')
<x-backend.select name="expense_category_id" :label="__('Category')" :options="$categories->where('is_active',true)->pluck('name','id')->all()" required />
<x-backend.input name="amount" :label="__('Amount')" type="number" min="1" step="1" required />
<x-backend.select name="method" :label="__('Payment method')" :options="['cash'=>__('Cash'),'card'=>__('External card'),'transfer'=>__('Transfer')]" />
<x-backend.input name="external_reference" :label="__('External reference')" maxlength="128" />
<x-backend.input name="description" :label="__('Description')" maxlength="500" required /><button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Record expense') }}</button></form></x-backend.card>
<x-backend.card :title="__('Expense categories')"><form method="POST" action="{{ route('backend.admin.expense-categories.store') }}" class="flex gap-3">@csrf @include('backend.phase4.context')<x-backend.input name="name" :label="__('Name')" required /><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Add category') }}</button></form>
@foreach($categories as $c)<form method="POST" action="{{ route('backend.admin.expense-categories.store') }}" class="flex gap-3 mt-3">@csrf @include('backend.phase4.context')<input type="hidden" name="id" value="{{ $c->id }}"><input class="qpos-control" name="name" value="{{ $c->name }}" required maxlength="255"><label><input name="is_active" type="checkbox" value="1" @checked($c->is_active)>{{ __('Active') }}</label><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Save') }}</button></form>@endforeach</x-backend.card>
@endcan
<x-backend.card :title="__('Expense history')"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Date') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Payment method') }}</th><th>{{ __('Description') }}</th><th>{{ __('Cash session') }}</th></tr></thead><tbody>
@foreach($expenses as $e)<tr><td>{{ $e->id }}</td><td>{{ Illuminate\Support\Carbon::parse($e->occurred_at,'UTC')->timezone('Africa/Douala')->format('d/m/Y H:i') }}</td><td>{{ $e->amount }} XAF</td><td>{{ __($e->method) }}</td><td>{{ $e->description }}</td><td>#{{ $e->cash_session_id }}</td></tr>@endforeach</tbody></table></div>{{ $expenses->links() }}</x-backend.card>
</div>
@endsection
