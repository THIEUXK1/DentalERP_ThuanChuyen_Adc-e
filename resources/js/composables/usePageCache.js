import { router } from '@inertiajs/vue3';

// pathname → encrypted history state ({ page: encryptedData }) as pushed by Inertia v2
const cache = new Map();

// Inertia v2 encrypts the page before calling window.history.pushState.
// The 'navigate' event fires AFTER the encrypted state is in window.history.state,
// so we capture window.history.state directly (not the decrypted page object).
router.on('navigate', (event) => {
    const path = new URL(event.detail.page.url, 'http://x').pathname;
    const state = window.history.state;
    if (state?.page) {
        cache.set(path, state);
    }
});

/**
 * Restore a previously-visited page from cache via popstate.
 * Because we store the original encrypted Inertia v2 history state,
 * Inertia's popstate handler can decrypt it and restore the page without
 * making a server request.
 *
 * Returns true if a cached state was found (popstate dispatched).
 * Returns false if not cached (caller should fall back to router.visit).
 */
export function restorePage(url) {
    const path = new URL(url, 'http://x').pathname;
    const cachedState = cache.get(path);
    if (!cachedState) return false;

    window.history.pushState(cachedState, '', url);
    window.dispatchEvent(new PopStateEvent('popstate', { state: cachedState }));
    return true;
}
