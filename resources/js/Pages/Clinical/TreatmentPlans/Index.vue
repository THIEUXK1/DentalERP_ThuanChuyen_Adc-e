<template>
    <AppLayout title="Kế hoạch điều trị">
        <div class="space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    Kế hoạch điều trị
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ filteredPlans.length }})</span>
                </h2>
                <div class="flex items-center gap-2">
                    <!-- View toggle -->
                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button @click="viewMode = 'list'"
                            :class="['p-2 transition-colors', viewMode === 'list' ? 'bg-primary-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600']"
                            title="Dạng danh sách">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </button>
                        <button @click="viewMode = 'grid'"
                            :class="['p-2 transition-colors', viewMode === 'grid' ? 'bg-primary-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600']"
                            title="Dạng hộp">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                            </svg>
                        </button>
                    </div>
                    <Link v-if="can('treatment_plans.create')" :href="route('clinical.treatment-plans.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                        + Tạo kế hoạch
                    </Link>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center gap-3">

                <!-- Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Tìm tên BN, mã, bác sĩ, chi nhánh, ghi chú..."
                        class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    <button v-if="search" @click="search = ''"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <select v-model="filterBranch"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>

                <select v-model="filterStatus"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>

                <select v-model="perPage"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option :value="10">10/trang</option>
                    <option :value="20">20/trang</option>
                    <option :value="50">50/trang</option>
                    <option :value="100">100/trang</option>
                    <option value="all">Tất cả</option>
                </select>

                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 flex-shrink-0 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Xóa lọc
                </button>
            </div>

            <!-- Results info -->
            <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                <span>
                    Hiển thị <strong class="text-gray-700">{{ paginatedPlans.length }}</strong>
                    / {{ filteredPlans.length }} kế hoạch
                    <span v-if="filteredPlans.length < all_plans.length" class="text-gray-400">
                        (tổng {{ all_plans.length }})
                    </span>
                </span>
                <span v-if="totalPages > 1">Trang {{ currentPage }}/{{ totalPages }}</span>
            </div>

            <!-- Empty state -->
            <div v-if="filteredPlans.length === 0"
                class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                {{ hasActiveFilters ? 'Không tìm thấy kế hoạch phù hợp' : 'Chưa có kế hoạch điều trị nào' }}
            </div>

            <!-- LIST VIEW -->
            <div v-else-if="viewMode === 'list'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Mã</th>
                            <th class="px-4 py-3 text-left font-medium">Khách hàng</th>
                            <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">Bác sĩ</th>
                            <th class="px-4 py-3 text-left font-medium hidden lg:table-cell">Chi nhánh</th>
                            <th class="px-4 py-3 text-right font-medium">Giá trị KH</th>
                            <th class="px-4 py-3 text-right font-medium hidden xl:table-cell">Lịch thanh toán</th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-left font-medium hidden md:table-cell">Ngày tạo</th>
                            <th class="px-4 py-3 text-right font-medium w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in paginatedPlans" :key="p.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ p.code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link :href="route('clinical.treatment-plans.show', p.id)" class="hover:text-primary-600">
                                    {{ p.patient }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ p.doctor }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">{{ p.branch }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="font-medium text-gray-800">{{ formatVnd(p.net_total) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right hidden xl:table-cell whitespace-nowrap">
                                <template v-if="p.payment_schedule_count > 0">
                                    <span class="font-semibold text-emerald-700">{{ formatVnd(p.payment_schedule_total) }}</span>
                                    <span class="ml-1 text-xs text-gray-400">({{ p.payment_schedule_count }} đợt)</span>
                                </template>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :color="p.status_color">{{ p.status_label }}</StatusBadge>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden md:table-cell whitespace-nowrap">{{ p.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('clinical.treatment-plans.show', p.id)"
                                    class="text-primary-600 text-xs font-medium hover:underline">Xem →</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GRID VIEW -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <Link v-for="p in paginatedPlans" :key="p.id"
                    :href="route('clinical.treatment-plans.show', p.id)"
                    class="bg-white rounded-xl border border-gray-200 hover:border-primary-200 hover:shadow-md transition-all p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-mono text-xs text-gray-400">{{ p.code }}</span>
                        <StatusBadge :color="p.status_color" class="flex-shrink-0">{{ p.status_label }}</StatusBadge>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 truncate">{{ p.patient }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ p.doctor }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span class="truncate">{{ p.branch }}</span>
                    </div>
                    <p v-if="p.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 truncate">
                        📝 {{ p.notes }}
                    </p>
                    <div class="mt-auto pt-2 border-t border-gray-100 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Giá trị KH</span>
                            <span class="text-sm font-semibold text-primary-700">{{ formatVnd(p.net_total) }}</span>
                        </div>
                        <div v-if="p.payment_schedule_count > 0" class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Lịch TT ({{ p.payment_schedule_count }} đợt)</span>
                            <span class="text-sm font-semibold text-emerald-600">{{ formatVnd(p.payment_schedule_total) }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-400">{{ p.created_at }}</span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 py-2">
                <button @click="currentPage = 1" :disabled="currentPage === 1"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors">«</button>
                <button @click="currentPage--" :disabled="currentPage === 1"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors">‹</button>
                <template v-for="n in pageNumbers" :key="n">
                    <span v-if="n === '...'" class="px-2 py-1.5 text-xs text-gray-400">…</span>
                    <button v-else @click="currentPage = n"
                        :class="['px-2.5 py-1.5 text-xs border rounded-lg transition-colors',
                            n === currentPage ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-200 hover:bg-gray-50']">
                        {{ n }}
                    </button>
                </template>
                <button @click="currentPage++" :disabled="currentPage === totalPages"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors">›</button>
                <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors">»</button>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();

const props = defineProps({ all_plans: Array, statuses: Array, branches: Array });

const search       = ref('');
const filterBranch = ref('');
const filterStatus = ref('');
const perPage      = ref(localStorage.getItem('tp_per_page') === 'all' ? 'all' : (Number(localStorage.getItem('tp_per_page')) || 20));
const currentPage  = ref(1);
const viewMode     = ref(localStorage.getItem('tp_view_mode') || 'list');

watch(viewMode,  v => localStorage.setItem('tp_view_mode', v));
watch(perPage,   v => localStorage.setItem('tp_per_page', String(v)));
watch([search, filterBranch, filterStatus, perPage], () => { currentPage.value = 1; });

const filteredPlans = computed(() => {
    let list = props.all_plans;
    if (filterBranch.value) {
        list = list.filter(p => p.branch_id == filterBranch.value);
    }
    if (filterStatus.value) {
        list = list.filter(p => p.status === filterStatus.value);
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(p =>
            (p.patient     || '').toLowerCase().includes(q) ||
            (p.code        || '').toLowerCase().includes(q) ||
            (p.doctor      || '').toLowerCase().includes(q) ||
            (p.branch      || '').toLowerCase().includes(q) ||
            (p.status_label|| '').toLowerCase().includes(q) ||
            (p.notes       || '').toLowerCase().includes(q) ||
            (p.created_at  || '').includes(q)
        );
    }
    return list;
});

const paginatedPlans = computed(() => {
    if (perPage.value === 'all') return filteredPlans.value;
    const size  = Number(perPage.value);
    const start = (currentPage.value - 1) * size;
    return filteredPlans.value.slice(start, start + size);
});

const totalPages = computed(() =>
    perPage.value === 'all' ? 1 : Math.max(1, Math.ceil(filteredPlans.value.length / Number(perPage.value)))
);

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

const hasActiveFilters = computed(() => !!(search.value || filterBranch.value || filterStatus.value));

function clearFilters() {
    search.value       = '';
    filterBranch.value = '';
    filterStatus.value = '';
}
</script>
