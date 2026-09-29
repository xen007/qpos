@extends('backend.master-tailwind')

@section('title', __('Transactions for sale #:id', ['id' => $order->id]))

@section('content')
    {{-- Les lignes sont rendues par le serveur : cette page n'a besoin d'aucun
         plugin (l'ancienne table portait un id "datatables" sans initialisation). --}}
    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ __('Transaction ID') }}</th>
                        <th class="px-3 py-3">{{ __('Amount') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Paid By') }}</th>
                        <th class="px-3 py-3">{{ __('Created') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->transactions as $index => $transaction)
                        <tr class="border-b border-qpos-line/60 last:border-0">
                            <td class="px-3 py-3 text-qpos-muted">{{ $index + 1 }}</td>
                            <td class="px-3 py-3 text-qpos-ink">#{{ $transaction->id }}</td>
                            <td class="px-3 py-3 tabular-nums text-qpos-ink">
                                {{ number_format($transaction->amount, 2, '.', ',') }}</td>
                            <td class="px-3 py-3 text-qpos-ink">{{ $transaction->paid_by }}</td>
                            <td class="px-3 py-3 text-qpos-muted">
                                {{ $transaction->created_at->translatedFormat('M-d Y, h:i A') }}</td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end">
                                    <a href="{{ route('backend.admin.collectionInvoice', $transaction->id) }}"
                                        class="inline-flex items-center gap-2 rounded-lg border border-qpos-line px-3 py-2 text-sm font-medium text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink">
                                        <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                        {{ __('Invoice') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- 6 colonnes : l'ancienne version indiquait 5, ce qui decalait le message. --}}
                            <td colspan="6" class="px-3 py-6 text-center text-qpos-muted">
                                {{ __('No transaction found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-backend.card>
@endsection
