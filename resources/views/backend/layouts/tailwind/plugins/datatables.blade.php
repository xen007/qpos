{{--
    DataTables (mode serveur) : a declarer par les pages de liste
    (@include('backend.layouts.tailwind.plugins.datatables')).

    Reprend exactement les feuilles et scripts du layout AdminLTE, dans le meme
    ordre, pour que les tables serveur se comportent a l'identique.
--}}
@include('backend.layouts.tailwind.plugins.jquery')

@once
    @push('style')
        <link rel="stylesheet" href="{{ asset('assets/css/datatable/datatable.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/datatable/buttons.dataTables.min.css') }}">
    @endpush

    @push('script')
        <script src="{{ asset('assets/js/datatable/datatable.js') }}"></script>
        <script src="{{ asset('assets/js/datatable/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('assets/js/datatable/dataTables.buttons.min.js') }}"></script>
        @include('backend.layouts.partials.datatables-i18n')
    @endpush
@endonce
