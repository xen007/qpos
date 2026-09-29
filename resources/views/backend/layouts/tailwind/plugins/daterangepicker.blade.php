{{--
    Selecteur de periode (bootstrap-daterangepicker + moment) : a declarer par
    les pages de rapports, qui portent aussi leur script d'initialisation.

    Attention : le theme sombre du plugin est decrit dans public/css/custom-style.css
    (non charge sur les pages migrees). Une page migree doit donc soit fournir
    ses propres styles, soit utiliser des champs de date natifs.
--}}
@include('backend.layouts.tailwind.plugins.jquery')

@once
    @push('style')
        <link rel="stylesheet" href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}">
    @endpush

    @push('script')
        <script src="{{ asset('plugins/moment/moment-with-locales.min.js') }}"></script>
        <script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    @endpush
@endonce
