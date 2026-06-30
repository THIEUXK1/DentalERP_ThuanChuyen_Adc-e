const positions = {};

export function saveScroll(url) {
    const key = new URL(url, 'http://x').pathname;
    positions[key] = window.scrollY;
}

export function restoreScroll(url) {
    const key = new URL(url, 'http://x').pathname;
    window.scrollTo({ top: positions[key] ?? 0, behavior: 'instant' });
}
