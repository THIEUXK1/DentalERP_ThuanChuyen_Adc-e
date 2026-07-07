<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                    <h3 class="text-base font-semibold text-gray-800">Sửa hồ sơ — {{ patient.full_name }}</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body (scrollable) -->
                <div class="overflow-y-auto flex-1 px-6 py-5">
                    <form @submit.prevent="doSave" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Họ tên <span class="text-red-500">*</span></label>
                                <input v-model="form.full_name" type="text"
                                    @input="form.full_name = toTitleCase(form.full_name)"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                                    :class="{'border-red-400': form.errors.full_name}" />
                                <p v-if="form.errors.full_name" class="mt-0.5 text-xs text-red-500">{{ form.errors.full_name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại</label>
                                <input v-model="form.phone" type="tel" placeholder="0912345678"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                                    :class="{'border-red-400': form.errors.phone}" />
                                <p v-if="form.errors.phone" class="mt-0.5 text-xs text-red-500">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                <input v-model="form.email" type="email"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ngày sinh</label>
                                <input v-model="form.dob" type="date"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Giới tính</label>
                                <select v-model="form.gender" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                    <option value="">-- Chọn --</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nguồn khách</label>
                                <select v-model="form.source" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                    <option value="">-- Chọn --</option>
                                    <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Chi nhánh</label>
                                <select v-model="form.branch_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                    <option :value="null">-- Chọn --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Người liên hệ khẩn cấp</label>
                                <input v-model="form.emergency_contact" type="text" placeholder="Tên — SĐT"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Địa chỉ</label>
                            <input v-model="form.address" type="text"
                                @input="form.address = toTitleCase(form.address)"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none" />
                        </div>

                        <!-- Medical flags -->
                        <div class="border-t pt-3">
                            <p class="text-xs font-medium text-gray-600 mb-2">Tiểu sử bệnh</p>
                            <div class="flex flex-wrap gap-1.5">
                                <label v-for="flag in MEDICAL_FLAGS" :key="flag.key"
                                    :class="['flex items-center gap-1 px-2.5 py-1 rounded-lg border text-xs cursor-pointer transition-colors select-none',
                                        form.medical_flags.includes(flag.key)
                                            ? 'bg-rose-50 border-rose-300 text-rose-700 font-medium'
                                            : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300']">
                                    <input type="checkbox" :value="flag.key" v-model="form.medical_flags" class="hidden" />
                                    {{ flag.label }}
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Dị ứng (chi tiết)</label>
                                <textarea v-model="form.allergies" rows="2" placeholder="Liệt kê các dị ứng cụ thể..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none resize-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú bệnh lý khác</label>
                                <textarea v-model="form.medical_history" rows="2" placeholder="Bệnh lý khác cần lưu ý..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none resize-none" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea v-model="form.notes" rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none resize-none" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="is_active_edit" v-model="form.is_active" type="checkbox"
                                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                            <label for="is_active_edit" class="text-sm text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Hủy
                    </button>
                    <button @click="doSave" :disabled="form.processing"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium flex items-center gap-1.5">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    patient: Object, branches: Array, sources: Array,
    // When opened from a page that should stay put after saving (e.g. the patients list),
    // ask the server to redirect back here instead of to the patient's show page.
    stayOnPage: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const MEDICAL_FLAGS = [
    { key: 'chay_mau_lau',   label: 'Chảy máu lâu' },
    { key: 'phan_ung_thuoc', label: 'Phản ứng thuốc' },
    { key: 'di_ung_khop',    label: 'Dị ứng, thấp khớp' },
    { key: 'cao_ha',         label: 'Cao HA' },
    { key: 'tim_mach',       label: 'Tim mạch' },
    { key: 'tieu_duong',     label: 'Tiểu đường' },
    { key: 'da_day',         label: 'Dạ dày, tiêu hóa' },
    { key: 'benh_phoi',      label: 'Bệnh phổi' },
    { key: 'truyen_nhiem',   label: 'Bệnh truyền nhiễm' },
];

const form = useForm({
    full_name:         props.patient.full_name ?? '',
    phone:             props.patient.phone ?? '',
    email:             props.patient.email ?? '',
    dob:               props.patient.dob_raw ?? props.patient.dob ?? '',
    gender:            props.patient.gender ?? '',
    address:           props.patient.address ?? '',
    source:            props.patient.source ?? '',
    allergies:         props.patient.allergies ?? '',
    medical_history:   props.patient.medical_history ?? '',
    medical_flags:     props.patient.medical_flags ?? [],
    emergency_contact: props.patient.emergency_contact ?? '',
    branch_id:         props.patient.branch_id ?? null,
    notes:             props.patient.notes ?? '',
    is_active:         props.patient.is_active ?? true,
    force_save:        true,
    stay:              props.stayOnPage,
});

function toTitleCase(str) {
    if (!str) return '';
    return str.replace(/(^|[\s,\-\/])(\S)/g, (_, sep, char) => sep + char.toUpperCase());
}

function doSave() {
    form.full_name = toTitleCase(form.full_name);
    form.address   = toTitleCase(form.address);
    form.put(route('patients.update', props.patient.id), {
        onSuccess: () => emit('close'),
    });
}
</script>
