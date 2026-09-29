{{--
    Traduction par defaut des tables DataTables (FR/EN).

    Source unique, partagee par le layout AdminLTE (backend.master) et par le
    partiel de plugin du layout Tailwind. Les libelles viennent du catalogue de
    la locale active, expose dans window.qposTranslations par les deux layouts.
--}}
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
