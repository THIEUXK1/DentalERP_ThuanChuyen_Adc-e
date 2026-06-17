<template>
    <aside :class="['fixed inset-y-0 left-0 z-30 flex flex-col bg-gray-900 transition-all duration-200 border-r border-gray-800/50', collapsed ? 'w-16' : 'w-60']">
        <!-- Logo -->
        <div class="flex h-16 items-center justify-between px-4 border-b border-gray-800">
            <span v-if="!collapsed" class="text-white font-bold text-sm tracking-wider uppercase bg-gradient-to-r from-primary-400 to-teal-200 bg-clip-text text-transparent">Dental ERP</span>
            <button @click="$emit('toggle')" class="text-gray-400 hover:text-white p-1.5 rounded hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-3 space-y-1 px-2 scrollbar-thin scrollbar-thumb-gray-800">
            <template v-for="group in visibleGroups" :key="group.label">
                <p v-if="!collapsed && group.items.length" class="text-[10px] text-gray-500 font-bold uppercase px-3 pt-4 pb-1.5 tracking-widest select-none">
                    {{ group.label }}
                </p>
                <div class="space-y-0.5">
                    <NavItem v-for="item in group.items" :key="item.label" :item="item" :collapsed="collapsed" />
                </div>
            </template>
        </nav>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NavItem from './NavItem.vue';
import { usePermission } from '@/composables/usePermission';
import { menuConfig } from './menuConfig';

defineProps({ collapsed: Boolean });
defineEmits(['toggle']);

const { hasPermission: can, hasAnyRole } = usePermission();
const page = usePage();
const isHkd = computed(() => (page.props.app?.accounting_regime ?? 'TT152_HKD') === 'TT152_HKD');

const navGroups = computed(() => {
    return menuConfig.map(group => {
        // Kiểm tra điều kiện hiển thị của nhóm
        let showGroup = true;
        if (group.condition === 'isHkd') {
            showGroup = isHkd.value;
        }

        // Lọc các items dựa trên permission hoặc role
        const items = group.items.map(item => {
            let showItem = true;

            if (item.children) {
                // Nếu có con, duyệt qua các con và gán quyền hiển thị cho từng con
                const children = item.children.map(child => {
                    let showChild = true;
                    if (child.permission) {
                        showChild = can(child.permission);
                    } else if (child.roles) {
                        showChild = hasAnyRole(...child.roles);
                    }
                    return { ...child, show: showChild };
                });

                // Chỉ hiển thị menu cha nếu có ít nhất 1 menu con được phép hiển thị
                const visibleChildren = children.filter(c => c.show !== false);
                showItem = visibleChildren.length > 0;
                return { ...item, children, show: showItem };
            } else {
                // Logic thông thường cho item phẳng
                if (item.permission) {
                    showItem = can(item.permission);
                } else if (item.roles) {
                    showItem = hasAnyRole(...item.roles);
                }
                return { ...item, show: showItem };
            }
        });

        return { ...group, items, show: showGroup };
    });
});

const visibleGroups = computed(() =>
    navGroups.value
        .filter(g => g.show !== false)
        .map(g => ({ ...g, items: g.items.filter(i => i.show !== false) }))
        .filter(g => g.items.length > 0)
);
</script>
