<template>
    <AppLayout :title="plan ? 'Sửa kế hoạch' : 'Tạo kế hoạch điều trị'">
        <div class="space-y-4">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link v-if="patientId" :href="route('patients.show', patientId)" class="hover:text-indigo-600 font-medium">← Quay lại hồ sơ bệnh nhân</Link>
                <Link v-else :href="route('clinical.treatment-plans.index')" class="hover:text-indigo-600 font-medium">← Quay lại danh sách kế hoạch</Link>
            </div>

            <!-- Grid: Left 2 cols + Right 1 col -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- ── LEFT: Dental chart + Add form + Items table ─────────── -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Dental chart -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Sơ đồ răng — chọn vị trí điều trị
                        </h3>
                        <ToothChart v-model="selectedTeeth" :treated-teeth="treatedTeethList" @select="onTeethSelect" />
                        <p v-if="selectedTeeth.length" class="mt-2 text-xs text-indigo-600 font-medium">
                            Đã chọn: Răng {{ selectedTeeth.join(', ') }}
                        </p>
                    </div>

                    <!-- Add item form -->
                    <div class="bg-white rounded-xl border border-indigo-100 p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Thêm dịch vụ điều trị
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div class="sm:col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Dịch vụ / Thủ thuật *</label>
                                <select v-model="addItem.service_id" @change="onAddServiceChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- Chọn dịch vụ --</option>
                                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Số lượng</label>
                                <input v-model.number="addItem.quantity" type="number" min="1"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Vị trí răng</label>
                                <input v-model="addItem.tooth_number" type="text" placeholder="VD: 11,12,21"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Đơn giá (₫)</label>
                                <input v-model.number="addItem.unit_price" type="number" min="0"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm tabular-nums focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Thành tiền</label>
                                <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-semibold text-indigo-700 tabular-nums">
                                    {{ fmt(addItemAmount) }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Giảm giá (₫)</label>
                                <input v-model.number="addItem.discount" type="number" min="0"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm tabular-nums focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Số buổi dự kiến</label>
                                <input v-model.number="addItem.estimated_sessions" type="number" min="1" placeholder="VD: 3"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Chẩn đoán</label>
                                <input v-model="addItem.diagnosis" type="text" placeholder="Chẩn đoán dịch vụ này..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Người thực hiện</label>
                                <select v-model="addItem.responsible_doctor_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn bác sĩ --</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Trợ thủ</label>
                                <select v-model="addItem.assistant_doctor_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn trợ thủ --</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                                <input v-model="addItem.notes" type="text" placeholder="Ghi chú nội dung..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" @click="submitAddItem" :disabled="!addItem.service_id"
                                class="inline-flex items-center gap-1.5 px-5 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-40 font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Thêm dịch vụ
                            </button>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden overflow-x-auto shadow-sm">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Danh sách dịch vụ điều trị</h3>
                            <span class="text-xs text-gray-400">{{ form.items.length }} dịch vụ</span>
                        </div>
                        <div v-if="form.items.length === 0" class="flex flex-col items-center py-10 text-gray-400">
                            <svg class="w-8 h-8 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm">Chưa có dịch vụ nào</p>
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-medium w-6">#</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Dịch vụ</th>
                                    <th class="px-4 py-2.5 text-center font-medium">Răng</th>
                                    <th class="px-4 py-2.5 text-center font-medium">SL</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Đơn giá</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Giảm giá</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Thành tiền</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-blue-50/20 transition-colors">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ serviceName(item.service_id) }}</p>
                                        <p v-if="item.diagnosis" class="text-xs text-amber-600 mt-0.5">🔍 {{ item.diagnosis }}</p>
                                        <p v-if="item.notes" class="text-xs text-gray-400 mt-0.5">{{ item.notes }}</p>
                                        <p v-if="item.estimated_sessions" class="text-xs text-gray-400 mt-0.5">{{ item.estimated_sessions }} buổi</p>
                                        <p v-if="item.responsible_doctor_id" class="text-xs text-indigo-500 mt-0.5">
                                            🦷 {{ doctorName(item.responsible_doctor_id) }}
                                            <span v-if="item.assistant_doctor_id" class="text-gray-400"> · Trợ: {{ doctorName(item.assistant_doctor_id) }}</span>
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="item.tooth_number"
                                            class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-mono text-xs">
                                            {{ item.tooth_number }}
                                        </span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ fmt(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        <span v-if="item.discount" class="text-rose-500">-{{ fmt(item.discount) }}</span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800 tabular-nums">{{ fmt(item.amount) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="removeItemRow(idx)"
                                            class="text-red-400 hover:text-red-600 text-xs hover:underline">Xóa</button>
                                    </td>
                                </tr>
                                <!-- Total row -->
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td colspan="6" class="px-4 py-3 text-right text-gray-500 text-xs font-medium">Tổng:</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 tabular-nums">{{ fmt(form.total_amount) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── RIGHT: Info + Staff + Cost + Save ───────────────────── -->
                <div class="space-y-4">

                    <!-- Patient & Branch -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Thông tin hành chính
                        </h3>

                        <!-- Patient -->
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Khách hàng *</label>
                            <div v-if="currentPatient" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0 bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 flex items-center justify-between gap-2">
                                    <span class="truncate">{{ activePatientName }}</span>
                                    <span class="flex-shrink-0 font-mono text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">{{ activePatientCode }}</span>
                                </div>
                                <button type="button" @click="clearPatient" title="Đổi bệnh nhân"
                                    class="flex-shrink-0 p-2 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg border border-indigo-200 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </button>
                            </div>
                            <div v-else class="relative" ref="patientComboRef">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input v-model="patientSearch" type="text" placeholder="Tìm tên hoặc mã bệnh nhân..."
                                    @focus="patientDropdownOpen = true" @input="patientDropdownOpen = true"
                                    class="block w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                <div v-if="patientDropdownOpen && filteredPatients.length > 0"
                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                    <button v-for="p in filteredPatients" :key="p.id" type="button"
                                        @mousedown.prevent="selectPatient(p)"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 flex items-center justify-between gap-2">
                                        <span class="truncate">{{ p.full_name }}</span>
                                        <span class="text-xs font-mono text-gray-400">{{ p.code }}</span>
                                    </button>
                                </div>
                                <div v-if="patientDropdownOpen && patientSearch && filteredPatients.length === 0"
                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-sm text-gray-400">
                                    Không tìm thấy bệnh nhân
                                </div>
                            </div>
                            <p v-if="form.errors.patient_id" class="text-xs text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                        </div>

                        <!-- Branch -->
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Chi nhánh *</label>
                            <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">-- Chọn chi nhánh --</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="form.errors.branch_id" class="text-xs text-red-500 mt-1">{{ form.errors.branch_id }}</p>
                        </div>

                        <!-- Treatment date: defaults to today, but can be set ahead of time -->
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Ngày điều trị *</label>
                            <div class="flex items-center gap-1.5">
                                <input v-model="startDatePart" type="date"
                                    class="flex-1 min-w-0 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                <input v-model="startTimePart" type="time" lang="vi"
                                    class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <p v-if="form.errors.start_date" class="text-xs text-red-500 mt-1">{{ form.errors.start_date }}</p>
                        </div>
                    </div>

                    <!-- Nhân sự -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Nhân sự phụ trách
                        </h3>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Bác sĩ điều trị chính</label>
                            <select v-model="form.doctor_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="null">-- Chọn bác sĩ --</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Tư vấn viên (CSKH)</label>
                            <select v-model="form.consultant_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="null">-- Chọn tư vấn viên --</option>
                                <option v-for="c in consultants" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Cost summary + Save -->
                    <div class="bg-slate-900 rounded-xl p-4 shadow-sm text-white space-y-4">
                        <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Tổng hợp chi phí</h3>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tổng dịch vụ:</span>
                                <span class="font-semibold font-mono">{{ fmt(form.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tổng giảm giá:</span>
                                <span class="font-semibold font-mono text-rose-400">-{{ fmt(form.discount_amount) }}</span>
                            </div>
                            <div class="border-t border-slate-700 pt-2 flex justify-between text-base">
                                <span class="font-bold text-slate-200">Thực thu:</span>
                                <span class="font-bold font-mono text-emerald-400">{{ fmt(netTotal) }}</span>
                            </div>
                        </div>

                        <div class="border-t border-slate-700 pt-3">
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Ghi chú</label>
                            <textarea v-model="form.notes" rows="2" placeholder="Lưu ý lâm sàng, tiền sử dị ứng..."
                                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-slate-700">
                            <button type="button" @click="submitPlan('draft', 'show')" :disabled="form.processing"
                                class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs font-semibold text-slate-300 transition-colors disabled:opacity-50">
                                Lưu nháp
                            </button>
                            <button type="button" @click="submitPlan('approved', 'show')" :disabled="form.processing"
                                class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-xs font-semibold text-white transition-colors disabled:opacity-50">
                                Lưu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, watch, ref, reactive, onMounted, onUnmounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ToothChart from '@/Components/Shared/ToothChart.vue';

const props = defineProps({
    plan: Object,
    patients: Array,
    doctors: Array,
    consultants: Array,
    branches: Array,
    services: Array,
    selected_patient_id: Number,
    selected_branch_id: Number,
});

// ── Patient combobox ──────────────────────────────────────────────────────
const patientSearch      = ref('');
const patientDropdownOpen = ref(false);
const patientComboRef    = ref(null);

const currentPatient = computed(() => {
    const pId = form.patient_id || props.plan?.patient_id || props.selected_patient_id;
    return pId ? (props.patients.find(p => p.id == pId) || null) : null;
});
const activePatientName = computed(() => currentPatient.value?.full_name ?? 'Bệnh nhân chưa chọn');
const activePatientCode = computed(() => currentPatient.value?.code ?? 'BN-XXXX');
const patientId         = computed(() => currentPatient.value?.id ?? null);

const filteredPatients = computed(() => {
    const q = patientSearch.value.toLowerCase().trim();
    if (!q) return props.patients.slice(0, 20);
    return props.patients.filter(p =>
        p.full_name.toLowerCase().includes(q) ||
        p.code.toLowerCase().includes(q) ||
        (p.phone && p.phone.includes(q))
    ).slice(0, 30);
});

function selectPatient(p) {
    form.patient_id = p.id;
    patientSearch.value = '';
    patientDropdownOpen.value = false;
}
function clearPatient() {
    form.patient_id = '';
    patientSearch.value = '';
    patientDropdownOpen.value = false;
}
function handlePatientOutsideClick(e) {
    if (patientComboRef.value && !patientComboRef.value.contains(e.target)) {
        patientDropdownOpen.value = false;
    }
}
onMounted(() => document.addEventListener('click', handlePatientOutsideClick));
onUnmounted(() => document.removeEventListener('click', handlePatientOutsideClick));

// ── Dental chart ──────────────────────────────────────────────────────────
const selectedTeeth    = ref([]);
const treatedTeethList = computed(() => form.items.filter(i => i.tooth_number).map(i => i.tooth_number));

function onTeethSelect(teeth) {
    selectedTeeth.value     = teeth;
    addItem.tooth_number    = teeth.join(',');
}

// Local calendar date (not toISOString(), which is UTC and can land on the wrong
// day for timezones ahead of UTC, e.g. late-night in Asia/Ho_Chi_Minh).
function todayLocalDate() {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
}
function nowLocalTime() {
    const d = new Date();
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

// ── Ngày điều trị: tách ô ngày/giờ, ghép lại thành form.start_date ──────────
function datePartOf(raw) {
    return raw ? raw.split('T')[0] ?? '' : '';
}
function timePartOf(raw) {
    return raw ? (raw.split('T')[1] ?? '00:00') : '00:00';
}
const startDatePart = computed({
    get: () => datePartOf(form.start_date),
    set: (v) => { form.start_date = v ? `${v}T${timePartOf(form.start_date)}` : ''; },
});
const startTimePart = computed({
    get: () => timePartOf(form.start_date),
    set: (v) => {
        const date = datePartOf(form.start_date) || todayLocalDate();
        form.start_date = `${date}T${v || '00:00'}`;
    },
});

// ── Main form ─────────────────────────────────────────────────────────────
const form = useForm({
    patient_id:         props.plan?.patient_id    ?? props.selected_patient_id ?? '',
    branch_id:          props.plan?.branch_id     ?? props.selected_branch_id  ?? '',
    doctor_id:          props.plan?.doctor_id     ?? null,
    consultant_id:      props.plan?.consultant_id ?? null,
    appointment_id:     props.plan?.appointment_id ?? null,
    notes:              props.plan?.notes         ?? '',
    diagnosis:          props.plan?.diagnosis     ?? '',
    chief_complaint:    props.plan?.chief_complaint ?? '',
    treatment_goal:     props.plan?.treatment_goal  ?? '',
    start_date:         props.plan?.start_date    ?? `${todayLocalDate()}T${nowLocalTime()}`,
    expected_end_date:  props.plan?.expected_end_date ?? '',
    estimated_sessions: props.plan?.estimated_sessions ?? 1,
    frequency:          props.plan?.frequency     ?? '',
    priority:           props.plan?.priority      ?? 'normal',
    status:             props.plan?.status        ?? 'draft',
    total_amount:       props.plan?.total_amount  ?? 0,
    discount_amount:    props.plan?.discount_amount ?? 0,
    action:             'show',
    items:              props.plan?.items         ?? [],
});

watch(() => form.patient_id, (pId) => {
    if (!pId || form.branch_id) return;
    const p = props.patients.find(x => x.id == pId);
    if (p?.branch_id) form.branch_id = p.branch_id;
});

const netTotal = computed(() => Math.max(0, form.total_amount - form.discount_amount));

function fmt(val) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val ?? 0);
}

function serviceName(id) {
    return props.services.find(s => s.id == id)?.name ?? '—';
}
function doctorName(id) {
    return props.doctors.find(d => d.id == id)?.name ?? '—';
}

// ── Add item form ─────────────────────────────────────────────────────────
const addItem = reactive({
    service_id:            '',
    quantity:              1,
    tooth_number:          '',
    unit_price:            0,
    discount:              0,
    estimated_sessions:    null,
    responsible_doctor_id: null,
    assistant_doctor_id:   null,
    diagnosis:             '',
    notes:                 '',
});

const addItemAmount = computed(() =>
    Math.max(0, (addItem.unit_price || 0) * (addItem.quantity || 1) - (addItem.discount || 0))
);

function onAddServiceChange() {
    const svc = props.services.find(s => s.id == addItem.service_id);
    if (svc) addItem.unit_price = svc.selling_price ?? 0;
}

function submitAddItem() {
    if (!addItem.service_id) return;
    form.items.push({
        service_id:            addItem.service_id,
        quantity:              addItem.quantity || 1,
        tooth_number:          addItem.tooth_number,
        unit_price:            addItem.unit_price || 0,
        discount:              addItem.discount || 0,
        amount:                addItemAmount.value,
        estimated_sessions:    addItem.estimated_sessions || null,
        stage_name:            '',
        responsible_doctor_id: addItem.responsible_doctor_id,
        assistant_doctor_id:   addItem.assistant_doctor_id,
        diagnosis:             addItem.diagnosis,
        notes:                 addItem.notes,
    });
    recalculateTotals();
    // Reset form but keep teeth selection
    addItem.service_id            = '';
    addItem.quantity              = 1;
    addItem.unit_price            = 0;
    addItem.discount              = 0;
    addItem.estimated_sessions    = null;
    addItem.responsible_doctor_id = null;
    addItem.assistant_doctor_id   = null;
    addItem.diagnosis             = '';
    addItem.notes                 = '';
}

function removeItemRow(idx) {
    form.items.splice(idx, 1);
    recalculateTotals();
}

function recalculateTotals() {
    form.total_amount    = form.items.reduce((s, i) => s + (i.unit_price * i.quantity), 0);
    form.discount_amount = form.items.reduce((s, i) => s + (i.discount || 0), 0);
}

// ── Submit ────────────────────────────────────────────────────────────────
function submitPlan(statusVal, actionVal) {
    form.status = statusVal;
    form.action = actionVal;
    if (props.plan) {
        form.put(route('clinical.treatment-plans.update', props.plan.id));
    } else {
        form.post(route('clinical.treatment-plans.store'));
    }
}
</script>
