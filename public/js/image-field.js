/**
 * Apercu de l'image choisie dans le champ fichier.
 *
 * Une meme page peut porter plusieurs champs : chacun est repere par
 * [data-qpos-image-field] et le script agit a l'interieur de son conteneur (il
 * ne depend donc plus d'identifiants uniques). Le balisage historique
 * (#imageUploadContainer) reste pris en charge pour les pages AdminLTE.
 *
 * Les bascules d-none (Bootstrap) et hidden (Tailwind) sont conservees : le
 * fichier sert les deux layouts pendant la migration.
 */
(() => {
    const initField = (container) => {
        const inputFile = container.querySelector('input[type="file"]');
        const thumbnailPreview = container.querySelector('img');
        const uploadText = container.querySelector('.upload-text');

        if (!inputFile || !thumbnailPreview || !uploadText) {
            return;
        }

        container.addEventListener('click', (event) => {
            if (event.target !== inputFile) {
                inputFile.click();
            }
        });

        inputFile.addEventListener('change', () => {
            const reader = new FileReader();

            reader.addEventListener('load', () => {
                thumbnailPreview.src = reader.result;
                thumbnailPreview.classList.remove('d-none', 'hidden');
                uploadText.classList.add('d-none', 'hidden');
            });

            if (inputFile.files[0]) {
                reader.readAsDataURL(inputFile.files[0]);
            }
        });
    };

    document.querySelectorAll('[data-qpos-image-field]').forEach(initField);

    // Compatibilite avec le balisage historique du contrat d'origine.
    const legacy = document.getElementById('imageUploadContainer');

    if (legacy && !legacy.hasAttribute('data-qpos-image-field')) {
        initField(legacy);
    }
})();
