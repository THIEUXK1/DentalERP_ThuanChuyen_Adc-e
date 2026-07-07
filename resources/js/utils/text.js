/** Lowercases and strips Vietnamese diacritics, so "thieu" matches "Thiều". */
export function normalizeSearch(str) {
    return (str ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase();
}

/** True if the string has any Vietnamese diacritics (accents, or đ/Đ). */
export function hasDiacritics(str) {
    return (str ?? '').toLowerCase() !== normalizeSearch(str);
}

/**
 * Search match with a precision rule: typing without accents ("thieu") matches
 * loosely regardless of the target's accents ("Thiều", "Thiệu", "Thieu"...).
 * Typing WITH accents ("Thiều") narrows down to an exact accent-sensitive match,
 * so it won't also surface "Thiệu".
 */
export function matchesQuery(target, query) {
    const q = (query ?? '').trim();
    if (!q) return true;
    const t = target ?? '';
    if (hasDiacritics(q)) {
        return t.toLowerCase().includes(q.toLowerCase());
    }
    return normalizeSearch(t).includes(normalizeSearch(q));
}
