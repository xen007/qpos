/**
 * Theme partage par les shells Tailwind et AdminLTE.
 * Le script du <head> applique le theme avant peinture ; ce module le valide,
 * synchronise les controles et reste utilisable sans stockage navigateur.
 */
(() => {
    const root = document.documentElement;
    const systemTheme = window.matchMedia?.("(prefers-color-scheme: dark)");
    const toggles = document.querySelectorAll("[data-theme-toggle]");
    const validTheme = (value) => value === "light" || value === "dark";
    let savedTheme;

    try {
        savedTheme = localStorage.getItem("qpos-theme");
    } catch (_) {
        // Le choix reste applique pour cette page si le stockage est bloque.
    }

    let hasPreference = validTheme(savedTheme);

    const applyTheme = (theme) => {
        root.dataset.theme = theme;
        const isDark = theme === "dark";

        toggles.forEach((toggle) => {
            const icon = toggle.querySelector("[data-theme-icon]");
            icon?.classList.toggle("fa-moon", !isDark);
            icon?.classList.toggle("fa-sun", isDark);
            toggle.setAttribute("aria-pressed", String(isDark));
        });
    };

    applyTheme(hasPreference ? savedTheme : (systemTheme?.matches ? "dark" : "light"));

    toggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const theme = root.dataset.theme === "dark" ? "light" : "dark";
            hasPreference = true;
            applyTheme(theme);

            try {
                localStorage.setItem("qpos-theme", theme);
            } catch (_) {
                // Aucun echec de la bascule si localStorage est indisponible.
            }
        });
    });

    systemTheme?.addEventListener("change", (event) => {
        if (!hasPreference) {
            applyTheme(event.matches ? "dark" : "light");
        }
    });

    // Synchronisation entre onglets ; une preference supprimee suit le systeme.
    window.addEventListener("storage", (event) => {
        if (event.key !== "qpos-theme" && event.key !== null) {
            return;
        }

        hasPreference = validTheme(event.newValue);
        applyTheme(hasPreference ? event.newValue : (systemTheme?.matches ? "dark" : "light"));
    });
})();
