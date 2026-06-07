export function useCurrency() {
    function formatVnd(value) {
        return new Intl.NumberFormat('vi-VN').format(value ?? 0) + ' ₫';
    }

    function parseVnd(str) {
        return parseInt(String(str).replace(/\D/g, ''), 10) || 0;
    }

    return { formatVnd, parseVnd };
}
