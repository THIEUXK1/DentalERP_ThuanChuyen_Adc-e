<template>
    <AppLayout title="Dữ liệu hệ thống">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Dữ liệu hệ thống</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Nhật ký giao dịch hợp nhất — dịch vụ đã thực hiện và thanh toán đã ghi nhận trong hệ thống</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 whitespace-nowrap">{{ records.total.toLocaleString('vi-VN') }} bản ghi</span>
                    <button @click="openExport"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-green-300 text-green-700 hover:bg-green-50 transition-colors font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Xuất Excel
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap gap-3">
                    <input v-model="form.search" type="search"
                        placeholder="Tìm tên KH, mã KH, dịch vụ, SĐT, mã chứng từ..."
                        class="flex-1 min-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @keyup.enter="applyFilters" />
                    <select v-model="form.record_type"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="">Tất cả loại</option>
                        <option value="service">Thủ thuật</option>
                        <option value="payment">Thanh toán</option>
                    </select>
                    <select v-if="branches.length > 0" v-model="form.branch_id"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <select v-model="form.year"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="">Tất cả năm</option>
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <input v-model="form.date_from" type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters" />
                    <input v-model="form.date_to" type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters" />
                    <select v-model="form.per_page"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="20">20 / trang</option>
                        <option value="50">50 / trang</option>
                        <option value="100">100 / trang</option>
                        <option value="200">200 / trang</option>
                        <option value="500">500 / trang</option>
                        <option value="1000">1000 / trang</option>
                    </select>
                    <button @click="applyFilters"
                        class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors">Lọc</button>
                    <button v-if="hasFilters" @click="clearFilters"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">Xóa lọc</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                                <th class="px-3 py-3 text-left">Ngày</th>
                                <th class="px-3 py-3 text-left">Mã KH</th>
                                <th class="px-3 py-3 text-left">Tên khách hàng</th>
                                <th class="px-3 py-3 text-left">Loại</th>
                                <th class="px-3 py-3 text-left">Diễn giải</th>
                                <th class="px-3 py-3 text-right">Đơn giá</th>
                                <th class="px-3 py-3 text-right">SL</th>
                                <th class="px-3 py-3 text-right">Khuyến mại</th>
                                <th class="px-3 py-3 text-right">Thành tiền</th>
                                <th class="px-3 py-3 text-left hidden lg:table-cell">Chứng từ</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Bác sĩ</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Tư vấn</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Trợ thủ</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Chi nhánh</th>
                                <th class="px-3 py-3 text-left">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="records.data.length === 0">
                                <td colspan="15" class="px-4 py-12 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                            <tr v-for="r in records.data" :key="r.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ formatDate(r.record_date) }}</td>
                                <td class="px-3 py-2.5">
                                    <Link v-if="r.patient_id" :href="route('patients.show', r.patient_id)"
                                        class="font-mono text-xs text-primary-700 hover:underline">{{ r.patient_code }}</Link>
                                </td>
                                <td class="px-3 py-2.5 font-medium text-gray-800">{{ r.patient_name }}</td>
                                <td class="px-3 py-2.5">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        r.record_type === 'payment' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700']">
                                        {{ r.record_type_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 max-w-[200px] truncate" :title="r.description">{{ r.description }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ fmt(r.unit_price) }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ r.quantity ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-orange-600">{{ fmt(r.discount) }}</td>
                                <td class="px-3 py-2.5 text-right font-medium"
                                    :class="r.amount < 0 ? 'text-red-600' : 'text-gray-800'">{{ fmt(r.amount) }}</td>
                                <td class="px-3 py-2.5 hidden lg:table-cell">
                                    <Link :href="referenceUrl(r)" class="font-mono text-xs text-indigo-600 hover:underline">{{ r.reference_code }}</Link>
                                </td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-700">{{ r.doctor_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.consultant_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.assistant_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-600 text-xs">{{ r.branch_name }}</td>
                                <td class="px-3 py-2.5">
                                    <span class="text-xs text-gray-500">{{ r.status_label }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="records.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500">
                        Trang {{ records.current_page }} / {{ records.last_page }} — {{ records.total.toLocaleString('vi-VN') }} bản ghi
                    </span>
                    <div class="flex gap-1">
                        <button v-for="link in records.links" :key="link.label"
                            :disabled="!link.url || link.active"
                            @click="goToPage(link.url)"
                            v-html="link.label"
                            :class="['px-3 py-1 rounded text-xs border transition-colors',
                                link.active ? 'bg-primary-600 text-white border-primary-600'
                                    : link.url ? 'border-gray-300 text-gray-600 hover:bg-gray-100'
                                    : 'border-gray-200 text-gray-300 cursor-not-allowed']" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Export modal -->
        <Teleport to="body">
            <div v-if="showExport" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeExport" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Xuất Excel</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Chọn khoảng thời gian cần xuất dữ liệu</p>
                    </div>

                    <div v-if="years.length > 0">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Chọn nhanh theo năm</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="y in years" :key="y" :disabled="exporting" @click="exportYear(y)"
                                class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-600 hover:border-green-400 hover:text-green-700 hover:bg-green-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Cả năm {{ y }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-px bg-gray-200" />
                        <span class="text-xs text-gray-400">hoặc chọn khoảng ngày</span>
                        <div class="flex-1 h-px bg-gray-200" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Từ ngày</label>
                            <input v-model="exportForm.date_from" type="date" :disabled="exporting"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-400" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Đến ngày</label>
                            <input v-model="exportForm.date_to" type="date" :disabled="exporting"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-400" />
                        </div>
                    </div>

                    <!-- Export progress -->
                    <div v-if="exporting" class="space-y-1.5">
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>Đang xuất dữ liệu...</span>
                            <span class="font-semibold text-green-600">{{ exportProgress }}%</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full transition-all duration-300 ease-out"
                                :style="{ width: exportProgress + '%' }" />
                        </div>
                    </div>

                    <p v-if="exportError" class="text-sm text-red-600 flex gap-1.5 items-center">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ exportError }}
                    </p>
                    <p v-if="!exporting" class="text-xs text-gray-400">
                        Các bộ lọc đang áp dụng (loại, chi nhánh, tìm kiếm...) trên trang sẽ được giữ nguyên khi xuất.
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="closeExport" :disabled="exporting"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Hủy
                        </button>
                        <button @click="confirmExport" :disabled="exporting"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg v-if="exporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ exporting ? `${exportProgress}%` : 'Xuất Excel' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    records: Object,
    filters: Object,
    branches: Array,
    years: Array,
});

const form = reactive({
    search:      props.filters?.search      ?? '',
    record_type: props.filters?.record_type ?? '',
    branch_id:   props.filters?.branch_id   ?? '',
    year:        props.filters?.year        ?? '',
    date_from:   props.filters?.date_from   ?? '',
    date_to:     props.filters?.date_to     ?? '',
    per_page:    props.filters?.per_page    ?? '50',
});

const hasFilters = computed(() =>
    form.search || form.record_type || form.branch_id || form.year || form.date_from || form.date_to
);

function applyFilters() {
    router.get(route('system-records.index'), {
        search:      form.search      || undefined,
        record_type: form.record_type || undefined,
        branch_id:   form.branch_id   || undefined,
        year:        form.year        || undefined,
        date_from:   form.date_from   || undefined,
        date_to:     form.date_to     || undefined,
        per_page:    form.per_page !== '50' ? form.per_page : undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    form.search = ''; form.record_type = ''; form.branch_id = ''; form.year = ''; form.date_from = ''; form.date_to = '';
    applyFilters();
}

function goToPage(url) {
    if (url) router.visit(url, { preserveState: true });
}

function fmt(val) {
    if (!val) return '—';
    return Number(val).toLocaleString('vi-VN');
}

function formatDate(val) {
    if (!val) return '—';
    const [y, m, d] = val.split('-');
    return `${d}/${m}/${y}`;
}

function referenceUrl(r) {
    return r.reference_type === 'invoice'
        ? route('cashier.invoices.show', r.reference_id)
        : route('clinical.treatment-plans.show', r.reference_id);
}

// ── Export modal ──────────────────────────────────────────────
const showExport = ref(false);
const exporting = ref(false);
const exportProgress = ref(0);
const exportError = ref('');
const exportForm = reactive({ date_from: '', date_to: '' });
let exportProgressTimer = null;

function openExport() {
    exportError.value = '';
    exportForm.date_from = form.date_from || '';
    exportForm.date_to = form.date_to || '';
    showExport.value = true;
}

function closeExport() {
    if (exporting.value) return;
    showExport.value = false;
}

function exportYear(y) {
    if (exporting.value) return;
    exportForm.date_from = `${y}-01-01`;
    exportForm.date_to = `${y}-12-31`;
    confirmExport();
}

function exportFilenameBase() {
    const isFullYear = exportForm.date_from.endsWith('-01-01')
        && exportForm.date_to.endsWith('-12-31')
        && exportForm.date_from.slice(0, 4) === exportForm.date_to.slice(0, 4);

    return isFullYear
        ? `du-lieu-he-thong-nam-${exportForm.date_from.slice(0, 4)}`
        : `du-lieu-he-thong_${exportForm.date_from}_${exportForm.date_to}`;
}

async function confirmExport() {
    if (exporting.value) return;

    if (!exportForm.date_from || !exportForm.date_to) {
        exportError.value = 'Vui lòng chọn đầy đủ từ ngày và đến ngày.';
        return;
    }
    if (exportForm.date_from > exportForm.date_to) {
        exportError.value = 'Từ ngày phải trước hoặc bằng đến ngày.';
        return;
    }

    exportError.value = '';
    exporting.value = true;
    exportProgress.value = 0;

    // The server builds the whole file before sending anything back, so there's no real
    // byte-level signal during that phase — ramp toward 90% while waiting, same pattern
    // as the clinic-records import modal, then jump to 100% once the response arrives.
    exportProgressTimer = setInterval(() => {
        if (exportProgress.value < 90) exportProgress.value += Math.random() * 8;
    }, 300);

    try {
        const response = await axios.get(route('system-records.export', {
            search:      form.search      || undefined,
            record_type: form.record_type || undefined,
            branch_id:   form.branch_id   || undefined,
            year:        form.year        || undefined,
            date_from:   exportForm.date_from,
            date_to:     exportForm.date_to,
        }), {
            responseType: 'blob',
            onDownloadProgress(e) {
                if (e.total) exportProgress.value = Math.max(exportProgress.value, Math.round((e.loaded / e.total) * 100));
            },
        });

        clearInterval(exportProgressTimer);
        exportProgress.value = 100;

        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${exportFilenameBase()}.xlsx`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        setTimeout(() => {
            exporting.value = false;
            showExport.value = false;
        }, 400);
    } catch (err) {
        clearInterval(exportProgressTimer);
        exporting.value = false;
        exportProgress.value = 0;

        if (err.response?.data instanceof Blob) {
            const text = await err.response.data.text();
            try {
                const parsed = JSON.parse(text);
                exportError.value = parsed.message ?? Object.values(parsed.errors ?? {}).flat()[0] ?? 'Có lỗi xảy ra khi xuất dữ liệu.';
            } catch {
                exportError.value = 'Có lỗi xảy ra khi xuất dữ liệu.';
            }
        } else {
            exportError.value = err.response?.data?.message ?? 'Có lỗi xảy ra khi xuất dữ liệu.';
        }
    }
}
</script>
