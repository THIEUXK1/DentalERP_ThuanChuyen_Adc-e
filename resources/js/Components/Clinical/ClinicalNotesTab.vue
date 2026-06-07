<template>
    <div class="space-y-4">
        <!-- Add note button -->
        <div v-if="canCreate" class="flex justify-end">
            <button @click="showForm = !showForm"
                class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                {{ showForm ? 'Đóng form' : '+ Ghi hồ sơ lâm sàng' }}
            </button>
        </div>

        <!-- Add form -->
        <div v-if="showForm && canCreate" class="bg-white rounded-xl border border-primary-200 p-4 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">Ghi hồ sơ lâm sàng</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Bác sĩ</label>
                    <select v-model="form.doctor_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">-- Chọn bác sĩ --</option>
                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Lý do đến khám</label>
                    <input v-model="form.chief_complaint" type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Chẩn đoán</label>
                    <textarea v-model="form.diagnosis" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Điều trị đã thực hiện</label>
                    <textarea v-model="form.treatment_done" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Đơn thuốc / Hướng dẫn</label>
                    <textarea v-model="form.prescription" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Dặn dò tái khám</label>
                    <textarea v-model="form.next_visit_notes" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="showForm = false; resetForm()"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Hủy</button>
                <button @click="submit" :disabled="submitting"
                    class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                    Lưu hồ sơ
                </button>
            </div>
        </div>

        <!-- Notes list -->
        <div v-if="clinicalNotes.length === 0 && !showForm"
            class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400 text-sm">
            Chưa có hồ sơ lâm sàng nào
        </div>
        <div v-for="note in clinicalNotes" :key="note.id"
            class="bg-white rounded-xl border border-gray-200 p-4 space-y-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-800">{{ note.created_at }}</span>
                    <span v-if="note.doctor_name" class="text-xs text-gray-500">· {{ note.doctor_name }}</span>
                </div>
                <button v-if="canCreate" @click="deleteNote(note)"
                    class="text-xs text-gray-300 hover:text-red-500">✕</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">
                <NoteRow label="Lý do khám" :value="note.chief_complaint" />
                <NoteRow label="Chẩn đoán" :value="note.diagnosis" />
                <NoteRow label="Điều trị" :value="note.treatment_done" />
                <NoteRow label="Đơn thuốc" :value="note.prescription" />
                <NoteRow v-if="note.next_visit_notes" label="Dặn dò" :value="note.next_visit_notes" class="col-span-2" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, defineComponent, h } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    patient:       Object,
    clinicalNotes: { type: Array, default: () => [] },
    doctors:       { type: Array, default: () => [] },
    canCreate:     { type: Boolean, default: false },
});

const showForm  = ref(false);
const submitting = ref(false);
const form      = ref({ doctor_id: '', chief_complaint: '', diagnosis: '', treatment_done: '', prescription: '', next_visit_notes: '' });

function resetForm() {
    form.value = { doctor_id: '', chief_complaint: '', diagnosis: '', treatment_done: '', prescription: '', next_visit_notes: '' };
}

function submit() {
    submitting.value = true;
    router.post(route('clinical-notes.store', props.patient.id), form.value, {
        onSuccess: () => { showForm.value = false; resetForm(); },
        onFinish:  () => { submitting.value = false; },
    });
}

function deleteNote(note) {
    if (!confirm('Xóa hồ sơ lâm sàng này?')) return;
    router.delete(route('clinical-notes.destroy', note.id));
}

const NoteRow = defineComponent({
    props: { label: String, value: String },
    setup(props) {
        return () => props.value
            ? h('div', { class: 'flex gap-2' }, [
                h('span', { class: 'text-gray-400 min-w-24 flex-shrink-0 text-xs' }, props.label + ':'),
                h('span', { class: 'text-gray-700 whitespace-pre-line' }, props.value),
              ])
            : null;
    },
});
</script>
