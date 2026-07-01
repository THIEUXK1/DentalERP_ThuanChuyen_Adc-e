<template>
    <AppLayout title="Đăng ký khám">
        <div class="space-y-3">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-800">
                    Đăng ký khám
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ filtered.length }})</span>
                </h2>
                <div class="flex items-center gap-2">
                    <!-- Date navigation -->
                    <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <button @click="prevDay" class="px-2 py-2 text-gray-500 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <input type="date" v-model="selectedDate"
                            class="border-0 text-sm text-gray-700 font-medium px-1 py-2 focus:outline-none focus:ring-0 bg-transparent cursor-pointer" />
                        <button @click="nextDay" class="px-2 py-2 text-gray-500 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    <button @click="goToday"
                        :class="['px-3 py-2 text-sm rounded-xl border shadow-sm font-medium transition-colors',
                            isToday ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50']">
                        Hôm nay
                    </button>
                    <Link :href="route('patients.index')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 shadow-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Đăng ký mới
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <div class="text-2xl font-bold text-gray-800">{{ filtered.length }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Tổng đăng ký</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <div class="text-2xl font-bold text-yellow-600">{{ countByStatus('pending') }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Đang chờ</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <div class="text-2xl font-bold text-teal-600">{{ countByStatus('in_treatment') }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Đang làm</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <div class="text-2xl font-bold text-green-600">{{ countByStatus('completed') }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Đã xong</div>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap gap-2 shadow-sm">
                <input v-model="search" type="text" placeholder="Tìm bệnh nhân, SĐT..."
                    class="flex-1 min-w-40 rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                <select v-model="filterStatus" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <button v-if="search || filterStatus" @click="clearFilters"
                    class="px-3 py-1.5 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50">
                    Xóa lọc
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="filtered.length === 0" class="py-16 text-center">
                    <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-400 text-sm">Không có đăng ký khám nào ngày {{ formatDateVn(selectedDate) }}</p>
                    <Link :href="route('patients.index')"
                        class="mt-3 inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:underline">
                        Đăng ký mới
                    </Link>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs">Giờ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs">Bệnh nhân</th>
                            <th class="hidden sm:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">SĐT</th>
                            <th class="hidden md:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">Bác sĩ</th>
                            <th class="hidden lg:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">Ghế</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs">Trạng thái</th>
                            <th class="hidden xl:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">Ghi chú</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 text-xs">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="a in filtered" :key="a.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 font-mono text-gray-800 font-medium">{{ a.scheduled_time }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('patients.register-appointment', a.patient_id)"
                                    class="font-medium text-gray-800 hover:text-indigo-600">
                                    {{ a.patient }}
                                </Link>
                                <div class="sm:hidden text-xs text-gray-400">{{ a.patient_phone }}</div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 text-gray-500">{{ a.patient_phone ?? '—' }}</td>
                            <td class="hidden md:table-cell px-4 py-3 text-gray-600">{{ a.doctor }}</td>
                            <td class="hidden lg:table-cell px-4 py-3 text-gray-600">{{ a.chair }}</td>
                            <td class="px-4 py-3">
                                <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', statusClass(a.status_color)]">
                                    {{ a.status_label }}
                                </span>
                            </td>
                            <td class="hidden xl:table-cell px-4 py-3 text-gray-500 max-w-xs truncate">{{ a.notes ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="route('schedule.appointments.edit', a.id)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Sửa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </Link>
                                    <button @click="printRow(a)"
                                        class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="In">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </button>
                                    <button @click="openDelete(a)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Xóa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Delete modal -->
        <div v-if="deleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="font-semibold text-gray-800 mb-1">Xóa đăng ký khám</h3>
                <p class="text-sm text-gray-500 mb-4">Bệnh nhân: <strong>{{ deleteModal.row?.patient }}</strong> — {{ deleteModal.row?.scheduled_time }}</p>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lý do xóa <span class="text-red-500">*</span></label>
                <textarea v-model="deleteModal.reason" rows="2"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-red-300 focus:outline-none mb-4"
                    placeholder="Nhập lý do xóa..." />
                <div class="flex justify-end gap-3">
                    <button @click="deleteModal.open = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                    <button @click="confirmDelete" :disabled="!deleteModal.reason.trim()"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    all_appointments: Array,
    statuses: Array,
});

const today = new Date().toISOString().slice(0, 10);
const selectedDate = ref(today);
const search = ref('');
const filterStatus = ref('');

const isToday = computed(() => selectedDate.value === today);

function prevDay() {
    const d = new Date(selectedDate.value);
    d.setDate(d.getDate() - 1);
    selectedDate.value = d.toISOString().slice(0, 10);
}
function nextDay() {
    const d = new Date(selectedDate.value);
    d.setDate(d.getDate() + 1);
    selectedDate.value = d.toISOString().slice(0, 10);
}
function goToday() {
    selectedDate.value = today;
}

function clearFilters() {
    search.value = '';
    filterStatus.value = '';
}

const filtered = computed(() => {
    let list = props.all_appointments.filter(a => a.scheduled_date === selectedDate.value);
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(a =>
            a.patient.toLowerCase().includes(q) ||
            (a.patient_phone && a.patient_phone.includes(q))
        );
    }
    if (filterStatus.value) {
        list = list.filter(a => a.status === filterStatus.value);
    }
    return list;
});

function countByStatus(status) {
    return filtered.value.filter(a => a.status === status).length;
}

function formatDateVn(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

const STATUS_BG = {
    gray:   'bg-gray-100 text-gray-700',
    blue:   'bg-blue-100 text-blue-700',
    indigo: 'bg-indigo-100 text-indigo-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    teal:   'bg-teal-100 text-teal-700',
    orange: 'bg-orange-100 text-orange-700',
    red:    'bg-red-100 text-red-700',
    purple: 'bg-purple-100 text-purple-700',
    green:  'bg-green-100 text-green-700',
};
function statusClass(color) {
    return STATUS_BG[color] ?? STATUS_BG.gray;
}

function printRow(a) {
    const w = window.open('', '_blank', 'width=400,height=300');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Đăng ký khám</title>
    <style>body{font-family:Arial,sans-serif;font-size:13px;padding:16px}h3{margin:0 0 8px}table{width:100%}td{padding:3px 0}td:first-child{color:#555;width:110px}.val{font-weight:600}</style>
    </head><body>
    <h3>PHIẾU ĐĂNG KÝ KHÁM</h3>
    <table>
    <tr><td>Bệnh nhân</td><td class="val">${a.patient}</td></tr>
    <tr><td>SĐT</td><td class="val">${a.patient_phone ?? '—'}</td></tr>
    <tr><td>Ngày khám</td><td class="val">${formatDateVn(a.scheduled_date)}</td></tr>
    <tr><td>Giờ</td><td class="val">${a.scheduled_time}</td></tr>
    <tr><td>Bác sĩ</td><td class="val">${a.doctor}</td></tr>
    <tr><td>Ghế</td><td class="val">${a.chair}</td></tr>
    <tr><td>Trạng thái</td><td class="val">${a.status_label}</td></tr>
    ${a.notes ? `<tr><td>Ghi chú</td><td class="val">${a.notes}</td></tr>` : ''}
    </table>
    <script>window.onload=()=>{window.print();window.close();}<\/script>
    </body></html>`);
    w.document.close();
}

const deleteModal = ref({ open: false, row: null, reason: '' });

function openDelete(row) {
    deleteModal.value = { open: true, row, reason: '' };
}

function confirmDelete() {
    if (!deleteModal.value.reason.trim()) return;
    router.delete(route('schedule.appointments.destroy', deleteModal.value.row.id), {
        data: { reason: deleteModal.value.reason },
        preserveScroll: true,
        onSuccess: () => { deleteModal.value.open = false; },
    });
}
</script>
