<template>
    <AppLayout title="Tình trạng server">
        <div class="max-w-3xl space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Tình trạng server</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">{{ status?.server_time ?? '...' }}</span>
                    <button type="button" @click="fetchStatus" :disabled="loading"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50">
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
                        Làm mới
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div v-if="status" class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>CPU</span>
                            <span>{{ status.cpu.percent !== null ? status.cpu.percent + '%' : 'N/A' }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-500" :style="{ width: (status.cpu.percent ?? 0) + '%' }"></div>
                        </div>
                        <p v-if="status.cpu.load" class="text-xs text-gray-400 mt-1">
                            Load: {{ status.cpu.load.map(l => l.toFixed(2)).join(' / ') }} ({{ status.cpu.cores }} lõi)
                        </p>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>RAM</span>
                            <span>{{ status.memory ? status.memory.percent + '%' : 'N/A' }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500" :style="{ width: (status.memory?.percent ?? 0) + '%' }"></div>
                        </div>
                        <p v-if="status.memory" class="text-xs text-gray-400 mt-1">
                            {{ status.memory.used_mb }} / {{ status.memory.total_mb }} MB
                        </p>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Ổ đĩa</span>
                            <span>{{ status.disk.percent !== null ? status.disk.percent + '%' : 'N/A' }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500" :style="{ width: (status.disk.percent ?? 0) + '%' }"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ status.disk.used_gb }} / {{ status.disk.total_gb }} GB
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400">Đang tải...</p>

                <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                    <span>PHP {{ status?.php_version }}</span>
                    <span>Laravel {{ status?.laravel_version }}</span>
                    <span v-if="status?.uptime">Uptime: {{ formatUptime(status.uptime) }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';

const status = ref(null);
const loading = ref(false);
let statusTimer = null;

async function fetchStatus() {
    loading.value = true;
    try {
        const res = await fetch(route('admin.server-status.data'), {
            headers: { Accept: 'application/json' },
        });
        status.value = await res.json();
    } catch (e) {
        // Bỏ qua lỗi tạm thời, giữ dữ liệu cũ và thử lại ở lần refresh sau
    } finally {
        loading.value = false;
    }
}

function formatUptime(seconds) {
    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return `${d}d ${h}h ${m}m`;
}

onMounted(() => {
    fetchStatus();
    statusTimer = setInterval(fetchStatus, 10000);
});

onUnmounted(() => {
    clearInterval(statusTimer);
});
</script>
