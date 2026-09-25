@extends('backend.master')

@section('title', __('Sales'))

@section('content')
<div class="card">
  <div class="card-body p-2 p-md-4 pt-0">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body table-responsive p-0" id="table_data">
          <table id="datatables" class="table table-hover">
            <thead>
              <tr>
                <th data-orderable="false">#</th>
                <th>{{ __('Sale ID') }}</th>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Items') }}</th>
                <th>{{ __('Sub Total') }} {{currency()->symbol??''}}</th>
                <th>{{ __('Discount') }} {{currency()->symbol??''}}</th>
                <th>{{ __('Total') }} {{currency()->symbol??''}}</th>
                <th>{{ __('Paid') }} {{currency()->symbol??''}}</th>
                <th>{{ __('Due') }} {{currency()->symbol??''}}</th>
                <th>{{ __('Status') }}</th>
                <th data-orderable="false">{{ __('Action') }}</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')

<script type="text/javascript">
  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      language: {
        emptyTable: @json(__('No data available in table')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
        infoFiltered: @json(__('(filtered from _MAX_ total entries)')),
        lengthMenu: @json(__('Show _MENU_ entries')),
        loadingRecords: @json(__('Loading...')),
        processing: @json(__('Processing...')),
        search: @json(__('Search:')),
        zeroRecords: @json(__('No matching records found')),
        paginate: {
          first: @json(__('First')),
          last: @json(__('Last')),
          next: @json(__('Next')),
          previous: @json(__('Previous'))
        }
      },
      order: [
        [1, 'desc']
      ],
      ajax: {
        url: "{{ route('backend.admin.orders.index') }}"
      },

      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex'
        },
        {
          data: 'saleId',
          name: 'id'
        },
        {
          data: 'customer',
          name: 'customer'
        },
        {
          data: 'item',
          name: 'item_quantity_sum'
        },
        {
          data: 'sub_total',
          name: 'sub_total'
        },
        {
          data: 'discount',
          name: 'discount'
        },
        {
          data: 'total',
          name: 'total'
        }, 
         {
          data: 'paid',
          name: 'paid'
        },
         {
          data: 'due',
          name: 'due'
        },
        {
          data: 'status',
          name: 'status'
        },
        {
          data: 'action',
          name: 'action'
        },
      ]
    });
  });
</script>
@endpush
