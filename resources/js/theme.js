/**
 * Bascule du theme clair/sombre.
 *
 * Source unique partagee par les deux layouts back-office (AdminLTE et Tailwind).
 * Contrat DOM :
 *   - [data-theme-toggle] : bouton de bascule
 *   - [data-theme-icon]   : icone Font Awesome a permuter (fa-moon / fa-sun)
 * Le theme initial est applique avant peinture par le script inline du <head>
 * (localStorage 'qpos-theme', puis prefers-color-scheme).
 */
(() => {
    const root = document.documentElement;
    const toggle = document.querySelector("[data-theme-toggle]");
    const icon = document.querySelector("[data-theme-icon]");

    if (!toggle) {
        return;
    }

    const updateIcon = () => {
        const isDark = root.dataset.theme === "dark";
        if (icon) {
            icon.classList.toggle("fa-moon", !isDark);
            icon.classList.toggle("fa-sun", isDark);
        }
        toggle.setAttribute("aria-pressed", String(isDark));
    };

    updateIcon();

    toggle.addEventListener("click", () => {
        const nextTheme = root.dataset.theme === "dark" ? "light" : "dark";
        root.dataset.theme = nextTheme;
        localStorage.setItem("qpos-theme", nextTheme);
        updateIcon();
    });
})();
