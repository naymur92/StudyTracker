<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('/') }}assets/vendor/Font-Awesome-6.x/css/all.min.css" rel="stylesheet" type="text/css">

    {{-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> --}}
    <link href="{{ generateAssetPath('assets/css/fonts.css') }}" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ generateAssetPath('assets/css/sb-admin-2.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('/') }}assets/vendor/sweetalert2@11.10.3/sweetalert2.min.css">
    <link href="{{ asset('/') }}assets/vendor/select2@4.1.0-rc.0/select2.min.css" rel="stylesheet" />

    <link href="{{ asset('/') }}assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker.css" rel="stylesheet">

    <link href="{{ generateAssetPath('assets/css/customized-bootstrap-datepicker.css') }}" rel="stylesheet">

    <!-- Styles -->
    @vite('resources/sass/app.scss')

    @stack('styles')
    <link href="{{ generateAssetPath('assets/css/custom.css') }}" rel="stylesheet">

    <style>
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
            text-align: right;
        }
    </style>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('/') }}assets/vendor/jquery/jquery.min.js"></script>
    <script src="{{ asset('/') }}assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('/') }}assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    {{-- style files for printing --}}
    <script>
        var fontUrls = [];
        // "https://fonts.googleapis.com/css?family=Rubik:300,400,500,700&display=swap",
        // "https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap",
        // "https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap",
        // "https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&display=swap",
        // "https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&display=swap",
        var cssFiles = [
            "{{ asset('/') }}assets/vendor/Font-Awesome-6.x/css/all.min.css",
            "{{ generateAssetPath('assets/css/print-style.css') }}",
        ];
        // "{{ generateAssetPath('assets/css/sb-admin-2.css') }}",
        // "{{ generateAssetPath('assets/css/custom.css') }}"
    </script>
    <!-- Custom scripts for all pages-->
    <script src="{{ generateAssetPath('assets/js/sb-admin-2.js') }}"></script>
    <script src="{{ generateAssetPath('assets/js/global_helpers.js') }}"></script>

    <script>
        window._asset = '{{ asset('') }}';
        window._app_url = '{{ url('') }}';
    </script>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        @include('layouts.sidebar')
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                @include('layouts.topbar')
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                @yield('content')
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            @include('layouts.footer')
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Scripts -->
    <script src="{{ asset('/') }}assets/vendor/sweetalert2@11.10.3/sweetalert2.all.min.js"></script>
    <script src="{{ asset('/') }}assets/vendor/select2@4.1.0-rc.0/select2.min.js"></script>
    <script src="{{ generateAssetPath('assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ generateAssetPath('assets/vendor/bootstrap-datepicker/locales/bootstrap-datepicker.bn.min.js') }}"></script>

    {{-- moment js --}}
    <script src="{{ asset('/') }}assets/vendor/moment.js/2.15.1/moment.min.js"></script>
    <script src="{{ asset('/') }}assets/vendor/moment.js/2.29.4/moment-with-locales.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script src="{{ generateAssetPath('assets/js/number-inputs.js') }}" defer></script>
    <script src="{{ generateAssetPath('assets/js/customized-bootstrap-datepicker.js') }}" defer></script>
    <script src="{{ generateAssetPath('assets/js/custom.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('select').select2();
        });
    </script>

    @stack('scripts')

    @vite('resources/js/app.js')

</body>

</html>
