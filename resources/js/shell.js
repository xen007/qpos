/**
 * Comportements du shell Tailwind (pages migrees).
 *
 * Aucune dependance : ni jQuery, ni Bootstrap, ni AdminLTE. Les classes
 * Tailwind basculees ici sont volontairement ecrites en clair pour qu'elles
 * soient detectees a la compilation (voir @source dans resources/css/app.css).
 *
 * Contrats DOM :
 *   - [data-qpos-drawer] / [data-qpos-drawer-toggle] / [data-qpos-drawer-overlay]
 *   - [data-qpos-nav-toggle] / [data-qpos-nav-panel] / [data-qpos-nav-chevron]
 *   - [data-qpos-dropdown] / [data-qpos-dropdown-toggle] / [data-qpos-dropdown-panel]
 *   - [data-qpos-tabs] / [data-qpos-tab] / [data-qpos-tab-panel]
 *   - [data-qpos-fullscreen]
 */
(() => {
    const desktopQuery = window.matchMedia("(min-width: 1024px)");

    // --- Tiroir de navigation (mobile) --------------------------------------
    const drawer = document.querySelector("[data-qpos-drawer]");
    const overlay = document.querySelector("[data-qpos-drawer-overlay]");
    const drawerToggles = document.querySelectorAll("[data-qpos-drawer-toggle]");
    const content = document.querySelector("[data-qpos-shell-content]");
    let drawerFocus;

    const setDrawer = (open) => {
        if (!drawer) {
            return;
        }

        const mobileOpen = open && !desktopQuery.matches;
        if (mobileOpen) drawerFocus = document.activeElement;
        drawer.inert = !open && !desktopQuery.matches;
        if (content) content.inert = mobileOpen;
        drawer.classList.toggle("-translate-x-full", !open);
        drawer.classList.toggle("translate-x-0", open);
        overlay?.classList.toggle("hidden", !open);
        document.body.classList.toggle("overflow-hidden", mobileOpen);
        drawerToggles.forEach((toggle) =>
            toggle.setAttribute("aria-expanded", String(open))
        );
        if (mobileOpen) drawer.querySelector("[data-qpos-drawer-toggle]")?.focus();
        else if (drawerFocus) { drawerFocus.focus(); drawerFocus = null; }
    };

    if (drawer) {
        setDrawer(false);

        drawerToggles.forEach((toggle) =>
            toggle.addEventListener("click", () =>
                setDrawer(drawer.classList.contains("-translate-x-full"))
            )
        );

        overlay?.addEventListener("click", () => setDrawer(false));

        // Retour a l'etat ferme quand on repasse en dessous du breakpoint.
        desktopQuery.addEventListener("change", (event) => {
            setDrawer(false);
        });
        document.addEventListener("keydown", (event) => {
            if (event.key !== "Tab" || desktopQuery.matches || drawer.classList.contains("-translate-x-full")) return;
            const focusable = [...drawer.querySelectorAll('a[href], button:not(:disabled), [tabindex="0"]')]
                .filter((element) => element.getClientRects().length);
            const first = focusable[0];
            const last = focusable.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        });
    }

    // --- Groupes de navigation repliables ----------------------------------
    document.querySelectorAll("[data-qpos-nav-toggle]").forEach((button) => {
        button.addEventListener("click", () => {
            const panel = button.parentElement?.querySelector(
                "[data-qpos-nav-panel]"
            );

            if (!panel) {
                return;
            }

            const open = !panel.classList.toggle("hidden");
            button.setAttribute("aria-expanded", String(open));
            button
                .querySelector("[data-qpos-nav-chevron]")
                ?.classList.toggle("-rotate-90", open);
        });
    });

    // --- Menus deroulants ---------------------------------------------------
    const dropdowns = Array.from(
        document.querySelectorAll("[data-qpos-dropdown]")
    );

    const closeDropdowns = (except) => {
        dropdowns.forEach((dropdown) => {
            if (dropdown === except) {
                return;
            }

            dropdown
                .querySelector("[data-qpos-dropdown-panel]")
                ?.classList.add("hidden");
            dropdown
                .querySelector("[data-qpos-dropdown-toggle]")
                ?.setAttribute("aria-expanded", "false");
        });
    };

    dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector("[data-qpos-dropdown-toggle]");
        const panel = dropdown.querySelector("[data-qpos-dropdown-panel]");

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener("click", (event) => {
            event.stopPropagation();
            const willOpen = panel.classList.contains("hidden");

            closeDropdowns(dropdown);
            panel.classList.toggle("hidden", !willOpen);
            toggle.setAttribute("aria-expanded", String(willOpen));
        });

        panel.addEventListener("click", () => closeDropdowns());
    });

    document.addEventListener("click", () => closeDropdowns());

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            const open = dropdowns.find((dropdown) => !dropdown.querySelector("[data-qpos-dropdown-panel]")?.classList.contains("hidden"));
            closeDropdowns();
            open?.querySelector("[data-qpos-dropdown-toggle]")?.focus();
            setDrawer(false);
        }
    });

    // --- Modales natives (<dialog>) ----------------------------------------
    document.querySelectorAll("[data-qpos-modal-open]").forEach((trigger) => {
        trigger.addEventListener("click", () => {
            const dialog = document.querySelector(
                trigger.dataset.qposModalOpen
            );

            dialog?.showModal?.();
        });
    });

    document.querySelectorAll("[data-qpos-modal]").forEach((dialog) => {
        dialog
            .querySelectorAll("[data-qpos-modal-close]")
            .forEach((button) => {
                button.addEventListener("click", () => dialog.close());
            });

        // Clic sur le fond : la cible est le <dialog> lui-meme.
        dialog.addEventListener("click", (event) => {
            const rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) {
                dialog.close();
            }
        });
    });

    // --- Onglets ------------------------------------------------------------
    document.querySelectorAll("[data-qpos-tabs]").forEach((group) => {
        const triggers = Array.from(group.querySelectorAll("[data-qpos-tab]"));
        const panels = Array.from(
            group.querySelectorAll("[data-qpos-tab-panel]")
        );

        if (triggers.length === 0) {
            return;
        }

        const activate = (name) => {
            triggers.forEach((trigger) => {
                const isActive = trigger.dataset.qposTab === name;

                trigger.classList.toggle("bg-qpos-brand", isActive);
                trigger.classList.toggle("text-white", isActive);
                trigger.classList.toggle("text-qpos-muted", !isActive);
                trigger.setAttribute("aria-selected", String(isActive));
                trigger.tabIndex = isActive ? 0 : -1;
            });

            panels.forEach((panel) =>
                panel.classList.toggle(
                    "hidden",
                    panel.dataset.qposTabPanel !== name
                )
            );
        };

        triggers.forEach((trigger) =>
            trigger.addEventListener("click", (event) => {
                event.preventDefault();
                activate(trigger.dataset.qposTab);
            })
        );
        triggers.forEach((trigger, index) => {
            trigger.addEventListener("keydown", (event) => {
                const directions = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
                let next;
                if (event.key in directions) next = (index + directions[event.key] + triggers.length) % triggers.length;
                else if (event.key === "Home") next = 0;
                else if (event.key === "End") next = triggers.length - 1;
                else return;
                event.preventDefault();
                activate(triggers[next].dataset.qposTab);
                triggers[next].focus();
            });
        });

        // Onglet initial : celui demande par l'URL (?active-tab=...), sinon le premier.
        const requested = new URLSearchParams(window.location.search).get(
            "active-tab"
        );
        const initial =
            triggers.find(
                (trigger) => trigger.dataset.qposTab === requested
            ) ?? triggers[0];

        activate(initial.dataset.qposTab);
    });

    // --- Plein ecran --------------------------------------------------------
    document.querySelectorAll("[data-qpos-fullscreen]").forEach((button) => {
        button.addEventListener("click", () => {
            if (document.fullscreenElement) {
                document.exitFullscreen?.();

                return;
            }

            document.documentElement.requestFullscreen?.();
        });
    });
})();
