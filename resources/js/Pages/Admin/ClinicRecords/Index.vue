<template>
    <AppLayout title="Bảng ghi phòng khám">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-xl font-bold text-gray-800">Bảng ghi phòng khám</h1>
                <span class="text-sm text-gray-500">{{ records.total }} bản ghi</span>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap gap-3">
                    <input
                        v-model="form.search"
                        type="search"
                        placeholder="Tìm tên KH, mã KH, dịch vụ, bác sĩ, SĐT..."
                        class="flex-1 min-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @keyup.enter="applyFilters"
                    />
                    <select
                        v-model="form.record_type"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters"
                    >
                        <option value="">Tất cả loại</option>
                        <option v-for="t in record_types" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <input
                        v-model="form.date_from"
                        type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters"
                    />
                    <input
                        v-model="form.date_to"
                        type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters"
                    />
                    <select
                        v-model="form.per_page"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters"
                    >
                        <option value="20">20 / trang</option>
                        <option value="50">50 / trang</option>
                        <option value="100">100 / trang</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors"
                    >Lọc</button>
                    <button
                        v-if="hasFilters"
                        @click="clearFilters"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors"
                    >Xóa lọc</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                                <th class="px-3 py-3 text-left w-12">#</th>
                                <th class="px-3 py-3 text-left">Ngày</th>
                                <th class="px-3 py-3 text-left">Mã KH</th>
                                <th class="px-3 py-3 text-left">Tên khách hàng</th>
                                <th class="px-3 py-3 text-left">Loại</th>
                                <th class="px-3 py-3 text-left">Dịch vụ</th>
                                <th class="px-3 py-3 text-right">Đơn giá</th>
                                <th class="px-3 py-3 text-right">SL</th>
                                <th class="px-3 py-3 text-right">Khuyến mại</th>
                                <th class="px-3 py-3 text-right">Thành tiền</th>
                                <th class="px-3 py-3 text-right">Tổng thu</th>
                                <th class="px-3 py-3 text-right">Còn nợ</th>
                                <th class="px-3 py-3 text-right hidden lg:table-cell">Thu kỳ này</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Nguồn quỹ</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Bước tiến trình</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Bác sĩ</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Tư vấn</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Trợ thủ</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">SĐT</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="records.data.length === 0">
                                <td colspan="20" class="px-4 py-12 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                            <tr
                                v-for="r in records.data"
                                :key="r.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-3 py-2.5 text-gray-400 text-xs">{{ r.id }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ r.record_date }}</td>
                                <td class="px-3 py-2.5 font-mono text-xs text-primary-700">{{ r.patient_code }}</td>
                                <td class="px-3 py-2.5 font-medium text-gray-800">{{ r.patient_name }}</td>
                                <td class="px-3 py-2.5">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        r.record_type === 'Thanh toán'
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-blue-100 text-blue-700'
                                    ]">{{ r.record_type }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 max-w-[180px] truncate" :title="r.service_name">{{ r.service_name }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ fmt(r.unit_price) }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ r.quantity }}</td>
                                <td class="px-3 py-2.5 text-right text-orange-600">{{ fmt(r.discount) }}</td>
                                <td class="px-3 py-2.5 text-right font-medium text-gray-800">{{ fmt(r.amount) }}</td>
                                <td class="px-3 py-2.5 text-right text-green-700">{{ fmt(r.total_collected) }}</td>
                                <td class="px-3 py-2.5 text-right text-red-600">{{ fmt(r.remaining_debt) }}</td>
                                <td class="px-3 py-2.5 text-right hidden lg:table-cell text-green-600">{{ fmt(r.collected_this_period) }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-600 text-xs">{{ r.fund_name }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-600 text-xs max-w-[120px] truncate" :title="r.treatment_step">{{ r.treatment_step }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-700">{{ r.doctor_name }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.consultant_name }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.assistant_name }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.phone }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell">
                                    <span v-if="r.status" class="text-xs text-gray-500">{{ r.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="records.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500">
                        Trang {{ records.current_page }} / {{ records.last_page }} — {{ records.total }} bản ghi
                    </span>
                    <div class="flex gap-1">
                        <button
                            v-for="link in records.links"
                            :key="link.label"
                            :disabled="!link.url || link.active"
                            @click="goToPage(link.url)"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1 rounded text-xs border transition-colors',
                                link.active
                                    ? 'bg-primary-600 text-white border-primary-600'
                                    : link.url
                                        ? 'border-gray-300 text-gray-600 hover:bg-gray-100'
                                        : 'border-gray-200 text-gray-300 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    records: Object,
    filters: Object,
    record_types: Array,
});

const form = reactive({
    search: props.filters?.search ?? '',
    record_type: props.filters?.record_type ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    per_page: props.filters?.per_page ?? '50',
});

const hasFilters = computed(() =>
    form.search || form.record_type || form.date_from || form.date_to
);

function applyFilters() {
    router.get(route('admin.clinic-records.index'), {
        search: form.search || undefined,
        record_type: form.record_type || undefined,
        date_from: form.date_from || undefined,
        date_to: form.date_to || undefined,
        per_page: form.per_page !== '50' ? form.per_page : undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    form.search = '';
    form.record_type = '';
    form.date_from = '';
    form.date_to = '';
    applyFilters();
}

function goToPage(url) {
    if (url) router.visit(url, { preserveState: true });
}

function fmt(val) {
    if (!val) return '—';
    return Number(val).toLocaleString('vi-VN');
}
</script>