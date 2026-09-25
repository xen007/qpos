import translate from "./translate";

export default function getErrorMessage(error) {
    const data = error?.response?.data;

    if (data?.errors && typeof data.errors === "object") {
        const validationMessages = Object.values(data.errors)
            .flat()
            .filter(Boolean);

        if (validationMessages.length > 0) {
            return validationMessages.join("\n");
        }
    }

    return (
        data?.message ||
        error?.message ||
        translate("Something went wrong. Please try again.")
    );
}
