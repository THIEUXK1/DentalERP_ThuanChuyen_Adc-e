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
                    <button @click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 shadow-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Đăng ký mới
                    </button>
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
                    <button @click="openCreate" class="mt-3 inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:underline">
                        Đăng ký mới
                    </button>
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
                        <tr v-for="r in filtered" :key="r.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 font-mono text-gray-800 font-medium">{{ r.visit_time ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('patients.show', r.patient_id)"
                                    class="font-medium text-gray-800 hover:text-indigo-600">
                                    {{ r.patient }}
                                </Link>
                                <div class="sm:hidden text-xs text-gray-400">{{ r.patient_phone }}</div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 text-gray-500">{{ r.patient_phone ?? '—' }}</td>
                            <td class="hidden md:table-cell px-4 py-3 text-gray-600">{{ r.doctor }}</td>
                            <td class="hidden lg:table-cell px-4 py-3 text-gray-600">{{ r.chair }}</td>
                            <td class="px-4 py-3">
                                <select
                                    :value="r.status"
                                    @change="changeStatus(r, $event.target.value)"
                                    :class="['text-xs font-medium rounded-full px-2 py-0.5 border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-300', statusClass(r.status_color)]">
                                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                                <div v-if="r.status === 'pending' && r.pending_since"
                                    class="mt-0.5 text-xs font-mono text-yellow-600 flex items-center gap-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ elapsed(r.pending_since) }}
                                </div>
                            </td>
                            <td class="hidden xl:table-cell px-4 py-3 text-gray-500 max-w-xs truncate">{{ r.notes ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEdit(r)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Sửa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="printRow(r)"
                                        class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="In">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </button>
                                    <button @click="openDelete(r)"
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

        <!-- Create modal -->
        <div v-if="createModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Đăng ký khám mới</h3>
                    <button @click="createModal.open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="p-5 space-y-4">
                    <!-- Patient search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bệnh nhân <span class="text-red-500">*</span></label>
                        <input v-model="patientSearch" type="text" placeholder="Tìm tên hoặc SĐT..."
                            @input="filteredPatients"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        <div v-if="patientSearch && !createForm.patient_id" class="mt-1 max-h-40 overflow-y-auto border border-gray-200 rounded-lg bg-white shadow-sm">
                            <button type="button" v-for="p in patientSuggestions" :key="p.id"
                                @click="selectPatient(p)"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 border-b border-gray-50 last:border-0">
                                <span class="font-medium">{{ p.full_name }}</span>
                                <span class="text-gray-400 ml-2">{{ p.phone }}</span>
                            </button>
                            <div v-if="patientSuggestions.length === 0" class="px-3 py-2 text-sm text-gray-400">Không tìm thấy</div>
                        </div>
                        <div v-if="createForm.patient_id" class="mt-1 flex items-center gap-2 text-sm text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ patientSearch }}
                            <button type="button" @click="clearPatient" class="text-gray-400 hover:text-red-500 ml-auto">✕</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày khám</label>
                            <input type="date" v-model="createForm.registration_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giờ vào</label>
                            <input type="time" v-model="createForm.visit_time"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bác sĩ</label>
                            <select v-model="createForm.doctor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghế</label>
                            <select v-model="createForm.dental_chair_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="c in chairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select v-model="createForm.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea v-model="createForm.notes" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                            placeholder="Ghi chú thêm..." />
                    </div>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="createModal.open = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button type="submit" :disabled="!createForm.patient_id"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                            Lưu đăng ký
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit modal -->
        <div v-if="editModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Chỉnh sửa đăng ký</h3>
                    <button @click="editModal.open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày khám</label>
                            <input type="date" v-model="editForm.registration_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giờ vào</label>
                            <input type="time" v-model="editForm.visit_time"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bác sĩ</label>
                            <select v-model="editForm.doctor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghế</label>
                            <select v-model="editForm.dental_chair_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="c in chairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select v-model="editForm.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea v-model="editForm.notes" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                            placeholder="Ghi chú..." />
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="editModal.open = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete modal -->
        <div v-if="deleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="font-semibold text-gray-800 mb-1">Xóa đăng ký khám</h3>
                <p class="text-sm text-gray-500 mb-4">Bệnh nhân: <strong>{{ deleteModal.row?.patient }}</strong> — {{ deleteModal.row?.visit_time ?? '' }}</p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteModal.open = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                    <button @click="confirmDelete"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    all_registrations: Array,
    statuses:          Array,
    patients:          Array,
    doctors:           Array,
    chairs:            Array,
});

const today = new Date().toISOString().slice(0, 10);
const selectedDate = ref(today);
const search = ref('');
const filterStatus = ref('');

const isToday = computed(() => selectedDate.value === today);

function prevDay() {
    const d = new Date(selectedDate.value); d.setDate(d.getDate() - 1);
    selectedDate.value = d.toISOString().slice(0, 10);
}
function nextDay() {
    const d = new Date(selectedDate.value); d.setDate(d.getDate() + 1);
    selectedDate.value = d.toISOString().slice(0, 10);
}
function goToday() { selectedDate.value = today; }
function clearFilters() { search.value = ''; filterStatus.value = ''; }

const filtered = computed(() => {
    let list = props.all_registrations.filter(r => r.registration_date === selectedDate.value);
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(r =>
            r.patient.toLowerCase().includes(q) ||
            (r.patient_phone && r.patient_phone.includes(q))
        );
    }
    if (filterStatus.value) list = list.filter(r => r.status === filterStatus.value);
    return list;
});

function countByStatus(status) {
    return filtered.value.filter(r => r.status === status).length;
}

function formatDateVn(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

// ── Live timer for pending rows ──────────────────────────────────────────
const nowTs = ref(Date.now());
let timerInterval = null;
onMounted(() => { timerInterval = setInterval(() => { nowTs.value = Date.now(); }, 1000); });
onUnmounted(() => clearInterval(timerInterval));

function elapsed(pendingSince) {
    if (!pendingSince) return null;
    const secs = Math.floor((nowTs.value - new Date(pendingSince).getTime()) / 1000);
    if (secs < 0) return '00:00';
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = secs % 60;
    if (h > 0) return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

const STATUS_BG = {
    gray: 'bg-gray-100 text-gray-700', yellow: 'bg-yellow-100 text-yellow-700',
    teal: 'bg-teal-100 text-teal-700', green: 'bg-green-100 text-green-700',
    red:  'bg-red-100 text-red-700',
};
function statusClass(color) { return STATUS_BG[color] ?? STATUS_BG.gray; }

// ── Status change inline ─────────────────────────────────────────────────
function changeStatus(r, newStatus) {
    router.patch(route('schedule.registrations.patch', r.id), { status: newStatus }, {
        preserveScroll: true,
    });
}

// ── Create modal ─────────────────────────────────────────────────────────
const createModal = ref({ open: false });
const patientSearch = ref('');
const createForm = ref({
    patient_id: null, registration_date: today, visit_time: '',
    doctor_id: null, dental_chair_id: null, status: 'pending', notes: '',
});

const patientSuggestions = computed(() => {
    if (!patientSearch.value || createForm.value.patient_id) return [];
    const q = patientSearch.value.toLowerCase();
    return (props.patients ?? [])
        .filter(p => p.full_name.toLowerCase().includes(q) || (p.phone && p.phone.includes(q)))
        .slice(0, 8);
});

function openCreate() {
    createForm.value = {
        patient_id: null, registration_date: selectedDate.value,
        visit_time: '', doctor_id: null, dental_chair_id: null,
        status: 'pending', notes: '',
    };
    patientSearch.value = '';
    createModal.value.open = true;
}

function selectPatient(p) {
    createForm.value.patient_id = p.id;
    patientSearch.value = p.full_name + (p.phone ? ` (${p.phone})` : '');
}

function clearPatient() {
    createForm.value.patient_id = null;
    patientSearch.value = '';
}

function submitCreate() {
    router.post(route('schedule.registrations.store'), createForm.value, {
        preserveScroll: true,
        onSuccess: () => { createModal.value.open = false; },
    });
}

// ── Edit modal ───────────────────────────────────────────────────────────
const editModal = ref({ open: false });
const editForm = ref({
    id: null, registration_date: '', visit_time: '',
    doctor_id: null, dental_chair_id: null, status: 'pending', notes: '',
});

function openEdit(r) {
    editForm.value = {
        id:                r.id,
        registration_date: r.registration_date,
        visit_time:        r.visit_time ?? '',
        doctor_id:         r.doctor_id,
        dental_chair_id:   r.dental_chair_id,
        status:            r.status,
        notes:             r.notes ?? '',
    };
    editModal.value.open = true;
}

function submitEdit() {
    router.put(route('schedule.registrations.update', editForm.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => { editModal.value.open = false; },
    });
}

// ── Print ────────────────────────────────────────────────────────────────
function printRow(r) {
    const w = window.open('', '_blank', 'width=400,height=300');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Đăng ký khám</title>
    <style>body{font-family:Arial,sans-serif;font-size:13px;padding:16px}h3{margin:0 0 8px}table{width:100%}td{padding:3px 0}td:first-child{color:#555;width:110px}.val{font-weight:600}</style>
    </head><body>
    <h3>PHIẾU ĐĂNG KÝ KHÁM</h3>
    <table>
    <tr><td>Bệnh nhân</td><td class="val">${r.patient}</td></tr>
    <tr><td>SĐT</td><td class="val">${r.patient_phone ?? '—'}</td></tr>
    <tr><td>Ngày khám</td><td class="val">${formatDateVn(r.registration_date)}</td></tr>
    <tr><td>Giờ</td><td class="val">${r.visit_time ?? '—'}</td></tr>
    <tr><td>Bác sĩ</td><td class="val">${r.doctor}</td></tr>
    <tr><td>Ghế</td><td class="val">${r.chair}</td></tr>
    <tr><td>Trạng thái</td><td class="val">${r.status_label}</td></tr>
    ${r.notes ? `<tr><td>Ghi chú</td><td class="val">${r.notes}</td></tr>` : ''}
    </table>
    <script>window.onload=()=>{window.print();window.close();}<\/script>
    </body></html>`);
    w.document.close();
}

// ── Delete ───────────────────────────────────────────────────────────────
const deleteModal = ref({ open: false, row: null });

function openDelete(row) { deleteModal.value = { open: true, row }; }

function confirmDelete() {
    router.delete(route('schedule.registrations.destroy', deleteModal.value.row.id), {
        preserveScroll: true,
        onSuccess: () => { deleteModal.value.open = false; },
    });
}
</script>
