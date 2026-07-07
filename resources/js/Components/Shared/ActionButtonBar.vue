<template>
    <!-- Action Button Bar — inspired by Bambu -->
    <div class="flex flex-wrap items-center gap-1.5">
        <!-- Primary actions -->
        <Link v-if="can('appointments.create')"
            :href="route('patients.register-appointment', patientId)"
            class="action-btn action-btn-teal">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Đăng ký khám
        </Link>

        <button v-if="can('appointments.view')"
            @click="$emit('book-appointment')"
            class="action-btn action-btn-primary">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Đặt lịch hẹn
        </button>

        <Link v-if="can('treatment_plans.view')"
            :href="route('clinical.treatment-plans.create', { patient_id: patientId })"
            class="action-btn action-btn-green">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Kế hoạch điều trị
        </Link>

        <Link v-if="can('cashier.view')"
            :href="route('cashier.invoices.index', { patient_id: patientId })"
            class="action-btn action-btn-amber">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Thanh toán
        </Link>

        <!-- Divider -->
        <div class="h-5 w-px bg-gray-200 mx-0.5 hidden sm:block"></div>

        <!-- Secondary actions -->
        <Link v-if="can('clinical_notes.create')"
            :href="route('patients.show', patientId) + '#clinical'"
            class="action-btn action-btn-ghost">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Hồ sơ lâm sàng
        </Link>

        <button v-if="can('patients.edit')"
            @click="$emit('edit')"
            class="action-btn action-btn-ghost">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Sửa hồ sơ
        </button>

        <button v-if="can('patients.delete')"
            @click="$emit('merge')"
            class="action-btn action-btn-ghost">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Gộp hồ sơ
        </button>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

defineProps({ patientId: { type: Number, required: true } });
defineEmits(['edit', 'book-appointment', 'merge']);
</script>

<style scoped>
.action-btn {
    @apply inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150 cursor-pointer whitespace-nowrap border;
}
.action-btn-primary {
    @apply bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700 hover:border-indigo-700 shadow-sm;
}
.action-btn-green {
    @apply bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700 hover:border-emerald-700 shadow-sm;
}
.action-btn-amber {
    @apply bg-amber-500 text-white border-amber-500 hover:bg-amber-600 hover:border-amber-600 shadow-sm;
}
.action-btn-teal {
    @apply bg-teal-600 text-white border-teal-600 hover:bg-teal-700 hover:border-teal-700 shadow-sm;
}
.action-btn-ghost {
    @apply bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-800;
}
</style>
