@extends('backend.master')

@section('title', __('Transactions for sale #:id', ['id' => $order->id]))

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
                <th>{{ __('Transaction ID') }}</th>
                <th>{{ __('Amount') }} {{currency()->symbol??''}}</th>
                <th>{{ __('Paid By') }}</th>
                <th>{{ __('Created') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($order->transactions as $index => $transaction)
              <tr>
                <td>{{ $index + 1}}</td>
                <td>#{{$transaction->id}}</td>
                <td>{{number_format($transaction->amount,2,'.',',')}}</td>
                <td>{{$transaction->paid_by}}</td>
                <td>{{ $transaction->created_at->translatedFormat('M-d Y, h:i A') }}</td>
                <td>
                  <a class="btn btn-success btn-sm" href="{{route('backend.admin.collectionInvoice',$transaction->id)}}">{{ __('Invoice') }}</a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center">{{ __('No transaction found.') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
@endpush
