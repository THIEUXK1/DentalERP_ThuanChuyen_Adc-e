<template>
    <AppLayout title="Lịch hẹn">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Lịch hẹn ngày {{ formattedDate }}</h2>
                <Link v-if="can('appointments.create')" :href="route('schedule.appointments.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                    + Đặt lịch hẹn
                </Link>
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
                <select v-model="status" @change="applyFilters"
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
                <Link v-for="a in appointments" :key="a.id" :href="route('schedule.appointments.show', a.id)"
                    class="flex items-center gap-4 bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-200 hover:shadow-sm transition-all">
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
                    </div>
                    <!-- Status -->
                    <StatusBadge :color="a.status_color">{{ a.status_label }}</StatusBadge>
                    <!-- Code -->
                    <span class="font-mono text-xs text-gray-400 flex-shrink-0">{{ a.code }}</span>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ appointments: Array, date: String, branches: Array, doctors: Array, statuses: Array, filters: Object });

const date     = ref(props.date);
const branchId = ref(props.filters.branch_id ?? '');
const doctorId = ref(props.filters.doctor_id ?? '');
const status   = ref(props.filters.status ?? '');

const formattedDate = computed(() => dayjs(date.value).format('DD/MM/YYYY'));

function applyFilters() {
    router.get(route('schedule.appointments.index'), { date: date.value, branch_id: branchId.value, doctor_id: doctorId.value, status: status.value }, { preserveState: true });
}

function changeDate(delta) {
    date.value = dayjs(date.value).add(delta, 'day').format('YYYY-MM-DD');
    applyFilters();
}

function goToday() {
    date.value = dayjs().format('YYYY-MM-DD');
    applyFilters();
}
</script>
