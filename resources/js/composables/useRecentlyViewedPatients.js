const STORAGE_KEY = 'patients_recently_viewed';
const MAX_ENTRIES  = 200;

function readMap() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}');
    } catch {
        return {};
    }
}

/** Record that a patient's profile was just viewed, for sorting the list by recency. */
export function recordPatientView(patientId) {
    let map = readMap();
    map[patientId] = Date.now();

    const entries = Object.entries(map);
    if (entries.length > MAX_ENTRIES) {
        entries.sort((a, b) => b[1] - a[1]);
        map = Object.fromEntries(entries.slice(0, MAX_ENTRIES));
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
}

/** Map of patientId (string) -> last-viewed timestamp (ms), for merging into list sort order. */
export function getRecentlyViewedMap() {
    return readMap();
}
