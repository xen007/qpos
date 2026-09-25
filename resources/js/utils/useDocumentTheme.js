import { useEffect, useState } from "react";

export default function useDocumentTheme() {
    const [theme, setTheme] = useState(() =>
        document.documentElement.dataset.theme === "dark" ? "dark" : "light"
    );

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setTheme(
                document.documentElement.dataset.theme === "dark"
                    ? "dark"
                    : "light"
            );
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["data-theme"],
        });

        return () => observer.disconnect();
    }, []);

    return theme;
}
