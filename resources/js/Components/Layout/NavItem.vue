<template>
    <Link
        v-if="item.show !== false"
        :href="safeRoute"
        :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150',
            isActive
                ? 'bg-primary-600 text-white font-medium shadow-sm shadow-primary-900/30'
                : 'text-gray-400 hover:text-white hover:bg-gray-800',
        ]"
        :title="collapsed ? item.label : undefined"
    >
        <component :is="iconComponent" class="w-5 h-5 flex-shrink-0" stroke-width="2" />
        <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    UsersIcon,
    UserPlusIcon,
    ClipboardDocumentCheckIcon,
    ChatBubbleBottomCenterTextIcon,
    ChatBubbleLeftRightIcon,
    ArrowPathIcon,
    CalendarDaysIcon,
    ClipboardDocumentListIcon,
    PencilSquareIcon,
    HeartIcon,
    PresentationChartLineIcon,
    BookOpenIcon,
    DocumentTextIcon,
    CreditCardIcon,
    BanknotesIcon,
    ArrowTrendingUpIcon,
    CalendarIcon,
    DocumentChartBarIcon,
    FunnelIcon,
    PresentationChartBarIcon,
    ArrowsRightLeftIcon,
    ChartBarIcon,
    IdentificationIcon,
    BriefcaseIcon,
    FingerPrintIcon,
    ClockIcon,
    TableCellsIcon,
    ClipboardDocumentIcon,
    ArrowRightOnRectangleIcon,
    ReceiptPercentIcon,
    GiftIcon,
    StarIcon,
    CubeIcon,
    ArrowDownTrayIcon,
    LockClosedIcon,
    BuildingOffice2Icon,
    UserGroupIcon,
    WrenchIcon,
    BeakerIcon,
    ShieldCheckIcon,
    Cog6ToothIcon,
    ArrowTrendingDownIcon,
    SparklesIcon,
    TagIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    item:      { type: Object, required: true },
    collapsed: { type: Boolean, default: false },
});

const page = usePage();

const safeRoute = computed(() => {
    try { return route(props.item.route); } catch { return '#'; }
});

const isActive = computed(() => {
    try { return page.url.startsWith(new URL(route(props.item.route), window.location.origin).pathname); }
    catch { return false; }
});

const icons = {
    'home': homeIconMap(), // wait, we can just map to component directly
};

// Map icon string identifier to Vue component
const iconMap = {
    'home': HomeIcon,
    'users': UsersIcon,
    'lead': UserPlusIcon,
    'follow-up': ClipboardDocumentCheckIcon,
    'message-template': ChatBubbleBottomCenterTextIcon,
    'message-log': ChatBubbleLeftRightIcon,
    'care-rules': ArrowPathIcon,
    'appointment': CalendarDaysIcon,
    'treatment-plan': ClipboardDocumentListIcon,
    'clinical-template': PencilSquareIcon,
    'examination': HeartIcon,
    'workflow-step': ArrowPathIcon,
    'kpi': PresentationChartLineIcon,
    'conditions': BookOpenIcon,
    'invoice': DocumentTextIcon,
    'debt': CreditCardIcon,
    'expense': BanknotesIcon,
    'report-revenue': ArrowTrendingUpIcon,
    'report-appointment': CalendarIcon,
    'report-debt': DocumentChartBarIcon,
    'report-crm': FunnelIcon,
    'report-profit-loss': PresentationChartBarIcon,
    'report-cashflow': ArrowsRightLeftIcon,
    'report-performance': ChartBarIcon,
    'employee': IdentificationIcon,
    'contract': BriefcaseIcon,
    'attendance-device': FingerPrintIcon,
    'work-shift': ClockIcon,
    'attendance-table': TableCellsIcon,
    'timesheet': ClipboardDocumentIcon,
    'leave': ArrowRightOnRectangleIcon,
    'salary-slip': ReceiptPercentIcon,
    'commission': GiftIcon,
    'review': StarIcon,
    'kpi-employee': PresentationChartLineIcon,
    'fixed-asset': CubeIcon,
    'payroll': BanknotesIcon,
    'supplier': UsersIcon,
    'purchase-invoice': ReceiptPercentIcon,
    'fund-transfer': ArrowsRightLeftIcon,
    'report-tax': ReceiptPercentIcon,
    'general-ledger': TableCellsIcon,
    'hkd-profile': IdentificationIcon,
    'hkd-revenue': ArrowTrendingUpIcon,
    'hkd-expense': ArrowTrendingDownIcon,
    'hkd-inventory': CubeIcon,
    'hkd-cash': CreditCardIcon,
    'hkd-tax': ReceiptPercentIcon,
    'hkd-period': LockClosedIcon,
    'hkd-report': ArrowDownTrayIcon,
    'branch': BuildingOffice2Icon,
    'department': UserGroupIcon,
    'fund': BanknotesIcon,
    'service': SparklesIcon,
    'price-list': TagIcon,
    'dental-chair': WrenchIcon,
    'lab': BeakerIcon,
    'lab-order': ClipboardDocumentIcon,
    'warranty': ShieldCheckIcon,
    'lab-payable': CreditCardIcon,
    'role': ShieldCheckIcon,
    'settings': Cog6ToothIcon,
    'audit-log': ClockIcon
};

const iconComponent = computed(() => {
    return iconMap[props.item.icon] || HomeIcon;
});
</script>
