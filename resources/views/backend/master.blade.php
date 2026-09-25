<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (() => {
            const savedTheme = localStorage.getItem('qpos-theme');
            const systemPrefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        })();
    </script>
    <title>
        @yield('title', __('Dashboard')) | {{ readConfig('site_name') }}
    </title>

    <!-- FAVICON ICON -->
    <link rel="shortcut icon" href="{{ assetImage(readconfig('favicon_icon')) }}" type="image/svg+xml">

    <!-- FAVICON ICON APPLE -->
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="72x72">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="114x114">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="144x144">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- iCheck -->
    <link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- JQVMap -->
    <link rel="stylesheet" href="{{ asset('plugins/jqvmap/jqvmap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{ asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Daterange picker -->
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- dropzonejs -->
    <link rel="stylesheet" href="{{ asset('plugins/dropzone/min/dropzone.min.css') }}">
    {{-- datatable --}}
    <link rel="stylesheet" href="{{ asset('assets/css/datatable/datatable.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatable/buttons.dataTables.min.css') }}">
    {{-- custom style --}}
    <link rel="stylesheet" href="{{ asset('css/custom-style.css') }}">

    @php
        $localeCatalogPath = lang_path(app()->getLocale() . '.json');
        $localeCatalog = is_file($localeCatalogPath)
            ? json_decode(file_get_contents($localeCatalogPath), true)
            : [];
    @endphp
    <script>
        window.qposLocale = @json(app()->getLocale());
        window.qposTranslations = @json($localeCatalog ?? []);
    </script>

    <style>
        .image-upload-container {
            border: 2px dashed #8b9ee9;
            /* Dashed border color */
            border-radius: 8px;
            background-color: #f8f9fa;
            /* Light background color */
            display: flex;
            justify-content: center;
            /* Center the content */
            align-items: center;
            /* Center the content vertically */
            width: 100%;
            /* Make the container full width of its parent */
            height: 200px;
            /* Fixed height */
            cursor: pointer;
            /* Indicate clickability */
        }

        .thumb-preview {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            /* Prevents overflow */
        }

        #thumbnailPreview {
            max-width: 100%;
            max-height: 100%;
            /* Ensure it fits within the container */
            object-fit: cover;
            /* Maintain aspect ratio while covering the box */
        }

        .upload-text {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #8b9ee9;
            /* Text color */
            text-align: center;
        }

        .upload-text i {
            font-size: 24px;
            /* Icon size */
            margin-bottom: 5px;
            /* Space between icon and text */
        }
    </style>
    @stack('style')
    @if (request()->routeIs('backend.admin.cart.index', 'backend.admin.purchase.create'))
        @viteReactRefresh
        @vite('resources/js/app.jsx')
    @endif
</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <x-simple-alert />

    <div class="wrapper">

        <!-- Preloader -->
        <!-- <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ assetImage(readconfig('site_logo')) }}" alt="Logo" height="60"
                width="60">
        </div> -->

        <!-- Navbar -->
        @include('backend.layouts.navbar')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar elevation-4 sidebar-light-lightblue">
            <!-- Brand Logo -->
            <a href="{{ route('frontend.home') }}" class="brand-link">
                <img src="{{ assetImage(readconfig('site_logo')) }}" alt="Logo"
                    class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">{{ readConfig('site_name') }}</span>
            </a>

            <!-- Sidebar -->
            @include('backend.layouts.sidebar')
            <!-- /.sidebar -->

        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                @yield('title')
                            </h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->


            <!-- Main content -->
            <section class="content">
                <!-- container-fluid -->
                <div class="container-fluid">

                    <!-- content -->
                    @yield('content')
                    <!-- /.content -->

                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.Main content -->
        </div>
        <!-- /.content-wrapper -->

        @include('backend.layouts.footer')

    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- ChartJS -->
    <!-- Sparkline -->
    <script src="{{ asset('plugins/sparklines/sparkline.js') }}"></script>
    <!-- JQVMap -->
    <script src="{{ asset('plugins/jqvmap/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
    <!-- jQuery Knob Chart -->
    <script src="{{ asset('plugins/jquery-knob/jquery.knob.min.js') }}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- Summernote -->
    <script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
    <!-- overlayScrollbars -->
    <script src="{{ asset('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    {{-- custom script --}}
    <script src="{{ asset('js/custom-script.js') }}"></script>
    <!-- dropzonejs -->
    <script src="{{ asset('plugins/dropzone/min/dropzone.min.js') }}"></script>

    {{-- datatable --}}
    <script src="{{ asset('assets/js/datatable/datatable.js') }}"></script>
    <script src="{{ asset('assets/js/datatable/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatable/dataTables.buttons.min.js') }}"></script>

    <script>
        if (window.jQuery?.fn?.dataTable) {
            const t = window.qposTranslations || {};
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    emptyTable: t['No data available in table'] || 'No data available in table',
                    zeroRecords: t['No matching records found'] || 'No matching records found',
                    info: t['Showing _START_ to _END_ of _TOTAL_ entries'] || 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: t['Showing 0 to 0 of 0 entries'] || 'Showing 0 to 0 of 0 entries',
                    infoFiltered: t['(filtered from _MAX_ total entries)'] || '(filtered from _MAX_ total entries)',
                    lengthMenu: t['Show _MENU_ entries'] || 'Show _MENU_ entries',
                    loadingRecords: t['Loading...'] || 'Loading...',
                    processing: t['Processing...'] || 'Processing...',
                    search: t['Search:'] || 'Search:',
                    paginate: {
                        first: t.First || 'First',
                        last: t.Last || 'Last',
                        next: t.Next || 'Next',
                        previous: t.Previous || 'Previous'
                    }
                }
            });
        }
    </script>

    @stack('script')
</body>

</html>
