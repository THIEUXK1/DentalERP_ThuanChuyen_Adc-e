import { ref, computed, watch, onMounted } from 'vue';

const AVATAR_COLORS = [
    'bg-indigo-500', 'bg-violet-500', 'bg-emerald-500', 'bg-blue-500',
    'bg-rose-500', 'bg-amber-500', 'bg-teal-500', 'bg-pink-500',
];

const SOURCE_CLASSES = {
    facebook: 'bg-blue-100 text-blue-700',
    zalo:     'bg-teal-100 text-teal-700',
    google:   'bg-red-100 text-red-700',
    referral: 'bg-purple-100 text-purple-700',
    walk_in:  'bg-gray-100 text-gray-700',
    other:    'bg-orange-100 text-orange-700',
};

export const PER_PAGE_OPTIONS = [
    { value: 10,    label: '10 / trang' },
    { value: 20,    label: '20 / trang' },
    { value: 50,    label: '50 / trang' },
    { value: 100,   label: '100 / trang' },
    { value: 120,   label: '120 / trang' },
    { value: 'all', label: 'Tất cả' },
];

const DEFAULT_PER_PAGE = 120;
const STORAGE_KEY = 'patients_filter';

export function avatarColor(name) {
    return AVATAR_COLORS[(name.charCodeAt(0) ?? 0) % AVATAR_COLORS.length];
}

export function sourceClass(src) {
    return SOURCE_CLASSES[src] ?? 'bg-gray-100 text-gray-600';
}

/**
 * Loads the patient list from the JSON API (not Inertia props), then does
 * client-side search/filter/sort/pagination, with state restored from the
 * URL or sessionStorage and kept in sync back to the URL.
 */
export function usePatientFilters() {
    const patients  = ref([]);
    const loading   = ref(true);
    const loadError = ref(false);

    async function loadData() {
        loading.value   = true;
        loadError.value = false;
        try {
            const res = await fetch(route('patients.data'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            patients.value = await res.json();
        } catch {
            loadError.value = true;
        } finally {
            loading.value = false;
        }
    }

    onMounted(loadData);

    const _q  = new URLSearchParams(window.location.search);
    const _ss = (() => { try { return JSON.parse(sessionStorage.getItem(STORAGE_KEY) ?? '{}'); } catch { return {}; } })();

    const search      = ref(_q.has('search')    ? _q.get('search')                                              : (_ss.search   ?? ''));
    const branchId    = ref(_q.has('branch_id') ? Number(_q.get('branch_id'))                                   : (_ss.branchId ?? ''));
    const source      = ref(_q.has('source')    ? _q.get('source')                                              : (_ss.source   ?? ''));
    const perPage     = ref(_q.has('per_page')  ? (_q.get('per_page') === 'all' ? 'all' : Number(_q.get('per_page'))) : (_ss.perPage ?? DEFAULT_PER_PAGE));
    const currentPage = ref(Number(_q.get('page') ?? 1));
    const viewMode    = ref('table');

    const totalCount = computed(() => patients.value.length);

    const filteredPatients = computed(() => {
        const q = search.value.trim().toLowerCase();
        const list = patients.value.filter(p => {
            if (q && !p.full_name.toLowerCase().includes(q)
                  && !(p.phone ?? '').toLowerCase().includes(q)
                  && !(p.code  ?? '').toLowerCase().includes(q)) return false;
            if (branchId.value !== '' && p.branch_id !== branchId.value) return false;
            if (source.value && p.source !== source.value) return false;
            return true;
        });

        return [...list].sort((a, b) => {
            if (a.has_registration !== b.has_registration) return a.has_registration ? -1 : 1;
            return b.id - a.id;
        });
    });

    const totalPages = computed(() =>
        perPage.value === 'all' ? 1 : Math.max(1, Math.ceil(filteredPatients.value.length / Number(perPage.value)))
    );

    // Reset to page 1 when filter/per_page changes
    watch([search, branchId, source, perPage], () => { currentPage.value = 1; });

    // Clamp page if it goes out of range
    watch(totalPages, (n) => { if (currentPage.value > n) currentPage.value = n; });

    const fromRecord = computed(() => {
        if (!filteredPatients.value.length) return 0;
        if (perPage.value === 'all') return 1;
        return (currentPage.value - 1) * Number(perPage.value) + 1;
    });
    const toRecord = computed(() => {
        if (perPage.value === 'all') return filteredPatients.value.length;
        return Math.min(currentPage.value * Number(perPage.value), filteredPatients.value.length);
    });
    const paginatedPatients = computed(() => {
        if (perPage.value === 'all') return filteredPatients.value;
        const size  = Number(perPage.value);
        const start = (currentPage.value - 1) * size;
        return filteredPatients.value.slice(start, start + size);
    });

    // Page number list with ellipsis
    const pageNumbers = computed(() => {
        const total = totalPages.value;
        const cur   = currentPage.value;
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages = [1];
        if (cur > 3) pages.push('...');
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
        if (cur < total - 2) pages.push('...');
        pages.push(total);
        return pages;
    });

    // Sync URL + sessionStorage without triggering a page reload
    watch([search, branchId, source, perPage, currentPage], () => {
        const p = new URLSearchParams();
        if (search.value)                          p.set('search',    search.value);
        if (branchId.value !== '')                 p.set('branch_id', String(branchId.value));
        if (source.value)                          p.set('source',    source.value);
        if (String(perPage.value) !== String(DEFAULT_PER_PAGE)) p.set('per_page', String(perPage.value));
        if (currentPage.value > 1)                 p.set('page',      String(currentPage.value));
        const qs = p.toString();
        history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname);
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
            search: search.value, branchId: branchId.value, source: source.value,
            perPage: perPage.value,
        }));
    });

    const hasActiveFilters = computed(() =>
        !!(search.value || branchId.value !== '' || source.value || String(perPage.value) !== String(DEFAULT_PER_PAGE))
    );

    function clearFilters() {
        search.value = ''; branchId.value = ''; source.value = '';
        perPage.value = DEFAULT_PER_PAGE;
        sessionStorage.removeItem(STORAGE_KEY);
    }

    return {
        loading, loadError, loadData, totalCount,
        search, branchId, source, perPage, currentPage, viewMode,
        filteredPatients, totalPages, fromRecord, toRecord, paginatedPatients, pageNumbers,
        hasActiveFilters, clearFilters,
    };
}
