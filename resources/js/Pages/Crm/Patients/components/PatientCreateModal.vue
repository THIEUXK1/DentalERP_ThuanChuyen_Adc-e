<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="$emit('close')">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                    <h3 class="text-base font-semibold text-gray-800">Tạo hồ sơ khách hàng mới</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body (scrollable) -->
                <div class="overflow-y-auto flex-1 px-6 py-5">
                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Basic info -->
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
                                <div class="mt-1.5 space-y-1.5">
                                    <div v-for="(p, i) in form.extra_phones" :key="i" class="flex items-center gap-1">
                                        <input v-model="form.extra_phones[i]" type="tel" placeholder="SĐT phụ"
                                            class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                                            :class="{'border-red-400': form.errors[`extra_phones.${i}`]}" />
                                        <button type="button" @click="form.extra_phones.splice(i, 1)" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" @click="form.extra_phones.push('')"
                                        class="text-xs text-indigo-600 hover:text-indigo-700">
                                        + Thêm số điện thoại khác
                                    </button>
                                </div>
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
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ngày sinh</label>
                                <input v-model="form.dob" type="date"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none" />
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

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea v-model="form.notes" rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none resize-none" />
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Hủy
                    </button>
                    <button @click="handleSubmit" :disabled="form.processing || checking"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium flex items-center gap-1.5">
                        <svg v-if="checking || form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Tạo hồ sơ
                    </button>
                </div>
            </div>
        </div>

        <!-- Duplicate warning modal -->
        <div v-if="warnings" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="closeWarnings"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md z-10">
                <div :class="['px-5 pt-5 pb-4 border-b rounded-t-2xl',
                    warnings.full_duplicate ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200']">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0"
                            :class="warnings.full_duplicate ? 'text-red-500' : 'text-amber-500'"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <h3 :class="['font-semibold text-base', warnings.full_duplicate ? 'text-red-800' : 'text-amber-800']">
                            {{ warnings.full_duplicate ? 'Thông tin khách hàng bị trùng!' : 'Cảnh báo trước khi lưu' }}
                        </h3>
                    </div>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div v-if="warnings.full_duplicate" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                        <p class="font-medium mb-2">Khách hàng này đã tồn tại trong hệ thống:</p>
                        <div class="space-y-0.5">
                            <p>Họ tên: <strong>{{ warnings.full_duplicate.name }}</strong></p>
                            <p>SĐT: <strong>{{ warnings.full_duplicate.phone }}</strong></p>
                            <p>Mã hồ sơ: <span class="font-mono font-semibold">{{ warnings.full_duplicate.code }}</span></p>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <p class="font-medium text-red-600">Đây rất có thể là hồ sơ trùng lặp.</p>
                            <a :href="`/patients/${warnings.full_duplicate.id}`" target="_blank"
                                class="inline-flex items-center gap-1 shrink-0 px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Xem hồ sơ
                            </a>
                        </div>
                    </div>
                    <div v-else-if="warnings.name_duplicate" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                        <p class="font-medium mb-2">Đã có khách hàng trùng tên:</p>
                        <div class="space-y-0.5">
                            <p>Họ tên: <strong>{{ warnings.name_duplicate.name }}</strong></p>
                            <p>SĐT: <strong>{{ warnings.name_duplicate.phone || '(chưa có)' }}</strong></p>
                            <p>Mã hồ sơ: <span class="font-mono font-semibold">{{ warnings.name_duplicate.code }}</span></p>
                        </div>
                        <div class="mt-2 flex justify-end">
                            <a :href="`/patients/${warnings.name_duplicate.id}`" target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-amber-500 text-white rounded-lg hover:bg-amber-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Xem hồ sơ
                            </a>
                        </div>
                    </div>
                    <div v-if="warnings.phone_empty" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                        <p class="font-medium">⚠ Số điện thoại chưa được điền.</p>
                        <label class="flex items-start gap-2 mt-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="bypassPhone" class="mt-0.5 w-4 h-4 rounded border-amber-400 text-amber-600 cursor-pointer" />
                            <span>Tôi xác nhận bỏ qua số điện thoại và vẫn tiếp tục lưu hồ sơ</span>
                        </label>
                    </div>
                </div>
                <div class="px-5 pb-5 flex justify-end gap-2">
                    <button @click="closeWarnings" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Quay lại kiểm tra
                    </button>
                    <button @click="confirmAndSave"
                        :disabled="warnings.phone_empty && !bypassPhone"
                        :class="['px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-40 disabled:cursor-not-allowed',
                            warnings.full_duplicate ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-500 hover:bg-amber-600']">
                        Vẫn tiếp tục lưu
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ branches: Array, sources: Array });
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
    full_name: '', phone: '', extra_phones: [], email: '', dob: '', gender: '',
    address: '', source: '', allergies: '', medical_history: '',
    medical_flags: [], emergency_contact: '', branch_id: null,
    notes: '', is_active: true, force_save: false,
});

const checking    = ref(false);
const warnings    = ref(null);
const bypassPhone = ref(false);

function toTitleCase(str) {
    if (!str) return '';
    return str.replace(/(^|[\s,\-\/])(\S)/g, (_, sep, char) => sep + char.toUpperCase());
}

function closeWarnings() {
    warnings.value = null;
    bypassPhone.value = false;
}

async function handleSubmit() {
    if (!form.full_name.trim()) return;

    checking.value = true;
    try {
        const resp = await fetch('/patients/check-duplicate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ full_name: form.full_name, phone: form.phone }),
        });
        const { warnings: w } = await resp.json();
        const hasWarning = w.phone_empty || w.name_duplicate || w.full_duplicate;
        if (hasWarning) {
            bypassPhone.value = false;
            warnings.value = w;
        } else {
            doSave();
        }
    } catch {
        alert('Không thể kiểm tra trùng lặp. Vui lòng thử lại.');
    } finally {
        checking.value = false;
    }
}

function confirmAndSave() {
    if (warnings.value?.phone_empty && !bypassPhone.value) return;
    warnings.value = null;
    form.force_save = true;
    doSave();
}

function doSave() {
    form.full_name = toTitleCase(form.full_name);
    form.address   = toTitleCase(form.address);
    form.extra_phones = form.extra_phones.map(p => p.trim()).filter(Boolean);
    form.post(route('patients.store'), {
        onSuccess: () => emit('close'),
    });
}
</script>
