@extends('backend.master-tailwind')

@section('title', __('Inventory Report'))

@section('content')
    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
            <table id="datatables" class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3">{{ __('SKU') }}</th>
                        <th class="px-3 py-3">{{ __('Price') }}</th>
                        <th class="px-3 py-3">{{ __('Stock') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-backend.card>
@endsection

@include('backend.layouts.tailwind.plugins.datatables-export')

@push('style')
    <style>
        /* Markup genere par DataTables et par ses boutons : ces elements ne
           peuvent pas recevoir d'utilitaires Tailwind. */
        .dt-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .dt-buttons .dt-button {
            background: none;
            border: 0;
        }

        .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dataTables_length select {
            border: 1px solid var(--qpos-line);
            border-radius: 0.5rem;
            background: var(--qpos-surface);
            color: var(--qpos-ink);
            padding: 0.35rem 0.5rem;
        }
    </style>
@endpush

@push('script')
    <script type="text/javascript">
        $(function() {
            const exportButton = 'rounded-lg border border-qpos-line px-3 py-2 text-sm font-medium text-qpos-muted transition hover:bg-qpos-page';

            $('#datatables').DataTable({
                processing: true,
                serverSide: true,
                ordering: true,
                order: [
                    [1, 'desc']
                ],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: "{{ route('backend.admin.inventory.report') }}"
                },
                lengthChange: true,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        // Prix applique, avec le prix d'origine barre s'il est plus eleve.
                        data: 'price_value',
                        name: 'price_value',
                        render: (value, type, row) => parseFloat(row.price_original) > parseFloat(value) ?
                            value + '<br><del class="text-qpos-muted">' + row.price_original + '</del>' :
                            value,
                    },
                    {
                        // Stock suivi de l'unite.
                        data: 'quantity_value',
                        name: 'quantity_value',
                        render: (value, type, row) => row.unit_short ? value + ' ' + row.unit_short : value,
                    },
                ],
                dom: 'lBfrtip', // active les boutons
                buttons: [{
                        extend: 'excel',
                        text: @json(__('Export to Excel')),
                        className: exportButton
                    },
                    {
                        extend: 'pdf',
                        text: @json(__('Export to PDF')),
                        className: exportButton
                    },
                    {
                        extend: 'print',
                        text: @json(__('Print')),
                        className: exportButton
                    }
                ],
                initComplete: function() {
                    // Masque le texte du selecteur de longueur, seul le menu reste.
                    $('.dataTables_length label').contents().filter(function() {
                        return this.nodeType === 3;
                    }).remove();
                }
            });
        });
    </script>
@endpush
