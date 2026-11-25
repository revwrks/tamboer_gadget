/**
 * Format numeric value as Indonesian Rupiah without decimals.
 */
export function formatCurrency(value) {
    return (parseFloat(value) || 0).toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
}

/**
 * Format date string into localized Indonesian short date (e.g. 25 Nov 2026).
 */
export function formatDate(value) {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "-";
    return date.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

/**
 * Download an ExcelJS ArrayBuffer/Uint8Array natively in the browser without any server proxy.
 */
export function downloadExcelBuffer(buffer, filename) {
    const blob = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

/**
 * Extract readable error message from Axios / Laravel validation responses.
 */
export function extractApiError(error, fallback = "Terjadi kesalahan sistem") {
    const payload = error?.response?.data;
    if (!payload) return fallback;

    if (typeof payload.error === "string") {
        return payload.error === "Invalid credentials"
            ? "Username atau kata sandi salah."
            : payload.error;
    }

    if (payload.error && typeof payload.error === "object") {
        return Object.values(payload.error).flat().join(" ");
    }

    if (typeof payload.message === "string") {
        return payload.message;
    }

    return fallback;
}
