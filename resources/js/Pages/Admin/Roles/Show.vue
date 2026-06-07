<template>
    <AppLayout :title="`Quyền: ${role.name}`">
        <div class="max-w-4xl space-y-4">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.roles.index')" class="text-gray-400 hover:text-gray-600">← Quay lại</Link>
                <h2 class="text-lg font-semibold text-gray-800 capitalize">{{ role.name.replace('_', ' ') }}</h2>
            </div>

            <div v-for="(perms, group) in permissions" :key="group"
                class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ group }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <div v-for="perm in perms" :key="perm.id"
                        class="flex items-center gap-2 text-sm">
                        <span :class="perm.granted ? 'text-green-500' : 'text-gray-300'">
                            {{ perm.granted ? '✓' : '✗' }}
                        </span>
                        <span :class="perm.granted ? 'text-gray-700' : 'text-gray-400'">
                            {{ perm.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

defineProps({ role: Object, permissions: Object });
</script>
