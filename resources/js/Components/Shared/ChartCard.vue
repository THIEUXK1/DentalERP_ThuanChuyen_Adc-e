<template>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ title }}</h3>
        <div :style="{ height: height + 'px' }">
            <canvas ref="canvasRef"></canvas>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    title:   { type: String, required: true },
    type:    { type: String, default: 'line' },
    data:    { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    height:  { type: Number, default: 200 },
});

const canvasRef = ref(null);
let chart = null;

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 11 } } } },
};

onMounted(() => {
    chart = new Chart(canvasRef.value, {
        type: props.type,
        data: props.data,
        options: { ...defaultOptions, ...props.options },
    });
});

onUnmounted(() => { chart?.destroy(); });

watch(() => props.data, (newData) => {
    if (chart) {
        chart.data = newData;
        chart.update();
    }
}, { deep: true });
</script>
