import { useEffect } from "react";
import { Toaster, toast } from "sonner";
import useDocumentTheme from "../utils/useDocumentTheme";

/**
 * Notifications flash du back-office (Sonner).
 *
 * Les messages sont produits par le serveur (x-backend.flash-toasts) et
 * transmis ici en JSON : succes, avertissement et erreurs de validation.
 * Le theme suit data-theme via useDocumentTheme, comme l'ecran de caisse.
 */
const notifiers = {
    success: toast.success,
    warning: toast.warning,
    error: toast.error,
};

export default function FlashToasts({ messages = [] }) {
    const theme = useDocumentTheme();

    useEffect(() => {
        messages.forEach(({ type, message }) => {
            const notify = notifiers[type] ?? toast;

            notify(message);
        });
    }, [messages]);

    return (
        <Toaster position="top-right" richColors closeButton theme={theme} />
    );
}
