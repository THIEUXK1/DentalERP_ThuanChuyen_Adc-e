<template>
    <div v-if="item.show !== false" class="w-full">
        <!-- 1. Menu Item KHÔNG CÓ children -->
        <Link
            v-if="!item.children"
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

        <!-- 2. Menu Item CÓ children (Submenu) -->
        <div v-else class="space-y-0.5">
            <button
                @click="toggleOpen"
                :class="[
                    'w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-all duration-150 select-none text-left',
                    isChildActive
                        ? 'text-white font-semibold'
                        : 'text-gray-400 hover:text-white hover:bg-gray-800',
                ]"
                :title="collapsed ? item.label : undefined"
            >
                <div class="flex items-center gap-3">
                    <component :is="iconComponent" class="w-5 h-5 flex-shrink-0" stroke-width="2" />
                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                </div>
                <ChevronDownIcon
                    v-if="!collapsed && filteredChildren.length"
                    :class="['w-4 h-4 transition-transform duration-200 text-gray-500', isOpen ? 'rotate-180 text-white' : '']"
                />
            </button>

            <!-- Submenu list -->
            <div v-if="isOpen && !collapsed && filteredChildren.length" class="pl-5 pr-1 py-1 space-y-0.5 border-l border-gray-800 ml-5 my-1">
                <Link
                    v-for="child in filteredChildren"
                    :key="child.route"
                    :href="getSafeRoute(child.route)"
                    :class="[
                        'block py-1.5 px-3 rounded-md text-[12px] transition-all duration-150 truncate',
                        isRouteActive(child.route)
                            ? 'bg-primary-500/10 text-primary-400 font-semibold border-l-2 border-primary-500 rounded-l-none'
                            : 'text-gray-500 hover:text-gray-200 hover:bg-gray-800/50',
                    ]"
                >
                    {{ child.label }}
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
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
    TagIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    item:      { type: Object, required: true },
    collapsed: { type: Boolean, default: false },
});

const page = usePage();

const isOpen = ref(false);

const toggleOpen = () => {
    isOpen.value = !isOpen.value;
};

// Check if a child route is active
const isRouteActive = (routeName) => {
    try {
        const routePath = new URL(route(routeName), window.location.origin).pathname;
        return page.url.startsWith(routePath);
    } catch {
        return false;
    }
};

const safeRoute = computed(() => {
    try { return route(props.item.route); } catch { return '#'; }
});

const getSafeRoute = (routeName) => {
    try { return route(routeName); } catch { return '#'; }
};

const isActive = computed(() => {
    try { return page.url.startsWith(new URL(route(props.item.route), window.location.origin).pathname); }
    catch { return false; }
});

// Filter child items based on pre-calculated permissions
const filteredChildren = computed(() => {
    if (!props.item.children) return [];
    return props.item.children.filter(child => child.show !== false);
});

const isChildActive = computed(() => {
    if (!filteredChildren.value.length) return false;
    return filteredChildren.value.some(child => isRouteActive(child.route));
});

// Auto-expand if a child item is active
watch(isChildActive, (active) => {
    if (active) {
        isOpen.value = true;
    }
}, { immediate: true });

// Auto-close submenus when sidebar collapses
watch(() => props.collapsed, (collapsed) => {
    if (collapsed) {
        isOpen.value = false;
    } else if (isChildActive.value) {
        isOpen.value = true;
    }
});

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
