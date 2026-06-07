<template>
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <ToothChart
                v-model="selectedTeeth"
                :conditionMap="conditionMap"
                :showConditionLegend="true"
                @select="onToothSelect"
            />
        </div>

        <!-- Condition list -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Tình trạng răng đã ghi nhận</h3>
                <button v-if="canEdit" @click="showForm = !showForm"
                    class="text-xs text-primary-600 hover:text-primary-800">
                    {{ showForm ? 'Đóng' : '+ Ghi nhận tình trạng' }}
                </button>
            </div>

            <!-- Add/edit form -->
            <div v-if="showForm && canEdit" class="mb-4 p-3 bg-gray-50 rounded-lg space-y-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Số răng (FDI)</label>
                        <input v-model="form.tooth_number" type="text" placeholder="VD: 16"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Tình trạng</label>
                        <select v-model="form.condition"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="c in conditionTypes" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                        <input v-model="form.note" type="text" placeholder="Ghi chú thêm..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="showForm = false; resetForm()"
                        class="px-3 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                    <button @click="submitCondition" :disabled="!form.tooth_number || !form.condition"
                        class="px-3 py-1 text-xs text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                        Lưu tình trạng
                    </button>
                </div>
            </div>

            <!-- Conditions table -->
            <div v-if="toothConditions.length === 0 && !showForm" class="text-sm text-gray-400 text-center py-4">
                Chưa ghi nhận tình trạng răng nào
            </div>
            <div v-else class="space-y-1">
                <div v-for="tc in toothConditions" :key="tc.id"
                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 text-sm">
                    <span class="font-mono font-bold text-gray-700 w-8 text-center">{{ tc.tooth_number }}</span>
                    <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-${tc.condition_color}-100 text-${tc.condition_color}-700`">
                        {{ tc.condition_label }}
                    </span>
                    <span class="flex-1 text-gray-500 text-xs">{{ tc.note }}</span>
                    <button v-if="canEdit" @click="deleteCondition(tc)"
                        class="text-gray-300 hover:text-red-500 text-xs">✕</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ToothChart from '@/Components/Shared/ToothChart.vue';

const props = defineProps({
    patient:        Object,
    toothConditions:{ type: Array, default: () => [] },
    conditionTypes: { type: Array, default: () => [] },
    canEdit:        { type: Boolean, default: false },
});

const selectedTeeth = ref([]);
const showForm      = ref(false);
const form          = ref({ tooth_number: '', condition: '', note: '' });

const conditionMap = computed(() => {
    const map = {};
    props.toothConditions.forEach(tc => { map[tc.tooth_number] = tc.condition; });
    return map;
});

function onToothSelect(teeth) {
    const last = teeth[teeth.length - 1];
    if (last && !['upper', 'lower', 'full'].includes(last)) {
        form.value.tooth_number = last;
        showForm.value = true;
    }
}

function resetForm() {
    form.value = { tooth_number: '', condition: '', note: '' };
}

function submitCondition() {
    router.post(route('tooth-conditions.upsert', props.patient.id), form.value, {
        onSuccess: () => { showForm.value = false; resetForm(); selectedTeeth.value = []; },
    });
}

function deleteCondition(tc) {
    if (!confirm(`Xóa tình trạng răng ${tc.tooth_number}?`)) return;
    router.delete(route('tooth-conditions.destroy', { patient: props.patient.id, condition: tc.id }));
}
</script>
