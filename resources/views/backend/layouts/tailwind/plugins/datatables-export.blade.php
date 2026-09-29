{{--
    DataTables + boutons d'export (Excel, PDF, Impression) : a declarer par les
    pages de rapport (@include('backend.layouts.tailwind.plugins.datatables-export')).

    Les bibliotheques sont celles du projet (public/assets/js/datatable). L'ancienne
    page du rapport de stock chargeait ces memes fichiers depuis des CDN, ce qui
    rendait les exports dependants d'Internet ; seules les versions locales sont
    desormais utilisees.
--}}
@include('backend.layouts.tailwind.plugins.datatables')

@once
    @push('script')
        <script src="{{ asset('assets/js/datatable/buttons.html5.min.js') }}"></script>
        <script src="{{ asset('assets/js/datatable/buttons.print.min.js') }}"></script>
        <script src="{{ asset('assets/js/datatable/jszip.min.js') }}"></script>
        <script src="{{ asset('assets/js/datatable/pdfmake.min.js') }}"></script>
        <script src="{{ asset('assets/js/datatable/vfs_fonts.js') }}"></script>
    @endpush
@endonce
