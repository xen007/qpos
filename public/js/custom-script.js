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

// La bascule du theme clair/sombre vit desormais dans resources/js/theme.js
// (module compile par Vite et partage avec le layout Tailwind).
