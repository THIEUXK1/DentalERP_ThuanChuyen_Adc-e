<template>
    <aside :class="['fixed inset-y-0 left-0 z-30 flex flex-col bg-gray-900 transition-all duration-200', collapsed ? 'w-16' : 'w-60']">
        <!-- Logo -->
        <div class="flex h-16 items-center justify-between px-4 border-b border-gray-800">
            <span v-if="!collapsed" class="text-white font-bold text-sm">Dental ERP</span>
            <button @click="$emit('toggle')" class="text-gray-400 hover:text-white p-1 rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-3 space-y-0.5 px-2">
            <template v-for="group in visibleGroups" :key="group.label">
                <p v-if="!collapsed && group.items.length" class="text-xs text-gray-500 uppercase px-2 pt-3 pb-1 tracking-wider">
                    {{ group.label }}
                </p>
                <NavItem v-for="item in group.items" :key="item.route" :item="item" :collapsed="collapsed" />
            </template>
        </nav>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import NavItem from './NavItem.vue';
import { usePermission } from '@/composables/usePermission';

defineProps({ collapsed: Boolean });
defineEmits(['toggle']);

const { hasPermission: can, hasAnyRole } = usePermission();

const navGroups = computed(() => [
    {
        label: 'Tổng quan',
        items: [
            { label: 'Dashboard', route: 'dashboard', icon: 'home', show: true },
        ],
    },
    {
        label: 'CRM & Khách hàng',
        items: [
            { label: 'Khách hàng', route: 'patients.index', icon: 'users', show: can('patients.view') },
            { label: 'Lead', route: 'crm.leads.index', icon: 'funnel', show: can('leads.view') },
            { label: 'Follow-up Tasks', route: 'crm.tasks.index', icon: 'clipboard', show: can('leads.view') },
        ],
    },
    {
        label: 'Lịch hẹn',
        items: [
            { label: 'Lịch hẹn', route: 'schedule.appointments.index', icon: 'calendar', show: can('appointments.view') },
        ],
    },
    {
        label: 'Điều trị',
        items: [
            { label: 'Kế hoạch điều trị', route: 'clinical.treatment-plans.index', icon: 'clipboard', show: can('treatment_plans.view') },
        ],
    },
    {
        label: 'Thu ngân',
        items: [
            { label: 'Hóa đơn', route: 'cashier.invoices.index', icon: 'receipt', show: can('cashier.view') },
            { label: 'Công nợ', route: 'cashier.debts.index', icon: 'chart', show: can('cashier.view') },
            { label: 'Phiếu chi', route: 'cashier.expenses.index', icon: 'receipt', show: can('expenses.view') },
        ],
    },
    {
        label: 'Báo cáo',
        items: [
            { label: 'Doanh thu', route: 'reports.revenue', icon: 'chart', show: can('reports.financial') },
            { label: 'Lịch hẹn', route: 'reports.appointments', icon: 'calendar', show: can('reports.view') },
            { label: 'Công nợ', route: 'reports.debt', icon: 'receipt', show: can('reports.financial') },
            { label: 'CRM & Lead', route: 'reports.crm', icon: 'funnel', show: can('reports.view') },
            { label: 'Lãi / Lỗ', route: 'reports.profit-loss', icon: 'chart', show: can('reports.financial') },
        ],
    },
    {
        label: 'Quản lý',
        items: [
            { label: 'Nhân viên', route: 'employees.index', icon: 'id-card', show: can('employees.view') },
            { label: 'Hoa hồng', route: 'hr.commissions.index', icon: 'chart', show: can('commissions.view') },
            { label: 'Chi nhánh', route: 'branches.index', icon: 'building', show: can('branches.view') },
            { label: 'Dịch vụ', route: 'catalog.services.index', icon: 'tooth', show: can('services.view') },
            { label: 'Bảng giá', route: 'catalog.price-lists.index', icon: 'receipt', show: can('services.view') },
            { label: 'Ghế nha', route: 'dental-chairs.index', icon: 'building', show: hasAnyRole('owner', 'admin') },
        ],
    },
    {
        label: 'Admin',
        items: [
            { label: 'Người dùng', route: 'admin.users.index', icon: 'users', show: can('admin.users') },
            { label: 'Vai trò', route: 'admin.roles.index', icon: 'id-card', show: can('admin.roles') },
            { label: 'Cấu hình', route: 'admin.settings.index', icon: 'settings', show: can('settings.view') },
            { label: 'Audit Log', route: 'admin.activity-log.index', icon: 'chart', show: can('admin.audit_log') },
        ],
    },
]);

const visibleGroups = computed(() =>
    navGroups.value.map(g => ({ ...g, items: g.items.filter(i => i.show !== false) }))
        .filter(g => g.items.length > 0)
);
</script>
