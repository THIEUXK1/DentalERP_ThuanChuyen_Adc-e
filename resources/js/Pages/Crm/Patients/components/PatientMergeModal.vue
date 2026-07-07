<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                    <h3 class="text-base font-semibold text-gray-800">Gộp hồ sơ trùng lặp</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="overflow-y-auto flex-1 px-6 py-5">

                    <!-- Step 1: pick the duplicate -->
                    <div v-if="step === 'pick'" class="space-y-4">
                        <p class="text-sm text-gray-600">
                            Hồ sơ đang xem (<strong>{{ patient.full_name }}</strong> — {{ patient.code }}) sẽ được
                            <strong>giữ lại</strong>. Chọn hồ sơ trùng lặp cần gộp vào — hồ sơ đó sẽ bị ẩn sau khi gộp.
                        </p>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Hồ sơ trùng lặp</label>
                            <SearchableSelect v-model="loserId" :options="options" placeholder="-- Tìm theo tên, mã, SĐT --" />
                        </div>
                        <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ error }}</p>
                    </div>

                    <!-- Step 2: preview & confirm -->
                    <div v-else-if="step === 'preview' && preview" class="space-y-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800 flex gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            Thao tác này không thể hoàn tác qua giao diện. Kiểm tra kỹ trước khi xác nhận.
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="border border-emerald-200 bg-emerald-50 rounded-lg p-3">
                                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide mb-1">Giữ lại</p>
                                <p class="font-semibold text-gray-900">{{ preview.survivor.full_name }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ preview.survivor.code }} · {{ preview.survivor.phone || '—' }}</p>
                            </div>
                            <div class="border border-red-200 bg-red-50 rounded-lg p-3">
                                <p class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1">Sẽ bị gộp (ẩn)</p>
                                <p class="font-semibold text-gray-900">{{ preview.loser.full_name }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ preview.loser.code }} · {{ preview.loser.phone || '—' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-600 mb-1.5">Dữ liệu sẽ chuyển sang hồ sơ giữ lại</p>
                            <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-1.5 text-left font-medium">Loại dữ liệu</th>
                                        <th class="px-3 py-1.5 text-right font-medium">Giữ lại</th>
                                        <th class="px-3 py-1.5 text-right font-medium">Bị gộp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(row, key) in preview.counts" :key="key">
                                        <td class="px-3 py-1.5 text-gray-700">{{ row.label }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ row.survivor }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums font-semibold" :class="row.loser > 0 ? 'text-red-600' : 'text-gray-300'">{{ row.loser }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="Object.keys(preview.field_diffs).length" class="text-xs text-gray-600">
                            <p class="font-medium text-gray-700 mb-1">Sẽ điền thêm các trường đang trống:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li v-for="(diff, field) in preview.field_diffs" :key="field">
                                    {{ FIELD_LABELS[field] ?? field }}: <strong>{{ diff.loser }}</strong>
                                </li>
                            </ul>
                        </div>

                        <p v-if="preview.extra_phones.length" class="text-xs text-gray-600">
                            Sẽ thêm số điện thoại phụ: <strong>{{ preview.extra_phones.join(', ') }}</strong>
                        </p>
                        <p v-if="preview.notes_will_append" class="text-xs text-gray-600">Ghi chú của 2 hồ sơ sẽ được nối lại.</p>

                        <p v-if="form.errors.loser_id" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ form.errors.loser_id }}</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button v-if="step === 'preview'" @click="step = 'pick'"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Quay lại
                    </button>
                    <button v-else @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Hủy
                    </button>
                    <button v-if="step === 'pick'" @click="loadPreview" :disabled="!loserId || loading"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                        {{ loading ? 'Đang tải...' : 'Xem trước' }}
                    </button>
                    <button v-else @click="confirmMerge" :disabled="form.processing"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 font-medium">
                        {{ form.processing ? 'Đang gộp...' : 'Xác nhận gộp' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';

const props = defineProps({
    patient:     Object,
    allPatients: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const FIELD_LABELS = {
    address: 'Địa chỉ', email: 'Email', dob: 'Ngày sinh', gender: 'Giới tính',
    allergies: 'Dị ứng', medical_history: 'Bệnh lý khác', emergency_contact: 'Người liên hệ khẩn cấp',
    source: 'Nguồn khách', branch_id: 'Chi nhánh',
};

const options = computed(() => props.allPatients.map(p => ({
    value: p.id, label: `${p.name} (${p.code})`, sublabel: p.phone,
})));

const step    = ref('pick');
const loserId = ref(null);
const preview = ref(null);
const loading = ref(false);
const error   = ref('');

async function loadPreview() {
    if (!loserId.value) return;
    loading.value = true;
    error.value = '';
    try {
        const res = await fetch(route('patients.merge-preview', props.patient.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ loser_id: loserId.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            error.value = data.error ?? 'Không thể tải xem trước.';
            return;
        }
        preview.value = data;
        step.value = 'preview';
    } catch {
        error.value = 'Không thể tải xem trước. Vui lòng thử lại.';
    } finally {
        loading.value = false;
    }
}

const form = useForm({ loser_id: null });
function confirmMerge() {
    form.loser_id = loserId.value;
    form.post(route('patients.merge', props.patient.id), {
        onSuccess: () => emit('close'),
    });
}
</script>
