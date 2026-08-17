<template>
    <div class="flex-shrink-0 flex items-center justify-between gap-3 flex-wrap bg-white border border-gray-200 rounded-xl px-3 py-2">
        <p class="text-xs text-gray-500">
            Hiển thị <span class="font-semibold text-gray-700">{{ from }}–{{ to }}</span>
            / {{ total }} bản ghi
            <span v-if="totalPages > 1" class="text-gray-400">· trang {{ page }}/{{ totalPages }}</span>
        </p>
        <div v-if="totalPages > 1" class="flex items-center gap-1">
            <button @click="go(1)" :disabled="page === 1" :class="BTN">«</button>
            <button @click="go(page - 1)" :disabled="page === 1" :class="BTN">‹</button>
            <template v-for="(p, i) in pages" :key="`${p}-${i}`">
                <span v-if="p === '...'" class="px-2 py-1.5 text-xs text-gray-400">…</span>
                <button v-else @click="go(p)"
                    :class="['px-3 py-1.5 text-xs border rounded-lg',
                        p === page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50']">
                    {{ p }}
                </button>
            </template>
            <button @click="go(page + 1)" :disabled="page === totalPages" :class="BTN">›</button>
            <button @click="go(totalPages)" :disabled="page === totalPages" :class="BTN">»</button>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    page:       { type: Number, required: true },
    totalPages: { type: Number, default: 1 },
    pages:      { type: Array, default: () => [] },
    from:       { type: Number, default: 0 },
    to:         { type: Number, default: 0 },
    total:      { type: Number, default: 0 },
});
const emit = defineEmits(['update:page']);

const BTN = 'px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40';

function go(p) {
    const target = Math.min(Math.max(1, p), props.totalPages);
    if (target !== props.page) emit('update:page', target);
}
</script>
