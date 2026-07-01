<template>
    <AppLayout title="Khách hàng">
        <div class="space-y-5">

            <!-- ── Header ───────────────────────────────────────────────── -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Danh sách khách hàng</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Quản lý toàn bộ hồ sơ bệnh nhân</p>
                </div>
                <Link v-if="can('patients.create')" :href="route('patients.create')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Thêm khách hàng
                </Link>
            </div>

            <!-- ── Stats bar ───────────────────────────────────────────── -->
            <div class="bg-slate-800 rounded-xl px-5 py-3 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-xs">Tổng KH</span>
                    <span class="font-bold text-white text-lg">{{ all_patients.length }}</span>
                </div>
                <div class="h-4 w-px bg-slate-600"></div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-xs">Kết quả lọc</span>
                    <span class="font-bold text-indigo-300">{{ filteredPatients.length }}</span>
                </div>
                <div v-if="filteredPatients.length > 0" class="h-4 w-px bg-slate-600"></div>
                <div v-if="filteredPatients.length > 0" class="flex items-center gap-2">
                    <span class="text-slate-400 text-xs">Đang xem</span>
                    <span class="text-slate-300 text-xs">{{ fromRecord }}–{{ toRecord }}</span>
                </div>
                <div class="ml-auto flex items-center gap-1">
                    <button @click="viewMode = 'table'"
                        :class="['p-1.5 rounded-lg transition-colors', viewMode === 'table' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white']">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </button>
                    <button @click="viewMode = 'card'"
                        :class="['p-1.5 rounded-lg transition-colors', viewMode === 'card' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white']">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ── Filters ───────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tìm kiếm</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" placeholder="Tên, SĐT, mã KH..."
                            class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-60 focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Chi nhánh</label>
                    <select v-model="branchId"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option :value="''">Tất cả</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Nguồn</label>
                    <select v-model="source"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả nguồn</option>
                        <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Sắp xếp</label>
                    <select v-model="sortBy"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="next_appointment">🗓 Lịch hẹn gần nhất</option>
                        <option value="created_at">📅 Ngày tạo mới nhất</option>
                        <option value="name">🔤 Tên A→Z</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Hiển thị</label>
                    <select v-model="perPage"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="px-3 py-2 text-indigo-600 text-sm rounded-lg border border-indigo-200 hover:bg-indigo-50 self-end font-medium flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Xóa bộ lọc
                </button>
            </div>

            <!-- ── TABLE VIEW ──────────────────────────────────────────── -->
            <div v-if="viewMode === 'table'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="paginatedPatients.length === 0" class="flex flex-col items-center py-14 text-gray-400">
                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm font-medium">Không tìm thấy khách hàng</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Khách hàng</th>
                                <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">SĐT</th>
                                <th class="px-4 py-3 text-left font-medium hidden md:table-cell">Nguồn</th>
                                <th class="px-4 py-3 text-left font-medium hidden lg:table-cell">Chi nhánh</th>
                                <th class="px-4 py-3 text-left font-medium hidden lg:table-cell">Ngày tạo</th>
                                <th class="px-4 py-3 text-left font-medium hidden xl:table-cell">
                                    <span class="flex items-center gap-1">🗓 Lịch hẹn gần nhất</span>
                                </th>
                                <th class="px-4 py-3 text-right font-medium">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="p in paginatedPatients" :key="p.id" class="hover:bg-blue-50/20 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0', avatarColor(p.full_name)]">
                                            {{ p.full_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <Link :href="route('patients.show', p.id)" class="font-semibold text-gray-900 hover:text-indigo-600">
                                                {{ p.full_name }}
                                            </Link>
                                            <p class="text-xs text-gray-400 font-mono">{{ p.code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ p.phone }}</td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <span v-if="p.source" :class="['text-xs px-2 py-0.5 rounded-full font-medium', sourceClass(p.source)]">
                                        {{ p.source }}
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 hidden lg:table-cell">{{ p.branch ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-400 text-xs hidden lg:table-cell">{{ p.created_at }}</td>
                                <td class="px-4 py-3 hidden xl:table-cell">
                                    <span v-if="p.next_appointment_display"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 whitespace-nowrap">
                                        🗓 {{ p.next_appointment_display }}
                                    </span>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center gap-1.5 justify-end flex-wrap">
                                        <Link :href="route('patients.register-appointment', p.id)"
                                            class="px-2.5 py-1 text-xs bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 font-medium whitespace-nowrap">
                                            🗓 Đăng ký khám
                                        </Link>
                                        <Link :href="route('patients.show', p.id)"
                                            class="px-2.5 py-1 text-xs bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 font-medium">
                                            Xem
                                        </Link>
                                        <Link v-if="can('patients.edit')" :href="route('patients.edit', p.id)"
                                            class="px-2.5 py-1 text-xs bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100">
                                            Sửa
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── CARD VIEW ───────────────────────────────────────────── -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-if="paginatedPatients.length === 0" class="col-span-full text-center py-14 text-gray-400">
                    Không tìm thấy khách hàng
                </div>
                <Link v-for="p in paginatedPatients" :key="p.id"
                    :href="route('patients.show', p.id)"
                    class="bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-200 hover:shadow-md transition-all group">
                    <div class="flex items-start gap-3 mb-3">
                        <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-base font-bold text-white flex-shrink-0 shadow-sm', avatarColor(p.full_name)]">
                            {{ p.full_name.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 group-hover:text-indigo-700 truncate">{{ p.full_name }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ p.code }}</p>
                        </div>
                    </div>
                    <div class="space-y-1 text-xs text-gray-500">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ p.phone }}
                        </div>
                        <div v-if="p.branch" class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            {{ p.branch }}
                        </div>
                    </div>
                    <div v-if="p.next_appointment_display"
                        class="mt-2 flex items-center gap-1.5 px-2 py-1 bg-indigo-50 rounded-lg">
                        <svg class="w-3 h-3 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs font-medium text-indigo-700">{{ p.next_appointment_display }}</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span v-if="p.source" :class="['text-xs px-2 py-0.5 rounded-full font-medium', sourceClass(p.source)]">
                            {{ p.source }}
                        </span>
                        <span v-else class="text-xs text-gray-300">—</span>
                        <span class="text-xs text-gray-400">{{ p.created_at }}</span>
                    </div>
                </Link>
            </div>

            <!-- ── Pagination ──────────────────────────────────────────── -->
            <div v-if="totalPages > 1 || filteredPatients.length > 0" class="flex items-center justify-between flex-wrap gap-3">
                <p class="text-sm text-gray-500">
                    Hiển thị
                    <span class="font-medium text-gray-700">{{ fromRecord }}–{{ toRecord }}</span>
                    / <span class="font-medium text-gray-700">{{ filteredPatients.length }}</span> bản ghi
                </p>

                <div v-if="totalPages > 1" class="flex items-center gap-1">
                    <button @click="currentPage--" :disabled="currentPage === 1"
                        class="px-2.5 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        ‹
                    </button>
                    <template v-for="pg in pageNumbers" :key="pg">
                        <span v-if="pg === '...'" class="px-1 text-gray-400 text-sm">…</span>
                        <button v-else @click="currentPage = pg"
                            :class="['px-2.5 py-1.5 text-sm rounded-lg border transition-colors',
                                pg === currentPage
                                    ? 'bg-indigo-600 border-indigo-600 text-white font-medium'
                                    : 'border-gray-200 text-gray-600 hover:bg-gray-50']">
                            {{ pg }}
                        </button>
                    </template>
                    <button @click="currentPage++" :disabled="currentPage === totalPages"
                        class="px-2.5 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        ›
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ all_patients: Array, branches: Array, sources: Array });

// ── Restore state from URL → sessionStorage → default ───────────
const _q  = new URLSearchParams(window.location.search);
const _ss = (() => { try { return JSON.parse(sessionStorage.getItem('patients_filter') ?? '{}'); } catch { return {}; } })();

const search      = ref(_q.has('search')    ? _q.get('search')                                              : (_ss.search   ?? ''));
const branchId    = ref(_q.has('branch_id') ? Number(_q.get('branch_id'))                                   : (_ss.branchId ?? ''));
const source      = ref(_q.has('source')    ? _q.get('source')                                              : (_ss.source   ?? ''));
const sortBy      = ref(_q.has('sort')      ? _q.get('sort')                                                : (_ss.sortBy   ?? 'created_at'));
const perPage     = ref(_q.has('per_page')  ? (_q.get('per_page') === 'all' ? 'all' : Number(_q.get('per_page'))) : (_ss.perPage ?? 20));
const currentPage = ref(Number(_q.get('page') ?? 1));
const viewMode    = ref('table');

const perPageOptions = [
    { value: 10,    label: '10 / trang' },
    { value: 20,    label: '20 / trang' },
    { value: 50,    label: '50 / trang' },
    { value: 100,   label: '100 / trang' },
    { value: 'all', label: 'Tất cả' },
];

// ── Filtering + Sorting ───────────────────────────────────────────
const filteredPatients = computed(() => {
    const q = search.value.trim().toLowerCase();
    const list = props.all_patients.filter(p => {
        if (q && !p.full_name.toLowerCase().includes(q)
              && !(p.phone ?? '').toLowerCase().includes(q)
              && !(p.code  ?? '').toLowerCase().includes(q)) return false;
        if (branchId.value !== '' && p.branch_id !== branchId.value) return false;
        if (source.value && p.source !== source.value) return false;
        return true;
    });

    return [...list].sort((a, b) => {
        if (sortBy.value === 'next_appointment') {
            // Có lịch hẹn lên trước, null xuống cuối; cùng có thì gần nhất lên đầu
            if (a.next_appointment_at && b.next_appointment_at)
                return a.next_appointment_at < b.next_appointment_at ? -1 : 1;
            if (a.next_appointment_at) return -1;
            if (b.next_appointment_at) return 1;
            return b.id - a.id;
        }
        if (sortBy.value === 'name') {
            return a.full_name.localeCompare(b.full_name, 'vi');
        }
        // created_at: mới nhất lên đầu
        return b.id - a.id;
    });
});

// ── Pagination ───────────────────────────────────────────────────
const totalPages = computed(() =>
    perPage.value === 'all' ? 1 : Math.max(1, Math.ceil(filteredPatients.value.length / Number(perPage.value)))
);

// Reset to page 1 when filter/per_page changes
watch([search, branchId, source, sortBy, perPage], () => { currentPage.value = 1; });

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

// ── Sync URL without reload ──────────────────────────────────────
watch([search, branchId, source, sortBy, perPage, currentPage], () => {
    const p = new URLSearchParams();
    if (search.value)                    p.set('search',    search.value);
    if (branchId.value !== '')           p.set('branch_id', String(branchId.value));
    if (source.value)                    p.set('source',    source.value);
    if (sortBy.value !== 'created_at')   p.set('sort',      sortBy.value);
    if (String(perPage.value) !== '20')  p.set('per_page',  String(perPage.value));
    if (currentPage.value > 1)           p.set('page',      String(currentPage.value));
    const qs = p.toString();
    history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname);
    sessionStorage.setItem('patients_filter', JSON.stringify({
        search: search.value, branchId: branchId.value, source: source.value,
        sortBy: sortBy.value, perPage: perPage.value,
    }));
});

// ── Helpers ───────────────────────────────────────────────────────
const hasActiveFilters = computed(() =>
    !!(search.value || branchId.value !== '' || source.value ||
       sortBy.value !== 'created_at' || String(perPage.value) !== '20')
);

function clearFilters() {
    search.value = ''; branchId.value = ''; source.value = '';
    sortBy.value = 'created_at'; perPage.value = 20;
    sessionStorage.removeItem('patients_filter');
}

const AVATAR_COLORS = [
    'bg-indigo-500', 'bg-violet-500', 'bg-emerald-500', 'bg-blue-500',
    'bg-rose-500', 'bg-amber-500', 'bg-teal-500', 'bg-pink-500',
];
function avatarColor(name) {
    return AVATAR_COLORS[(name.charCodeAt(0) ?? 0) % AVATAR_COLORS.length];
}

function sourceClass(src) {
    return {
        facebook: 'bg-blue-100 text-blue-700',
        zalo:     'bg-teal-100 text-teal-700',
        google:   'bg-red-100 text-red-700',
        referral: 'bg-purple-100 text-purple-700',
        walk_in:  'bg-gray-100 text-gray-700',
        other:    'bg-orange-100 text-orange-700',
    }[src] ?? 'bg-gray-100 text-gray-600';
}
</script>
