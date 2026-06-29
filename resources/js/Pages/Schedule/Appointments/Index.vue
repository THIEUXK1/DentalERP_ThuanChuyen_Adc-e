<template>
    <AppLayout title="Lịch hẹn">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Lịch hẹn ngày {{ formattedDate }}</h2>
                <button v-if="can('appointments.create')" @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Đặt lịch hẹn
                </button>
            </div>

            <!-- Date navigation + filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center gap-3">
                <button @click="changeDate(-1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <input type="date" v-model="date" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <button @click="changeDate(1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
                <button @click="goToday" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Hôm nay</button>

                <select v-model="branchId" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="doctorId" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả bác sĩ</option>
                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <select v-model="filterStatus" @change="applyFilters"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>

            <!-- Appointments list -->
            <div class="space-y-2">
                <div v-if="appointments.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                    Không có lịch hẹn nào trong ngày này
                </div>

                <div v-for="a in appointments" :key="a.id"
                    class="flex items-center gap-4 bg-white rounded-xl border border-gray-200 hover:border-primary-200 hover:shadow-sm transition-all">

                    <!-- Clickable main area -->
                    <Link :href="route('schedule.appointments.show', a.id)" class="flex flex-1 items-center gap-4 p-4 min-w-0">
                        <!-- Time block -->
                        <div class="w-20 text-center flex-shrink-0">
                            <p class="text-sm font-bold text-gray-800">{{ a.scheduled_at.split(' ')[1] }}</p>
                            <p class="text-xs text-gray-400">→ {{ a.ends_at }}</p>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 truncate">{{ a.patient }}</p>
                                <span class="text-xs text-gray-400">{{ a.patient_phone }}</span>
                            </div>
                            <p class="text-sm text-gray-500">{{ a.service }} · {{ a.doctor }} · {{ a.chair }}</p>
                            <p v-if="a.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 mt-1 inline-block max-w-xs truncate">
                                📝 {{ a.notes }}
                            </p>
                        </div>
                        <!-- Status -->
                        <StatusBadge :color="a.status_color">{{ a.status_label }}</StatusBadge>
                        <!-- Code -->
                        <span class="font-mono text-xs text-gray-400 flex-shrink-0 hidden sm:block">{{ a.code }}</span>
                    </Link>

                    <!-- Rời lịch button -->
                    <div v-if="can('appointments.manage')" class="pr-3 flex-shrink-0">
                        <button @click="openReschedule(a)"
                            title="Rời lịch nhanh"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 rounded-lg hover:bg-indigo-100 hover:border-indigo-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Rời lịch
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             SLIDE-OVER: TẠO LỊCH HẸN MỚI
        ═══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex justify-end">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="showCreate = false"></div>

                <!-- Panel -->
                <div class="relative bg-white w-full max-w-lg h-full flex flex-col shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                        <h3 class="text-base font-semibold text-gray-900">Đặt lịch hẹn mới</h3>
                        <button @click="showCreate = false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <div v-if="createForm.errors.conflict" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            ⚠ {{ createForm.errors.conflict }}
                        </div>

                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <!-- Khách hàng -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                    Khách hàng <span class="text-red-500">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="createForm.patient_id"
                                    :options="patientOptions"
                                    placeholder="-- Tìm khách hàng --"
                                    @update:modelValue="onPatientChange"
                                />
                                <p v-if="createForm.errors.patient_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.patient_id }}</p>
                            </div>

                            <!-- Chi nhánh -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                    Chi nhánh <span class="text-red-500">*</span>
                                </label>
                                <select v-model="createForm.branch_id" @change="onBranchChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                    <option value="">-- Chọn chi nhánh --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <p v-if="createForm.errors.branch_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.branch_id }}</p>
                            </div>

                            <!-- Bác sĩ + Ghế nha (2 cột) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Bác sĩ</label>
                                    <select v-model="createForm.doctor_id"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                        <option :value="null">-- Chọn bác sĩ --</option>
                                        <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghế nha</label>
                                    <select v-model="createForm.dental_chair_id"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                        <option :value="null">-- Chọn ghế --</option>
                                        <option v-for="c in filteredChairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dịch vụ -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Dịch vụ</label>
                                <select v-model="createForm.service_id" @change="onServiceChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                    <option :value="null">-- Chọn dịch vụ --</option>
                                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>

                            <!-- Ngày giờ + Thời lượng (2 cột) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                        Ngày & giờ hẹn <span class="text-red-500">*</span>
                                    </label>
                                    <input type="datetime-local" v-model="createForm.scheduled_at"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                                    <p v-if="createForm.errors.scheduled_at" class="text-red-500 text-xs mt-1">{{ createForm.errors.scheduled_at }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng (phút)</label>
                                    <select v-model="createForm.duration_minutes"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                        <option :value="15">15 phút</option>
                                        <option :value="20">20 phút</option>
                                        <option :value="30">30 phút</option>
                                        <option :value="45">45 phút</option>
                                        <option :value="60">60 phút</option>
                                        <option :value="90">90 phút</option>
                                        <option :value="120">2 giờ</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Ghi chú -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghi chú</label>
                                <textarea v-model="createForm.notes" rows="3" placeholder="Ghi chú thêm về lịch hẹn..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none resize-none" />
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50">
                        <button @click="showCreate = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100">
                            Hủy
                        </button>
                        <button @click="submitCreate" :disabled="createForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-1.5">
                            <svg v-if="createForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Đặt lịch
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════
             MODAL: RỜI LỊCH NHANH
        ═══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="rescheduleTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="rescheduleTarget = null"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm z-10">
                    <div class="px-5 pt-5 pb-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Rời lịch hẹn</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ rescheduleTarget.patient }}
                            <span class="font-mono text-xs text-gray-400 ml-1">{{ rescheduleTarget.code }}</span>
                        </p>
                    </div>

                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ngày & giờ mới</label>
                            <input type="datetime-local" v-model="rsForm.scheduled_at"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            <p v-if="rsErrors.scheduled_at" class="text-red-500 text-xs mt-1">{{ rsErrors.scheduled_at }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng (phút)</label>
                            <select v-model="rsForm.duration_minutes"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="15">15 phút</option>
                                <option :value="20">20 phút</option>
                                <option :value="30">30 phút</option>
                                <option :value="45">45 phút</option>
                                <option :value="60">60 phút</option>
                                <option :value="90">90 phút</option>
                                <option :value="120">2 giờ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghi chú</label>
                            <textarea v-model="rsForm.notes" rows="3" placeholder="Để trống nếu không muốn thay đổi..."
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none" />
                        </div>
                        <p v-if="rsErrors.conflict" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">
                            {{ rsErrors.conflict }}
                        </p>
                    </div>

                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button @click="rescheduleTarget = null"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button @click="submitReschedule" :disabled="rsSaving"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5">
                            <svg v-if="rsSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Xác nhận rời lịch
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({
    appointments: Array,
    date: String,
    branches: Array,
    doctors: Array,
    chairs: Array,
    services: Array,
    patients: Array,
    statuses: Array,
    filters: Object,
});

// ── Filters ──────────────────────────────────────────────────
const date        = ref(props.date);
const branchId    = ref(props.filters.branch_id ?? '');
const doctorId    = ref(props.filters.doctor_id ?? '');
const filterStatus = ref(props.filters.status ?? '');

const formattedDate = computed(() => dayjs(date.value).format('DD/MM/YYYY'));

function applyFilters() {
    router.get(route('schedule.appointments.index'), {
        date: date.value, branch_id: branchId.value,
        doctor_id: doctorId.value, status: filterStatus.value,
    }, { preserveState: true });
}
function changeDate(delta) {
    date.value = dayjs(date.value).add(delta, 'day').format('YYYY-MM-DD');
    applyFilters();
}
function goToday() {
    date.value = dayjs().format('YYYY-MM-DD');
    applyFilters();
}

// ── Create slide-over ────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    patient_id:       '',
    branch_id:        '',
    doctor_id:        null,
    dental_chair_id:  null,
    service_id:       null,
    scheduled_at:     dayjs(date.value).format('YYYY-MM-DD') + 'T08:00',
    duration_minutes: 30,
    notes:            '',
});

const patientOptions  = computed(() => props.patients.map(p => ({ value: p.id, label: `${p.code} — ${p.full_name}`, sublabel: p.phone })));
const filteredDoctors = computed(() => props.doctors.filter(d => !createForm.branch_id || d.branch_id == createForm.branch_id));
const filteredChairs  = computed(() => props.chairs.filter(c => !createForm.branch_id || c.branch_id == createForm.branch_id));

function openCreate() {
    createForm.reset();
    createForm.scheduled_at = dayjs(date.value).format('YYYY-MM-DD') + 'T08:00';
    showCreate.value = true;
}

function onPatientChange(patientId) {
    const p = props.patients.find(x => x.id === patientId);
    if (p?.branch_id && !createForm.branch_id) {
        createForm.branch_id = p.branch_id;
    }
}

function onBranchChange() {
    createForm.doctor_id = null;
    createForm.dental_chair_id = null;
}

function onServiceChange() {
    const svc = props.services.find(s => s.id === createForm.service_id);
    if (svc) createForm.duration_minutes = svc.duration_minutes;
}

function submitCreate() {
    createForm.post(route('schedule.appointments.store'), {
        onSuccess: () => { showCreate.value = false; },
    });
}

// ── Quick reschedule ─────────────────────────────────────────
const rescheduleTarget = ref(null);
const rsForm    = ref({ scheduled_at: '', duration_minutes: 30, notes: '' });
const rsErrors  = ref({});
const rsSaving  = ref(false);

function openReschedule(a) {
    rescheduleTarget.value = a;
    rsForm.value.scheduled_at    = a.scheduled_at.replace(' ', 'T');
    rsForm.value.duration_minutes = a.duration_minutes;
    rsForm.value.notes            = a.notes ?? '';
    rsErrors.value = {};
}

function submitReschedule() {
    if (!rescheduleTarget.value) return;
    rsSaving.value = true;
    rsErrors.value = {};

    router.patch(
        route('schedule.appointments.quick-reschedule', rescheduleTarget.value.id),
        {
            scheduled_at:     rsForm.value.scheduled_at.replace('T', ' ') + ':00',
            duration_minutes: rsForm.value.duration_minutes,
            notes:            rsForm.value.notes,
        },
        {
            preserveScroll: true,
            onSuccess: () => { rescheduleTarget.value = null; },
            onError:   (errors) => { rsErrors.value = errors; },
            onFinish:  () => { rsSaving.value = false; },
        }
    );
}
</script>
