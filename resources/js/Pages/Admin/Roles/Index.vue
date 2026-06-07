<template>
    <AppLayout title="Vai trò hệ thống">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Vai trò & Quyền hạn</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="r in roles"
                    :key="r.id"
                    :href="route('admin.roles.show', r.id)"
                    class="bg-white rounded-xl border border-gray-200 p-5 hover:border-primary-300 hover:shadow-sm transition-all"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-semibold text-gray-800 capitalize">{{ r.name.replace('_', ' ') }}</span>
                        <StatusBadge color="teal">{{ r.permissions_count }} quyền</StatusBadge>
                    </div>
                    <p class="text-sm text-gray-500">{{ r.users_count }} người dùng</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';

defineProps({ roles: Array });
</script>
