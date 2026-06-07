<template>
    <div ref="rootEl" class="relative">
        <!-- Trigger -->
        <div
            @click="toggle"
            :class="[
                'flex items-center gap-2 w-full rounded-lg border px-3 py-2 text-sm cursor-pointer transition-colors',
                isOpen
                    ? 'border-primary-500 ring-2 ring-primary-500 ring-opacity-30'
                    : 'border-gray-300 hover:border-gray-400',
                disabled ? 'bg-gray-50 cursor-not-allowed opacity-60' : 'bg-white',
            ]"
        >
            <span v-if="!isOpen" class="flex-1 truncate" :class="selectedOption ? 'text-gray-900' : 'text-gray-400'">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>
            <input
                v-else
                ref="inputEl"
                v-model="query"
                @keydown="onKeydown"
                :placeholder="selectedOption?.label ?? placeholder"
                class="flex-1 outline-none bg-transparent text-sm text-gray-900 placeholder-gray-400"
                autocomplete="off"
            />
            <button v-if="modelValue && !disabled && !isOpen" type="button"
                @click.stop="clear"
                class="text-gray-300 hover:text-gray-500 flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <svg v-else class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-150"
                :class="isOpen ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        <!-- Dropdown -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                :style="dropdownStyle"
                class="fixed z-[200] bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
            >
                <div class="max-h-60 overflow-y-auto">
                    <div v-if="filtered.length === 0"
                        class="px-3 py-3 text-sm text-gray-400 text-center">
                        Không tìm thấy kết quả
                    </div>
                    <button
                        v-for="(opt, idx) in filtered"
                        :key="opt.value"
                        type="button"
                        @mousedown.prevent="select(opt)"
                        :class="[
                            'w-full text-left px-3 py-2 text-sm transition-colors',
                            opt.value == modelValue
                                ? 'bg-primary-50 text-primary-700'
                                : highlighted === idx
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'hover:bg-gray-50 text-gray-700',
                        ]"
                    >
                        <div class="font-medium truncate">{{ opt.label }}</div>
                        <div v-if="opt.sublabel" class="text-xs text-gray-400 truncate">{{ opt.sublabel }}</div>
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue:  { default: null },
    options:     { type: Array, default: () => [] }, // [{ value, label, sublabel? }]
    placeholder: { type: String, default: '-- Chọn --' },
    disabled:    { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const rootEl     = ref(null);
const inputEl    = ref(null);
const isOpen     = ref(false);
const query      = ref('');
const highlighted = ref(0);
const dropdownStyle = ref({});

const selectedOption = computed(() =>
    props.options.find(o => o.value == props.modelValue) ?? null
);

const filtered = computed(() => {
    const q = query.value.toLowerCase().trim();
    if (!q) return props.options;
    return props.options.filter(o =>
        o.label.toLowerCase().includes(q) ||
        o.sublabel?.toLowerCase().includes(q)
    );
});

function positionDropdown() {
    const rect = rootEl.value?.getBoundingClientRect();
    if (!rect) return;
    const spaceBelow = window.innerHeight - rect.bottom;
    const dropHeight = Math.min(filtered.value.length * 40 + 8, 248);
    const openUp = spaceBelow < dropHeight && rect.top > dropHeight;
    dropdownStyle.value = {
        width: `${rect.width}px`,
        left:  `${rect.left + window.scrollX}px`,
        ...(openUp
            ? { bottom: `${window.innerHeight - rect.top - window.scrollY}px` }
            : { top:    `${rect.bottom + window.scrollY + 4}px` }),
    };
}

function open() {
    if (props.disabled) return;
    isOpen.value = true;
    query.value  = '';
    highlighted.value = Math.max(0, filtered.value.findIndex(o => o.value == props.modelValue));
    nextTick(() => { positionDropdown(); inputEl.value?.focus(); });
}

function close() {
    isOpen.value = false;
    query.value  = '';
}

function toggle() {
    isOpen.value ? close() : open();
}

function select(opt) {
    emit('update:modelValue', opt.value);
    close();
}

function clear() {
    emit('update:modelValue', null);
}

function onKeydown(e) {
    if (e.key === 'Escape')     { close(); return; }
    if (e.key === 'ArrowDown')  { e.preventDefault(); highlighted.value = Math.min(highlighted.value + 1, filtered.value.length - 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); highlighted.value = Math.max(highlighted.value - 1, 0); }
    else if (e.key === 'Enter') {
        e.preventDefault();
        if (filtered.value[highlighted.value]) select(filtered.value[highlighted.value]);
    }
}

watch(query, () => { highlighted.value = 0; });

function onOutsideClick(e) {
    if (rootEl.value && !rootEl.value.contains(e.target)) close();
}

watch(isOpen, v => {
    if (v) document.addEventListener('mousedown', onOutsideClick);
    else   document.removeEventListener('mousedown', onOutsideClick);
});

onBeforeUnmount(() => document.removeEventListener('mousedown', onOutsideClick));
</script>
