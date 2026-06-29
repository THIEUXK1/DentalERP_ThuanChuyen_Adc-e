<template>
    <AppLayout :title="patient ? 'Sửa khách hàng' : 'Thêm khách hàng'">
        <div class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">
                {{ patient ? 'Cập nhật hồ sơ khách hàng' : 'Tạo hồ sơ khách hàng mới' }}
            </h2>
            <form @submit.prevent="handleSubmit" class="space-y-5">
                <!-- Basic info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormInput label="Họ tên" :error="form.errors.full_name" required>
                        <input v-model="form.full_name" type="text"
                            @input="form.full_name = toTitleCase(form.full_name)"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Số điện thoại" :error="form.errors.phone" :required="!bypassPhone">
                        <input v-model="form.phone" type="tel"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Email" :error="form.errors.email">
                        <input v-model="form.email" type="email" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Ngày sinh" :error="form.errors.dob">
                        <input v-model="form.dob" type="date" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                    <FormInput label="Giới tính" :error="form.errors.gender">
                        <select v-model="form.gender" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </FormInput>
                    <FormInput label="Nguồn khách" :error="form.errors.source">
                        <select v-model="form.source" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">-- Chọn --</option>
                            <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Chi nhánh" :error="form.errors.branch_id">
                        <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option :value="null">-- Chọn --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </FormInput>
                    <FormInput label="Người liên hệ khẩn cấp" :error="form.errors.emergency_contact">
                        <input v-model="form.emergency_contact" type="text" placeholder="Tên — SĐT" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    </FormInput>
                </div>

                <FormInput label="Địa chỉ" :error="form.errors.address">
                    <input v-model="form.address" type="text"
                        @input="form.address = toTitleCase(form.address)"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>

                <!-- Medical info -->
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        Tiểu sử bệnh
                    </h3>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <label v-for="flag in MEDICAL_FLAGS" :key="flag.key"
                            :class="['flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition-colors select-none',
                                form.medical_flags.includes(flag.key)
                                    ? 'bg-rose-50 border-rose-300 text-rose-700 font-medium'
                                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300']">
                            <input type="checkbox" :value="flag.key" v-model="form.medical_flags" class="hidden" />
                            <span :class="['w-3.5 h-3.5 rounded border flex items-center justify-center flex-shrink-0',
                                form.medical_flags.includes(flag.key) ? 'bg-rose-500 border-rose-500' : 'border-gray-300']">
                                <svg v-if="form.medical_flags.includes(flag.key)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            {{ flag.label }}
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <FormInput label="Dị ứng (chi tiết)" :error="form.errors.allergies">
                            <textarea v-model="form.allergies" rows="2" placeholder="Liệt kê các dị ứng cụ thể..."
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        </FormInput>
                        <FormInput label="Ghi chú bệnh lý khác" :error="form.errors.medical_history">
                            <textarea v-model="form.medical_history" rows="2" placeholder="Bệnh lý khác cần lưu ý..."
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        </FormInput>
                    </div>
                </div>

                <FormInput label="Ghi chú" :error="form.errors.notes">
                    <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </FormInput>

                <div class="flex justify-end gap-3 pt-2">
                    <Link :href="route('patients.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</Link>
                    <button type="submit" :disabled="form.processing || checking"
                        class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-1.5">
                        <svg v-if="checking || form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ patient ? 'Cập nhật' : 'Tạo hồ sơ' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ══════════════════════════════════════
             MODAL CẢNH BÁO TRÙNG / THIẾU SĐT
        ══════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="warnings" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="closeWarnings"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md z-10">

                    <!-- Header -->
                    <div :class="['px-5 pt-5 pb-4 border-b rounded-t-2xl',
                        warnings.full_duplicate ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200']">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0"
                                :class="warnings.full_duplicate ? 'text-red-500' : 'text-amber-500'"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <h3 :class="['font-semibold text-base',
                                warnings.full_duplicate ? 'text-red-800' : 'text-amber-800']">
                                {{ warnings.full_duplicate ? 'Thông tin khách hàng bị trùng!' : 'Cảnh báo trước khi lưu' }}
                            </h3>
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-3">
                        <!-- Trùng hoàn toàn (tên + SĐT) -->
                        <div v-if="warnings.full_duplicate"
                            class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                            <p class="font-medium mb-2">Khách hàng này đã tồn tại trong hệ thống:</p>
                            <div class="space-y-0.5">
                                <p>Họ tên: <strong>{{ warnings.full_duplicate.name }}</strong></p>
                                <p>SĐT: <strong>{{ warnings.full_duplicate.phone }}</strong></p>
                                <p>Mã hồ sơ: <span class="font-mono font-semibold">{{ warnings.full_duplicate.code }}</span></p>
                            </div>
                            <p class="mt-2 font-medium text-red-600">Đây rất có thể là hồ sơ trùng lặp. Bạn có chắc muốn tạo mới?</p>
                        </div>

                        <!-- Chỉ trùng tên -->
                        <div v-else-if="warnings.name_duplicate"
                            class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                            <p class="font-medium mb-2">Đã có khách hàng trùng tên:</p>
                            <div class="space-y-0.5">
                                <p>Họ tên: <strong>{{ warnings.name_duplicate.name }}</strong></p>
                                <p>SĐT: <strong>{{ warnings.name_duplicate.phone || '(chưa có)' }}</strong></p>
                                <p>Mã hồ sơ: <span class="font-mono font-semibold">{{ warnings.name_duplicate.code }}</span></p>
                            </div>
                        </div>

                        <!-- Thiếu số điện thoại -->
                        <div v-if="warnings.phone_empty"
                            class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                            <p class="font-medium">⚠ Số điện thoại chưa được điền.</p>
                            <label class="flex items-start gap-2 mt-2 cursor-pointer select-none">
                                <input type="checkbox" v-model="bypassPhone"
                                    class="mt-0.5 w-4 h-4 rounded border-amber-400 text-amber-600 cursor-pointer" />
                                <span>Tôi xác nhận bỏ qua số điện thoại và vẫn tiếp tục lưu hồ sơ</span>
                            </label>
                        </div>
                    </div>

                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button @click="closeWarnings"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
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
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

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

const props = defineProps({ patient: Object, branches: Array, sources: Array });

const form = useForm({
    full_name:         props.patient?.full_name ?? '',
    phone:             props.patient?.phone ?? '',
    email:             props.patient?.email ?? '',
    dob:               props.patient?.dob ?? '',
    gender:            props.patient?.gender ?? '',
    address:           props.patient?.address ?? '',
    source:            props.patient?.source ?? '',
    allergies:         props.patient?.allergies ?? '',
    medical_history:   props.patient?.medical_history ?? '',
    medical_flags:     props.patient?.medical_flags ?? [],
    emergency_contact: props.patient?.emergency_contact ?? '',
    branch_id:         props.patient?.branch_id ?? null,
    notes:             props.patient?.notes ?? '',
    is_active:         props.patient?.is_active ?? true,
    force_save:        false,
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
    // Khi sửa hồ sơ cũ → không cần check duplicate
    if (props.patient) { doSave(); return; }

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
    } catch (err) {
        console.error('check-duplicate error:', err);
        // Không tự động lưu khi check thất bại — yêu cầu user thử lại
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
    if (props.patient) {
        form.put(route('patients.update', props.patient.id));
    } else {
        form.post(route('patients.store'));
    }
}
</script>