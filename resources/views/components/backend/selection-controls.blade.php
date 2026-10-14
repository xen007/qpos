@props(['scope', 'limit', 'description'])
<div data-selection-controls data-selection-scope="{{ $scope }}" data-selection-limit="{{ $limit }}" class="space-y-2">
    <p class="text-sm text-qpos-muted">{{ $description }}</p>
    <div class="flex flex-wrap gap-2">
        <button type="button" data-selection-value="all" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Select all') }}</button>
        <button type="button" data-selection-value="none" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Deselect all') }}</button>
        <span data-selection-count role="status" aria-live="polite" class="text-sm self-center"></span>
    </div>
</div>
@once
@push('script')
<script>
(() => {
    document.querySelectorAll('[data-selection-controls]').forEach(control => {
        const scope = document.getElementById(control.dataset.selectionScope);
        if (!scope) return;
        const boxes = () => [...scope.querySelectorAll('input[type="checkbox"]:not(:disabled)')];
        const limit = Number(control.dataset.selectionLimit);
        const status = control.querySelector('[data-selection-count]');
        const refresh = () => { status.textContent = boxes().filter(box => box.checked).length + ' / ' + boxes().length; };
        control.addEventListener('click', event => {
            const button = event.target.closest('[data-selection-value]');
            if (!button) return;
            boxes().forEach((box, index) => { box.checked = button.dataset.selectionValue === 'all' && index < limit; });
            refresh();
        });
        scope.addEventListener('change', refresh);
        refresh();
    });
})();
</script>
@endpush
@endonce
