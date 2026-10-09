// Display formatting only. Original decimal strings remain the API values.
const locale = () => window.qposLocale === "fr" ? "fr-FR" : "en-US";
export const normalizeInput = value => String(value ?? "").trim().replace(",", ".");
export function plainQuantity(value) {
    const raw = normalizeInput(value || "0");
    return raw.includes(".") ? raw.replace(/0+$/, "").replace(/\.$/, "") : raw;
}
export function quantity(value) {
    const raw = plainQuantity(value);
    return locale() === "fr-FR" ? raw.replace(".", ",") : raw;
}
export function money(value) {
    const match = /^(-?)(\d+)(?:\.(\d*))?$/.exec(normalizeInput(value || "0"));
    if (!match) return "—";
    const fraction = (match[3] || "").padEnd(3, "0");
    let cents = BigInt(match[2]) * 100n + BigInt(fraction.slice(0, 2));
    if (fraction[2] >= "5") cents += 1n;
    const integer = new Intl.NumberFormat(locale(), {maximumFractionDigits:0}).format(cents / 100n);
    const separator = locale() === "fr-FR" ? "," : ".";
    return (match[1] && cents > 0n ? "−" : "") + integer + separator + (cents % 100n).toString().padStart(2, "0");
}
export const validXafPayment = value => /^\d{1,14}(?:\.0{1,6})?$/.test(normalizeInput(value || "0"));
