{{--
    Select2 : a declarer par les pages qui utilisent des listes deroulantes
    enrichies (@include('backend.layouts.tailwind.plugins.select2')).

    Le layout AdminLTE initialise '.select2' via public/js/custom-script.js,
    qui n'est pas charge sur les pages migrees : la page doit initialiser ses
    propres champs dans son bloc @push('script').
--}}
@include('backend.layouts.tailwind.plugins.jquery')

@once
    @push('style')
        <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endpush

    @push('script')
        <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    @endpush
@endonce
