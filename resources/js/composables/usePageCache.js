import { router } from '@inertiajs/vue3';

// pathname → Inertia page state (serialisable snapshot)
const cache = new Map();

// Populate on every successful Inertia navigation
router.on('navigate', (event) => {
    const page = event.detail.page;
    const path = new URL(page.url, 'http://x').pathname;
    cache.set(path, JSON.parse(JSON.stringify(page)));
});

/**
 * Restore a previously-visited page from cache using the browser's
 * popstate mechanism — Inertia handles popstate without a server request,
 * exactly like the browser's Back/Forward buttons.
 *
 * Returns true if the page was restored from cache (no server request made).
 * Returns false if the page is not yet cached (caller should fall back to router.visit).
 */
export function restorePage(url) {
    const path = new URL(url, 'http://x').pathname;
    const cached = cache.get(path);
    if (!cached) return false;

    // Align the stored url with the tab url so Inertia doesn't see a mismatch
    const state = { ...cached, url };
    window.history.pushState(state, '', url);
    window.dispatchEvent(new PopStateEvent('popstate', { state }));
    return true;
}
