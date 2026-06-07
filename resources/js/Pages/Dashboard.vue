<template>
    <AppLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Branch filter (owner/admin only) -->
            <div v-if="branches.length > 0" class="flex items-center gap-3">
                <label class="text-sm text-gray-600">Chi nhánh:</label>
                <select v-model="selectedBranch" @change="changeBranch"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option :value="null">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <StatCard label="Lịch hẹn hôm nay" :value="kpis.todayAppts" icon="calendar" color="blue" />
                <StatCard v-if="canFinancial" label="Doanh thu hôm nay" :value="formatVnd(kpis.todayRevenue)" icon="receipt" color="green" />
                <StatCard v-if="canFinancial" label="Tổng công nợ" :value="formatVnd(kpis.totalOutstanding)" icon="chart" color="red" />
                <StatCard label="Lead mới (7 ngày)" :value="kpis.newLeads" icon="funnel" color="yellow" />
                <StatCard label="Khách hàng đang hoạt động" :value="kpis.activePatients" icon="users" color="teal" />
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Revenue trend -->
                <div class="lg:col-span-2">
                    <ChartCard v-if="canFinancial && revenueTrend.length > 0"
                        title="Doanh thu 30 ngày qua"
                        type="line"
                        :data="revenueChartData"
                        :height="220"
                    />
                    <div v-else-if="canFinancial" class="bg-white rounded-xl border border-gray-200 p-5 text-center text-gray-400 text-sm">
                        Chưa có dữ liệu doanh thu
                    </div>
                </div>

                <!-- Appointment breakdown -->
                <div>
                    <ChartCard v-if="canClinical && apptBreakdown.length > 0"
                        title="Lịch hẹn theo trạng thái"
                        type="doughnut"
                        :data="apptChartData"
                        :height="220"
                    />
                    <div v-else-if="canClinical" class="bg-white rounded-xl border border-gray-200 p-5 text-center text-gray-400 text-sm">
                        Chưa có lịch hẹn
                    </div>
                </div>
            </div>

            <!-- Lead funnel -->
            <div v-if="leadFunnel.length > 0" class="bg-white rounded-xl border border-gray-200 p-5">
                <ChartCard title="Pipeline Lead" type="bar" :data="leadChartData" :height="180" />
            </div>

            <!-- Treatment plan conversion + Revenue splits -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Treatment plan conversion KPI -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3">
                    <h3 class="text-sm font-semibold text-gray-700">Tỷ lệ chốt kế hoạch điều trị</h3>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-bold text-teal-600">{{ treatmentPlanConversion.rate }}%</span>
                        <span class="text-sm text-gray-400 mb-1">chốt</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-teal-500 h-2 rounded-full transition-all"
                            :style="{ width: treatmentPlanConversion.rate + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-400">
                        {{ treatmentPlanConversion.approved }} / {{ treatmentPlanConversion.total }} kế hoạch được duyệt
                    </p>
                </div>

                <!-- Revenue by doctor -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <ChartCard v-if="canFinancial && revenueByDoctor.length > 0"
                        title="Doanh thu theo bác sĩ (30 ngày)"
                        type="bar"
                        :data="doctorChartData"
                        :height="160"
                        :options="{ indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => formatVndShort(v) } } } }"
                    />
                    <div v-else-if="canFinancial" class="text-sm text-gray-400 text-center py-8">Chưa có dữ liệu</div>
                </div>

                <!-- Revenue by service -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <ChartCard v-if="canFinancial && revenueByService.length > 0"
                        title="Dịch vụ doanh thu cao (30 ngày)"
                        type="bar"
                        :data="serviceChartData"
                        :height="160"
                        :options="{ indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => formatVndShort(v) } } } }"
                    />
                    <div v-else-if="canFinancial" class="text-sm text-gray-400 text-center py-8">Chưa có dữ liệu</div>
                </div>
            </div>

            <!-- Quick nav -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <Link :href="route('schedule.appointments.create')"
                    class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all">
                    <div class="text-2xl mb-2">📅</div>
                    <p class="text-sm font-medium text-gray-700">Đặt lịch hẹn</p>
                </Link>
                <Link :href="route('patients.create')"
                    class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all">
                    <div class="text-2xl mb-2">👤</div>
                    <p class="text-sm font-medium text-gray-700">Thêm khách hàng</p>
                </Link>
                <Link :href="route('crm.leads.create')"
                    class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all">
                    <div class="text-2xl mb-2">🎯</div>
                    <p class="text-sm font-medium text-gray-700">Thêm lead mới</p>
                </Link>
                <Link v-if="canFinancial" :href="route('cashier.debts.index')"
                    class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:border-primary-300 hover:shadow-sm transition-all">
                    <div class="text-2xl mb-2">💰</div>
                    <p class="text-sm font-medium text-gray-700">Thu công nợ</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatCard from '@/Components/Shared/StatCard.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();
const props = defineProps({
    kpis:                    Object,
    revenueTrend:            Array,
    apptBreakdown:           Array,
    leadFunnel:              Array,
    treatmentPlanConversion: Object,
    revenueByDoctor:         Array,
    revenueByService:        Array,
    branches:                Array,
    canFinancial:            Boolean,
    canClinical:             Boolean,
    selectedBranch:          [Number, null],
});

function formatVndShort(v) {
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(0) + 'tr';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + 'k';
    return v;
}

const selectedBranch = ref(props.selectedBranch);

function changeBranch() {
    router.get(route('dashboard'), { branch_id: selectedBranch.value }, { preserveState: true });
}

// Revenue chart
const revenueChartData = computed(() => ({
    labels: props.revenueTrend.map(r => r.day),
    datasets: [
        {
            label: 'Doanh thu',
            data: props.revenueTrend.map(r => r.revenue),
            borderColor: '#0d9488',
            backgroundColor: 'rgba(13,148,136,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 2,
        },
    ],
}));

// Appointment breakdown chart
const apptColors = {
    booked: '#9ca3af', confirmed: '#3b82f6', checked_in: '#14b8a6',
    in_treatment: '#6366f1', completed: '#22c55e', cancelled: '#ef4444',
    no_show: '#f97316', rescheduled: '#eab308',
};
const apptLabels = {
    booked: 'Đã đặt', confirmed: 'Xác nhận', checked_in: 'Check-in',
    in_treatment: 'Đang KT', completed: 'Xong', cancelled: 'Hủy',
    no_show: 'Không đến', rescheduled: 'Dời lịch',
};

const apptChartData = computed(() => ({
    labels: props.apptBreakdown.map(r => apptLabels[r.status] ?? r.status),
    datasets: [{
        data: props.apptBreakdown.map(r => r.count),
        backgroundColor: props.apptBreakdown.map(r => apptColors[r.status] ?? '#9ca3af'),
    }],
}));

// Lead funnel chart
const leadChartData = computed(() => ({
    labels: props.leadFunnel.map(r => r.status),
    datasets: [{
        label: 'Lead',
        data: props.leadFunnel.map(r => r.count),
        backgroundColor: '#0d9488',
        borderRadius: 4,
    }],
}));

// Revenue by doctor chart
const doctorChartData = computed(() => ({
    labels: props.revenueByDoctor.map(r => r.name),
    datasets: [{
        label: 'Doanh thu',
        data: props.revenueByDoctor.map(r => r.revenue),
        backgroundColor: '#0d9488',
        borderRadius: 4,
    }],
}));

// Revenue by service chart
const serviceChartData = computed(() => ({
    labels: props.revenueByService.map(r => r.name),
    datasets: [{
        label: 'Doanh thu',
        data: props.revenueByService.map(r => r.revenue),
        backgroundColor: '#6366f1',
        borderRadius: 4,
    }],
}));
</script>
