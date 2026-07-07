<template>
    <AppLayout :title="`KH: ${patient.full_name}`">
        <div class="space-y-3">

            <!-- ── Header ───────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 space-y-3">
                <!-- Breadcrumb + edit -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm">
                        <Link :href="route('patients.index')" class="text-gray-400 hover:text-gray-600">← Danh sách</Link>
                        <span class="text-gray-300">/</span>
                        <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ patient.code }}</span>
                        <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', patient.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500']">
                            {{ patient.is_active ? 'Đang hoạt động' : 'Ngừng' }}
                        </span>
                    </div>
                </div>

                <!-- Name + contact -->
                <div class="flex items-start gap-4">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 relative group">
                        <img v-if="patient.photo_url" :src="patient.photo_url" :alt="patient.full_name"
                            class="w-12 h-12 rounded-full object-cover shadow-sm border border-gray-200" />
                        <div v-else class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                            {{ patient.full_name.charAt(0).toUpperCase() }}
                        </div>
                        <label v-if="can('patients.edit')" class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center cursor-pointer transition-opacity">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" @change="uploadAvatar" />
                        </label>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-bold text-gray-900">{{ patient.full_name }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ patient.phone }}
                            <span v-if="patient.email" class="ml-2">· {{ patient.email }}</span>
                            <span v-if="patient.dob" class="ml-2">· {{ patient.dob }}</span>
                            <span v-if="genderLabel" class="ml-2">· {{ genderLabel }}</span>
                        </p>
                    </div>
                </div>

                <!-- Action button bar -->
                <ActionButtonBar :patient-id="patient.id" @edit="showEditModal = true" @book-appointment="showBookAppointment = true" />

                <!-- Financial summary bar -->
                <FinancialSummaryBar
                    :total-amount="financial.total_amount"
                    :amount-paid="financial.amount_paid"
                    :amount-due="financial.amount_due"
                />

                <!-- Last visit banner -->
                <LastVisitBanner :last-activity="activities[0] ?? null" />
            </div>

            <!-- ── Tab navigation ────────────────────────────────────────────── -->
            <div class="flex gap-1 bg-white border border-gray-200 p-1 rounded-xl w-fit">
                <button v-for="tab in tabs" :key="tab.key" @click="setTab(tab.key)"
                    :class="['px-4 py-1.5 text-sm rounded-lg transition-all duration-150 flex items-center gap-1.5',
                        activeTab === tab.key
                            ? 'bg-indigo-600 shadow text-white font-medium'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50']">
                    <span>{{ tab.label }}</span>
                    <span v-if="tab.count !== undefined"
                        :class="['text-xs rounded-full px-1.5 py-0.5 font-medium',
                            activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500']">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- ── Tab: Thông tin ─────────────────────────────────────────────── -->
            <template v-if="activeTab === 'info'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Personal info -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Thông tin cá nhân
                        </h3>
                        <dl class="space-y-2 text-sm">
                            <InfoRow label="Giới tính"      :value="genderLabel" />
                            <InfoRow label="Ngày sinh"      :value="patient.dob ?? '—'" />
                            <InfoRow label="Địa chỉ"        :value="patient.address ?? '—'" />
                            <InfoRow label="Nguồn"          :value="patient.source ?? '—'" />
                            <InfoRow label="Liên hệ khẩn"   :value="patient.emergency_contact ?? '—'" />
                        </dl>
                    </div>

                    <!-- Medical info -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            Thông tin y tế
                        </h3>
                        <!-- Medical history flags -->
                        <div v-if="activeMedicalFlags.length" class="flex flex-wrap gap-1.5">
                            <span v-for="f in activeMedicalFlags" :key="f.key"
                                class="inline-flex items-center px-2 py-1 rounded-lg bg-rose-50 text-rose-700 text-xs font-medium border border-rose-100">
                                ⚠️ {{ f.label }}
                            </span>
                        </div>
                        <dl class="space-y-2 text-sm">
                            <InfoRow label="Dị ứng"        :value="patient.allergies ?? '—'" />
                            <InfoRow label="Tiền sử bệnh"  :value="patient.medical_history ?? '—'" />
                            <InfoRow label="Ghi chú"       :value="patient.notes ?? '—'" />
                        </dl>
                    </div>
                </div>

                <!-- Activity log -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            Hoạt động chăm sóc
                        </h3>
                        <button @click="showActivity = !showActivity"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ghi hoạt động
                        </button>
                    </div>
                    <div v-if="showActivity" class="mb-4 p-3 bg-indigo-50 rounded-xl space-y-3 border border-indigo-100">
                        <div class="flex gap-3">
                            <select v-model="actForm.type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-36 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option v-for="c in contactTypes" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                            <textarea v-model="actForm.content" rows="2" placeholder="Nội dung hoạt động..."
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="showActivity = false" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</button>
                            <button @click="submitActivity" :disabled="actForm.processing"
                                class="px-3 py-1.5 text-xs text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">Ghi lại</button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div v-if="activities.length === 0" class="text-sm text-gray-400 text-center py-6">Chưa có hoạt động</div>
                        <div v-for="a in activities" :key="a.id" class="flex gap-3 text-sm py-1.5 border-b border-gray-50 last:border-0">
                            <StatusBadge :color="a.type_color" class="mt-0.5 flex-shrink-0">{{ a.type_label }}</StatusBadge>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-700">{{ a.content }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ a.creator }} · {{ a.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ── Tab: Hóa đơn ──────────────────────────────────────────────── -->
            <div v-if="activeTab === 'invoices'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                        Danh sách hóa đơn
                    </h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">{{ invoices.length }} hóa đơn</span>
                        <Link :href="route('cashier.invoices.index', { patient_id: patient.id })"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium hover:underline">
                            Xem tất cả →
                        </Link>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="invoices.length === 0" class="text-center py-12 text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <p class="text-sm">Chưa có hóa đơn nào</p>
                </div>

                <!-- Invoice table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Mã HĐ</th>
                                <th class="px-4 py-2.5 text-left font-medium">Kế hoạch</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Ngày TT</th>
                                <th class="px-4 py-2.5 text-right font-medium">Tổng tiền</th>
                                <th class="px-4 py-2.5 text-right font-medium hidden md:table-cell">Đã TT</th>
                                <th class="px-4 py-2.5 text-right font-medium">Còn nợ</th>
                                <th class="px-4 py-2.5 text-left font-medium">Trạng thái</th>
                                <th class="px-4 py-2.5 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="inv in invoices" :key="inv.id"
                                :class="['hover:bg-gray-50 transition-colors', inv.status === 'cancelled' ? 'opacity-60' : '']">
                                <td class="px-4 py-2.5">
                                    <span class="font-mono text-xs text-gray-500">{{ inv.code }}</span>
                                    <span v-if="inv.installment_index !== null && inv.installment_index !== undefined"
                                        class="ml-1.5 text-xs bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded-full font-medium">
                                        Đợt {{ inv.installment_index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <Link v-if="inv.plan_id"
                                        :href="route('clinical.treatment-plans.show', inv.plan_id)"
                                        class="font-mono text-xs text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ inv.plan_code }}
                                    </Link>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-2.5 hidden md:table-cell">
                                    <span v-if="inv.last_payment_date" class="text-xs text-emerald-600 font-medium">
                                        {{ inv.last_payment_date }}
                                        <span v-if="inv.payment_count > 1" class="ml-1 text-gray-400 font-normal">({{ inv.payment_count }} lần)</span>
                                    </span>
                                    <span v-else class="text-xs text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-medium text-gray-800 tabular-nums">{{ formatVnd(inv.total) }}</td>
                                <td class="px-4 py-2.5 text-right text-emerald-600 tabular-nums hidden md:table-cell">{{ formatVnd(inv.amount_paid) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums"
                                    :class="inv.amount_due > 0 ? 'text-red-600 font-semibold' : 'text-gray-300'">
                                    {{ formatVnd(inv.amount_due) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        inv.status === 'cancelled' ? 'bg-gray-100 text-gray-500' :
                                        inv.amount_due <= 0 ? 'bg-emerald-100 text-emerald-700' :
                                        'bg-red-100 text-red-700']">
                                        {{ inv.status === 'cancelled' ? 'Đã hủy' : inv.amount_due <= 0 ? 'Thanh toán đủ' : 'Còn nợ' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <Link :href="route('cashier.invoices.show', inv.id)"
                                        class="text-xs text-indigo-500 hover:text-indigo-700 font-medium hover:underline whitespace-nowrap">
                                        Xem →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                        <!-- Summary footer -->
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-2.5 text-xs text-gray-500 font-medium hidden md:table-cell">Tổng cộng</td>
                                <td colspan="2" class="px-4 py-2.5 text-xs text-gray-500 font-medium md:hidden">Tổng cộng</td>
                                <td class="px-4 py-2.5 text-right font-bold text-gray-800 tabular-nums">
                                    {{ formatVnd(invoices.filter(i => i.status !== 'cancelled').reduce((s, i) => s + i.total, 0)) }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-emerald-700 font-bold tabular-nums hidden md:table-cell">
                                    {{ formatVnd(invoices.filter(i => i.status !== 'cancelled').reduce((s, i) => s + i.amount_paid, 0)) }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-red-600 font-bold tabular-nums">
                                    {{ formatVnd(invoices.filter(i => i.status !== 'cancelled').reduce((s, i) => s + i.amount_due, 0)) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ── Tab: Lịch sử điều trị ──────────────────────────────────────── -->
            <TreatmentHistoryTab
                v-if="activeTab === 'treatment'"
                :treatment-plans="treatmentPlans"
                :pending-deletions="pendingDeletions"
            />

            <!-- ── Tab: Lịch hẹn ─────────────────────────────────────────────── -->
            <AppointmentHistoryTab
                v-if="activeTab === 'appointments'"
                :appointments="appointments"
                :pending-deletions="pendingDeletions"
            />

            <!-- ── Tab: Sơ đồ răng ───────────────────────────────────────────── -->
            <DentalChartTab v-if="activeTab === 'chart'"
                :patient="patient"
                :toothConditions="toothConditions"
                :conditionTypes="conditionTypes"
                :canEdit="can('clinical_notes.create')"
            />

            <!-- ── Tab: Hồ sơ lâm sàng ──────────────────────────────────────── -->
            <ClinicalNotesTab v-if="activeTab === 'clinical'"
                :patient="patient"
                :clinicalNotes="clinicalNotes"
                :doctors="doctors"
                :canCreate="can('clinical_notes.create')"
            />

            <!-- ── Tab: Tài liệu đính kèm ────────────────────────────────────── -->
            <div v-if="activeTab === 'attachments'" class="bg-white rounded-xl border border-gray-200 p-5">
                <AttachmentsTab
                    :patientId="patient.id"
                    :attachments="attachments"
                    :attachmentTypes="attachmentTypes"
                />
            </div>

            <!-- ── Tab: Phiếu đồng ý ─────────────────────────────────────────── -->
            <div v-if="activeTab === 'consent'" class="bg-white rounded-xl border border-gray-200 p-5">
                <ConsentFormsTab
                    :patientId="patient.id"
                    :consentForms="consentForms"
                    :treatmentPlans="treatmentPlans"
                />
            </div>

            <!-- ── Tab: Timeline ─────────────────────────────────────────────── -->
            <div v-if="activeTab === 'timeline'" class="bg-white rounded-xl border border-gray-200 p-5">
                <TreatmentTimeline :timeline="timeline" />
            </div>

            <!-- ── Relationships section (always visible in info tab) ─────────── -->
            <div v-if="activeTab === 'info'" class="bg-white rounded-xl border border-gray-200 p-5">
                <RelationshipsSection
                    :patientId="patient.id"
                    :relationships="relationships"
                    :relationshipTypes="relationshipTypes"
                    :allPatients="allPatients"
                />
            </div>
        </div>

        <PatientEditModal v-if="showEditModal"
            :patient="patient" :branches="branches" :sources="sources"
            @close="showEditModal = false" />

        <AppointmentCreateModal v-if="showBookAppointment"
            :patient-id="patient.id" :default-branch-id="patient.branch_id"
            :branches="branches" :doctors="doctors" :chairs="chairs" :services="services"
            @close="showBookAppointment = false" />
    </AppLayout>
</template>

<script setup>
import { ref, computed, defineComponent, onMounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import FinancialSummaryBar from '@/Components/Shared/FinancialSummaryBar.vue';
import LastVisitBanner from '@/Components/Shared/LastVisitBanner.vue';
import ActionButtonBar from '@/Components/Shared/ActionButtonBar.vue';
import TreatmentHistoryTab from '@/Components/Clinical/TreatmentHistoryTab.vue';
import AppointmentHistoryTab from '@/Components/Clinical/AppointmentHistoryTab.vue';
import AppointmentCreateModal from '@/Components/Clinical/AppointmentCreateModal.vue';
import DentalChartTab from '@/Components/Clinical/DentalChartTab.vue';
import ClinicalNotesTab from '@/Components/Clinical/ClinicalNotesTab.vue';
import AttachmentsTab from './components/AttachmentsTab.vue';
import ConsentFormsTab from './components/ConsentFormsTab.vue';
import RelationshipsSection from './components/RelationshipsSection.vue';
import TreatmentTimeline from './components/TreatmentTimeline.vue';
import PatientEditModal from './components/PatientEditModal.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';
import { recordPatientView } from '@/composables/useRecentlyViewedPatients';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();

const props = defineProps({
    patient:            Object,
    financial:          Object,
    invoices:           { type: Array, default: () => [] },
    treatmentPlans:     Array,
    appointments:       Array,
    pendingDeletions:   { type: Object, default: () => ({}) },
    activities:         Array,
    clinicalNotes:      Array,
    toothConditions:    Array,
    attachments:        Array,
    consentForms:       Array,
    relationships:      Array,
    timeline:           Array,
    doctors:            Array,
    chairs:             { type: Array, default: () => [] },
    services:           { type: Array, default: () => [] },
    conditionTypes:     Array,
    contactTypes:       Array,
    attachmentTypes:    Array,
    relationshipTypes:  Array,
    allPatients:        Array,
    branches:           Array,
    sources:            Array,
});

const activeTab           = ref('treatment');
const showEditModal       = ref(false);
const showBookAppointment = ref(false);

const TAB_KEYS = ['info', 'invoices', 'treatment', 'appointments', 'chart', 'clinical', 'attachments', 'consent', 'timeline'];

onMounted(() => {
    const hash = window.location.hash.replace('#', '');
    if (hash && TAB_KEYS.includes(hash)) {
        activeTab.value = hash;
    }
    recordPatientView(props.patient.id);
});

function setTab(key) {
    activeTab.value = key;
    history.replaceState(null, '', '#' + key);
}

const showActivity = ref(false);
const actForm      = useForm({ type: 'note', content: '', patient_id: props.patient.id });

const tabs = computed(() => [
    { key: 'info',         label: 'Thông tin' },
    { key: 'invoices',     label: 'Hóa đơn', count: props.invoices?.length ?? 0 },
    { key: 'treatment',    label: 'Điều trị', count: props.treatmentPlans?.length ?? 0 },
    { key: 'appointments', label: 'Lịch hẹn', count: props.appointments?.length ?? 0 },
    { key: 'chart',        label: 'Sơ đồ răng' },
    { key: 'clinical',     label: 'Lâm sàng', count: props.clinicalNotes?.length ?? 0 },
    { key: 'attachments',  label: 'Tài liệu', count: props.attachments?.length ?? 0 },
    { key: 'consent',      label: 'Phiếu đồng ý', count: props.consentForms?.length ?? 0 },
    { key: 'timeline',     label: 'Timeline' },
]);

const genderLabel = computed(() => ({ male: 'Nam', female: 'Nữ', other: 'Khác' }[props.patient.gender] ?? '—'));

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

const activeMedicalFlags = computed(() => {
    const flags = props.patient.medical_flags ?? [];
    return MEDICAL_FLAGS.filter(f => flags.includes(f.key));
});

function uploadAvatar(e) {
    const file = e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('photo', file);
    router.post(route('patients.upload-avatar', props.patient.id), fd, { forceFormData: true });
}

function submitActivity() {
    actForm.post(route('crm.activities.store'), {
        onSuccess: () => { showActivity.value = false; actForm.reset('content'); },
    });
}

const InfoRow = defineComponent({
    props: { label: String, value: String },
    template: `<div class="flex gap-2"><span class="text-gray-400 min-w-32 flex-shrink-0 text-xs uppercase tracking-wide">{{ label }}</span><span class="text-gray-800 break-words font-medium">{{ value }}</span></div>`,
});
</script>
