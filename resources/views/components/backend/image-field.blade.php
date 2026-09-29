{{--
    Champ d'image (skin Tailwind).

    Le script public/js/image-field.js agit dans le conteneur
    [data-qpos-image-field], ce qui permet plusieurs champs sur une meme page.
    Sans `field-id`, les identifiants du contrat d'origine sont conserves
    (#imageUploadContainer, #thumbnailInput, #thumbnailPreview) ; avec
    `field-id`, ils en derivent pour rester uniques.

    - sans image courante : l'apercu est masque et l'invite d'envoi est visible ;
    - avec image courante : l'apercu est visible et l'invite masquee,
      exactement comme les formulaires AdminLTE d'origine.
--}}
@props(['name', 'label' => null, 'currentImage' => null, 'currentImageUrl' => null, 'fieldId' => null])

@php
    $hasImage = filled($currentImageUrl) || filled($currentImage);
    $previewSrc = filled($currentImageUrl)
        ? $currentImageUrl
        : ($hasImage ? asset('storage/' . $currentImage) : asset('backend/assets/images/blank.png'));
    $containerId = $fieldId ?? 'imageUploadContainer';
    $inputId = $fieldId ? $fieldId . '-input' : 'thumbnailInput';
    $previewId = $fieldId ? $fieldId . '-preview' : 'thumbnailPreview';
    $previewContainerId = $fieldId ? $fieldId . '-preview-container' : 'thumbPreviewContainer';
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-qpos-ink">{{ $label }}</label>
    @endif

    <div id="{{ $containerId }}" data-qpos-image-field
        class="mt-1 flex h-52 w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-qpos-brand/50 bg-qpos-page">
        <input type="file" name="{{ $name }}" id="{{ $inputId }}" accept="image/*" class="hidden">

        <div id="{{ $previewContainerId }}"
            class="flex h-full w-full items-center justify-center overflow-hidden p-2">
            <img src="{{ $previewSrc }}" alt="{{ __('Thumbnail Preview') }}" id="{{ $previewId }}"
                class="max-h-full max-w-full rounded-lg object-cover {{ $hasImage ? '' : 'd-none hidden' }}"
                @if ($hasImage) onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';" @endif>

            <span class="upload-text {{ $hasImage ? 'd-none hidden' : '' }} flex-col items-center gap-1 text-qpos-brand">
                <i class="fas fa-plus-circle text-2xl" aria-hidden="true"></i>
                <span class="text-sm">{{ __('Upload Image') }}</span>
            </span>
        </div>
    </div>
</div>

@once
    @push('script')
        <script src="{{ asset('js/image-field.js') }}"></script>
    @endpush
@endonce
