<template>
    <div class="tooth-chart select-none">
        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-3 mb-3 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300 inline-block"></span> Khỏe mạnh
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-primary-100 border border-primary-400 inline-block"></span> Đã chọn
            </span>
            <template v-if="showConditionLegend">
                <span v-for="(cfg, key) in CONDITION_COLORS" :key="key" class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm inline-block border"
                        :style="{ background: cfg.fill, borderColor: cfg.stroke }"></span>
                    {{ cfg.label }}
                </span>
            </template>
        </div>

        <!-- Upper jaw -->
        <div class="mb-1">
            <p class="text-xs text-gray-400 mb-1 text-center">Hàm trên</p>
            <div class="flex justify-center gap-0.5">
                <div class="flex gap-0.5">
                    <ToothButton v-for="n in upperRight" :key="n" :number="n"
                        :selected="selectedTeeth.includes(String(n))"
                        :condition="conditionMap[String(n)]"
                        @click="toggleTooth(String(n))" />
                </div>
                <div class="w-4 border-l border-dashed border-gray-300 mx-1"></div>
                <div class="flex gap-0.5">
                    <ToothButton v-for="n in upperLeft" :key="n" :number="n"
                        :selected="selectedTeeth.includes(String(n))"
                        :condition="conditionMap[String(n)]"
                        @click="toggleTooth(String(n))" />
                </div>
            </div>
        </div>

        <!-- Midline -->
        <div class="flex justify-center my-1">
            <div class="w-3/4 border-b-2 border-dashed border-gray-300"></div>
        </div>

        <!-- Lower jaw -->
        <div class="mt-1">
            <div class="flex justify-center gap-0.5">
                <div class="flex gap-0.5">
                    <ToothButton v-for="n in lowerRight" :key="n" :number="n"
                        :selected="selectedTeeth.includes(String(n))"
                        :condition="conditionMap[String(n)]"
                        @click="toggleTooth(String(n))" />
                </div>
                <div class="w-4 border-l border-dashed border-gray-300 mx-1"></div>
                <div class="flex gap-0.5">
                    <ToothButton v-for="n in lowerLeft" :key="n" :number="n"
                        :selected="selectedTeeth.includes(String(n))"
                        :condition="conditionMap[String(n)]"
                        @click="toggleTooth(String(n))" />
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-1 text-center">Hàm dưới</p>
        </div>

        <!-- Arch selectors -->
        <div class="flex justify-center gap-2 mt-3 flex-wrap">
            <button v-for="arch in archOptions" :key="arch.value" type="button"
                @click="selectArch(arch.value)"
                :class="['px-3 py-1 text-xs rounded-full border transition-colors',
                    selectedTeeth.includes(arch.value)
                        ? 'bg-primary-600 text-white border-primary-600'
                        : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']">
                {{ arch.label }}
            </button>
            <button type="button" @click="clearSelection"
                class="px-3 py-1 text-xs rounded-full border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">
                Xóa chọn
            </button>
        </div>

        <div v-if="selectedTeeth.length > 0" class="mt-3 p-2 bg-primary-50 rounded-lg text-xs text-primary-700">
            Đã chọn: <strong>{{ selectedTeeth.join(', ') }}</strong>
        </div>
    </div>
</template>

<script setup>
import { ref, defineComponent, h } from 'vue';

const CONDITION_COLORS = {
    caries:             { fill: '#fecaca', stroke: '#fb7185', label: 'Sâu răng' },
    filling:            { fill: '#dbeafe', stroke: '#60a5fa', label: 'Đã trám' },
    crown:              { fill: '#ede9fe', stroke: '#a78bfa', label: 'Mão sứ' },
    missing:            { fill: '#e5e7eb', stroke: '#9ca3af', label: 'Mất răng' },
    implant:            { fill: '#d1fae5', stroke: '#34d399', label: 'Implant' },
    root_canal:         { fill: '#fef3c7', stroke: '#fbbf24', label: 'Chữa tủy' },
    extraction_planned: { fill: '#fee2e2', stroke: '#ef4444', label: 'Dự kiến nhổ' },
    fractured:          { fill: '#ffedd5', stroke: '#f97316', label: 'Gãy/nứt' },
};

const props = defineProps({
    modelValue:         { type: Array, default: () => [] },
    // Map of tooth_number → condition string (e.g. { "16": "caries", "21": "crown" })
    conditionMap:       { type: Object, default: () => ({}) },
    // treatedTeeth kept for backwards compat with TreatmentPlan usage
    treatedTeeth:       { type: Array, default: () => [] },
    showConditionLegend:{ type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'select']);

const selectedTeeth = ref([...props.modelValue]);

const upperRight = [18, 17, 16, 15, 14, 13, 12, 11];
const upperLeft  = [21, 22, 23, 24, 25, 26, 27, 28];
const lowerLeft  = [31, 32, 33, 34, 35, 36, 37, 38];
const lowerRight = [48, 47, 46, 45, 44, 43, 42, 41];

const archOptions = [
    { value: 'upper', label: 'Toàn hàm trên' },
    { value: 'lower', label: 'Toàn hàm dưới' },
    { value: 'full',  label: 'Toàn bộ' },
];

function toggleTooth(n) {
    const idx = selectedTeeth.value.indexOf(n);
    if (idx > -1) {
        selectedTeeth.value.splice(idx, 1);
    } else {
        selectedTeeth.value = selectedTeeth.value.filter(t => !['upper', 'lower', 'full'].includes(t));
        selectedTeeth.value.push(n);
    }
    emit('update:modelValue', [...selectedTeeth.value]);
    emit('select', [...selectedTeeth.value]);
}

function selectArch(arch) {
    selectedTeeth.value = [arch];
    emit('update:modelValue', [arch]);
    emit('select', [arch]);
}

function clearSelection() {
    selectedTeeth.value = [];
    emit('update:modelValue', []);
    emit('select', []);
}

const ToothButton = defineComponent({
    props: { number: Number, selected: Boolean, treated: Boolean, condition: String },
    emits: ['click'],
    setup(props, { emit }) {
        return () => {
            const cond = props.condition ? CONDITION_COLORS[props.condition] : null;
            const fill   = props.selected ? '#0d9488'
                         : cond           ? cond.fill
                         : props.treated  ? '#f97316'
                         : '#e5e7eb';
            const stroke = props.selected ? '#0f766e'
                         : cond           ? cond.stroke
                         : props.treated  ? '#ea580c'
                         : '#9ca3af';

            return h('button', {
                type: 'button',
                onClick: () => emit('click'),
                title: `Răng ${props.number}${cond ? ' — ' + cond.label : ''}`,
                class: [
                    'w-8 h-10 rounded text-xs font-mono font-bold border-2 transition-all flex flex-col items-center justify-between p-0.5 hover:scale-110',
                    props.selected
                        ? 'border-primary-500 shadow-md scale-105'
                        : cond ? 'border-opacity-70' : 'border-gray-200 hover:border-gray-400',
                ],
                style: cond && !props.selected ? { borderColor: cond.stroke } : {},
            }, [
                h('span', { class: 'text-[9px] font-normal text-gray-400 leading-none' }, props.number),
                h('svg', { viewBox: '0 0 24 32', class: 'w-5 h-6', fill: 'none' }, [
                    h('path', {
                        d: 'M4 8 C4 4 8 2 12 2 C16 2 20 4 20 8 C20 14 18 20 16 26 C15 29 14 30 12 30 C10 30 9 29 8 26 C6 20 4 14 4 8 Z',
                        fill, stroke, 'stroke-width': '1.5',
                    }),
                    h('line', { x1: '12', y1: '22', x2: '10', y2: '30', stroke, 'stroke-width': '1' }),
                    h('line', { x1: '12', y1: '22', x2: '14', y2: '30', stroke, 'stroke-width': '1' }),
                ]),
            ]);
        };
    },
});
</script>

<style scoped>
.tooth-chart { font-size: 12px; }
</style>
