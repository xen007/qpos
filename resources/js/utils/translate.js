export default function translate(key) {
    return window.qposTranslations?.[key] ?? key;
}
