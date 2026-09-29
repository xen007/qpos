import React from "react";
import { createRoot } from "react-dom/client";
import FlashToasts from "./components/FlashToasts";

/**
 * Point de montage des notifications flash (pages migrees vers le layout
 * Tailwind). Le conteneur et le contenu JSON ne sont rendus par le serveur
 * que s'il y a effectivement quelque chose a afficher : ce module n'est donc
 * charge que dans ce cas.
 */
const container = document.getElementById("qpos-flash-root");
const payload = document.getElementById("qpos-flash-messages");

if (container && payload) {
    let messages = [];

    try {
        messages = JSON.parse(payload.textContent || "[]");
    } catch (error) {
        console.error("Notifications flash illisibles :", error);
    }

    createRoot(container).render(<FlashToasts messages={messages} />);
}
