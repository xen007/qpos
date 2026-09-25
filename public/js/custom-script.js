$(function () {
    // Initialize SummerNote
    $(".summerNote").summernote({
        height: 200,
    });

    //Initialize Select2 Elements
    $(".select2").select2();
});

function previewThumbnail(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var thumbnailPreview =
                input.parentNode.querySelector(".thumbnail-preview");
            if (thumbnailPreview) {
                thumbnailPreview.src = e.target.result;
            }
        };

        reader.readAsDataURL(input.files[0]);
    }
}
(() => {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');
    const icon = document.querySelector('[data-theme-icon]');

    if (!toggle) return;

    const updateIcon = () => {
        const isDark = root.dataset.theme === 'dark';
        if (icon) {
            icon.classList.toggle('fa-moon', !isDark);
            icon.classList.toggle('fa-sun', isDark);
        }
        toggle.setAttribute('aria-pressed', String(isDark));
    };

    updateIcon();
    toggle.addEventListener('click', () => {
        const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = nextTheme;
        localStorage.setItem('qpos-theme', nextTheme);
        updateIcon();
    });
})();
