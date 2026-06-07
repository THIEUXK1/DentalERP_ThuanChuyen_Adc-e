<template>
    <AppLayout title="Thông báo">
        <div class="max-w-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Thông báo</h2>
                <button v-if="unreadCount > 0" @click="markAllRead"
                    class="text-sm text-primary-600 hover:text-primary-800">Đọc tất cả</button>
            </div>

            <div class="space-y-2">
                <div v-if="notifications.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                    Không có thông báo
                </div>
                <div v-for="n in notifications.data" :key="n.id"
                    :class="['bg-white rounded-xl border p-4 flex gap-3 transition-colors', n.is_read ? 'border-gray-100' : 'border-primary-200 bg-primary-50/30']">
                    <StatusBadge :color="n.type_color" class="flex-shrink-0 mt-0.5">{{ n.type_label }}</StatusBadge>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800">{{ n.title }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">{{ n.message }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ n.created_at }}</p>
                    </div>
                    <div class="flex-shrink-0 flex items-start gap-2">
                        <a v-if="n.link" :href="n.link" class="text-xs text-primary-600 hover:text-primary-800">Xem</a>
                        <button v-if="!n.is_read" @click="markRead(n.id)"
                            class="text-xs text-gray-400 hover:text-gray-600">✓</button>
                    </div>
                </div>
            </div>
            <Pagination :links="notifications.links" :meta="notifications.meta" @navigate="url => router.get(url)" />
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Pagination from '@/Components/Shared/Pagination.vue';

const props = defineProps({ notifications: Object, unreadCount: Number });

function markRead(id) {
    router.post(route('notifications.read', id));
}

function markAllRead() {
    router.post(route('notifications.read-all'));
}
</script>
