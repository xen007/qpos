@extends('backend.master')

@section('title', __('Dashboard'))

@section('content')
<section class="content qpos-dashboard">
    @can('dashboard_view')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cog"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('Sale Subtotal') }}</span>
                        <span class="info-box-number">
                            {{currency()->symbol??''}} {{number_format($sub_total,2,'.',',')}}
                            <small></small>
                        </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-thumbs-up"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('Sale Discount') }}</span>
                        <span class="info-box-number">{{currency()->symbol??''}} {{number_format($discount,2,'.',',')}}</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->

            <!-- fix for small devices only -->
            <div class="clearfix hidden-md-up"></div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('Sale') }}</span>
                        <span class="info-box-number">{{currency()->symbol??''}} {{number_format($total,2,'.',',')}}</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('Sale Due') }}</span>
                        <span class="info-box-number">{{currency()->symbol??''}} {{number_format($due,2,'.',',')}}</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
        </div>

        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{$total_customer}}</h3>
                        <p>{{ __('Customers') }}</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="{{route('backend.admin.customers.index')}}" class="small-box-footer">
                        {{ __('More info') }}
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{$total_product}}</h3>
                        <p>{{ __('Products') }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-bar" aria-hidden="true"></i>
                    </div>
                    <a href="{{route('backend.admin.products.index')}}" class="small-box-footer">
                        {{ __('More info') }}
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{$total_order}}</h3>
                        <p>{{ __('Sale') }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                    </div>
                    <a href="{{route('backend.admin.orders.index')}}" class="small-box-footer">
                        {{ __('More info') }}
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{$total_sale_item}}</h3>
                        <p>{{ __('Sale Item') }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-pie" aria-hidden="true"></i>
                    </div>
                    <a href="{{route('backend.admin.orders.index')}}" class="small-box-footer">
                        {{ __('More info') }}
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->


        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Daily Total Sales') }} <small>{{ $dateRange }}</small></h5>
                        <div class="input-group w-auto">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="far fa-calendar-alt"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" id="reservation"
            aria-label="{{ __('Filter sales by date range') }}">
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="qpos-chart-container">
                            <canvas id="dailySaleLineChart" role="img"
                                aria-label="{{ __('Daily Total Sales') }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('Monthly Total Sales') }} <small>{{ __('for') }} {{ $currentYear }}</small></h5>
                    </div>
                    <div class="card-body">
                        <div class="qpos-chart-container">
                            <canvas id="barChartYear" role="img"
                                aria-label="{{ __('Monthly Total Sales') }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    <!-- /.container-fluid -->
</section>
@endsection
@push('style')
<link rel="stylesheet" href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}">
@endpush
@push('script')
@can('dashboard_view')
<script src="{{ asset('plugins/moment/moment-with-locales.min.js') }}"></script>
<script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
<script type="application/json" id="qpos-dashboard-chart-data">@json([
    'dates' => $dates,
    'dailySales' => $totalAmounts,
    'months' => $months,
    'monthlySales' => $totalAmountMonth,
    'salesLabel' => __('Sales'),
])</script>
@vite('resources/js/dashboard.js')
<script>
    $(function() {
        moment.locale(window.qposLocale === 'fr' ? 'fr' : 'en');
        $('#reservation').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: @json(__('Apply')),
                cancelLabel: @json(__('Cancel')),
                fromLabel: @json(__('Date range from')),
                toLabel: @json(__('Date range to')),
                customRangeLabel: @json(__('Custom Range')),
                daysOfWeek: moment.weekdaysMin(),
                monthNames: moment.months(),
                firstDay: moment.localeData().firstDayOfWeek()
            }
        }).on('apply.daterangepicker', function(e, picker) {
            let selectedDateRange = picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD');

            // Update URL with daterange query parameter
            let url = new URL(window.location.href);
            url.searchParams.set('daterange', selectedDateRange);
            window.location.href = url.toString();
        });

    })
</script>
@endcan
@endpush
