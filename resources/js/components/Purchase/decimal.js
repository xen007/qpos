const SCALE = 1000000n;
export const parseDecimal = value => {
    const text = String(value ?? "");
    if (!/^\d{1,14}(?:\.\d{1,6})?$/.test(text)) return null;
    const [whole, fraction = ""] = text.split(".");
    return BigInt(whole) * SCALE + BigInt(fraction.padEnd(6,"0"));
};
export const formatDecimal = value => {
    const negative = value < 0n;
    const amount = negative ? -value : value;
    return (negative ? "-" : "") + String(amount / SCALE) + "." + String(amount % SCALE).padStart(6,"0");
};
export const lineAmount = line => {
    const cost = parseDecimal(line.purchase_price), quantity = parseDecimal(line.qty);
    return cost === null || quantity === null ? null : (cost * quantity + SCALE / 2n) / SCALE;
};
export const purchaseTotals = (lines, tax, discount, shipping) => {
    const amounts = lines.map(lineAmount);
    const t = parseDecimal(tax || "0"), d = parseDecimal(discount || "0"), s = parseDecimal(shipping || "0");
    if (amounts.some(amount => amount === null) || t === null || d === null || s === null) return null;
    const subtotal = amounts.reduce((sum, amount) => sum + amount, 0n);
    return {subTotal:formatDecimal(subtotal), tax:formatDecimal(t), discount:formatDecimal(d), shipping:formatDecimal(s), grandTotal:formatDecimal(subtotal+t-d+s)};
};
