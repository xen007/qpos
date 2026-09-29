{{--
    jQuery : dependance des plugins (DataTables, select2, daterangepicker...).
    Le layout AdminLTE le charge globalement ; les pages migrees le declarent
    via les partiels de plugins, qui incluent tous celui-ci.
    @once garantit un seul chargement meme si plusieurs plugins sont declares.
--}}
@once
    @push('script')
        <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    @endpush
@endonce
