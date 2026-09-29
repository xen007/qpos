/**
 * Actions de ligne des tables DataTables (pages migrees vers Tailwind).
 *
 * Le layout AdminLTE fait rendre ces actions par les controleurs, en markup
 * Bootstrap (btn-group + dropdown + data-toggle) : incompatible avec une page
 * sans Bootstrap. Les pages migrees composent donc la cellule cote page, a
 * partir des donnees neutres renvoyees par le serveur (dont l'identifiant).
 *
 * Aucune dependance : ni jQuery, ni Bootstrap. Le rendu est expose dans
 * window.qposTableActions pour etre appele depuis le script de la page.
 */
(() => {
    const escapeHtml = (value) =>
        String(value).replace(
            /[&<>"']/g,
            (character) =>
                ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                })[character]
        );

    const buttonTone =
        "flex h-9 w-9 items-center justify-center rounded-lg border border-qpos-line text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink";

    const disabledTone = "pointer-events-none opacity-50";

    /**
     * Boutons d'action d'une ligne.
     *
     * @param {object} options
     * @param {string} options.csrf  jeton CSRF (formulaires)
     * @param {Array}  options.items actions : { type: 'link'|'form', url, method,
     *                               label, icon (classe Font Awesome), confirm,
     *                               disabled (enregistrement protege) }
     */
    const buttons = ({ csrf, items = [] }) => {
        const rendered = items
            .map((item) => {
                const label = escapeHtml(item.label);
                const icon = `<i class="${escapeHtml(item.icon)}" aria-hidden="true"></i>`;
                const tone = `${buttonTone} ${item.disabled ? disabledTone : ""}`;
                const disabledLink = item.disabled ? ' aria-disabled="true" tabindex="-1"' : "";

                if (item.type === "form") {
                    const confirm = item.confirm && !item.disabled
                        ? ` onsubmit="return confirm('${escapeHtml(item.confirm)}')"`
                        : "";
                    const method =
                        item.method && item.method !== "POST"
                            ? `<input type="hidden" name="_method" value="${escapeHtml(item.method)}">`
                            : "";

                    return `
                        <form action="${escapeHtml(item.url)}" method="POST"${confirm}>
                            <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                            ${method}
                            <button type="submit" class="${tone}" title="${label}"
                                aria-label="${label}"${item.disabled ? " disabled" : ""}>${icon}</button>
                        </form>`;
                }

                return `<a href="${escapeHtml(item.url)}" class="${tone}" title="${label}"
                    aria-label="${label}"${disabledLink}>${icon}</a>`;
            })
            .join("");

        return `<div class="flex items-center justify-end gap-2">${rendered}</div>`;
    };

    /**
     * Cas courant : modifier (lien) et supprimer (formulaire DELETE).
     */
    const inline = ({ editUrl, destroyUrl, csrf, labels }) =>
        buttons({
            csrf,
            items: [
                {
                    type: "link",
                    url: editUrl,
                    label: labels.edit,
                    icon: "fas fa-edit",
                },
                {
                    type: "form",
                    url: destroyUrl,
                    method: "DELETE",
                    label: labels.delete,
                    icon: "fas fa-trash",
                    confirm: labels.confirm,
                },
            ],
        });

    /**
     * Badge d'etat (actif / inactif) pour les colonnes de statut.
     * `tones` permet de remplacer les couleurs par defaut, par exemple pour un
     * etat « suspendu » en rouge.
     */
    const statusBadge = (isActive, labels, tones = {}) => {
        const tone = isActive
            ? (tones.active ?? "bg-qpos-brand text-white")
            : (tones.inactive ?? "bg-qpos-page text-qpos-muted border border-qpos-line");

        return `<span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${tone}">${
            isActive ? escapeHtml(labels.active) : escapeHtml(labels.inactive)
        }</span>`;
    };

    window.qposTableActions = { buttons, inline, statusBadge, escapeHtml };
})();
