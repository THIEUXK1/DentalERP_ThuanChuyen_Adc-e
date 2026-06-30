<template>
    <AppLayout title="KPI Chuyên môn Nha khoa">
        <div class="space-y-4">
            <h1 class="text-xl font-bold text-gray-800">KPI Chuyên môn Nha khoa</h1>

            <!-- Summary cards -->
            <div class="grid grid-cols-6 gap-3">
                <div v-for="(val, key) in summary" :key="key" class="bg-white rounded-xl shadow p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">{{ statusLabels[key] }}</p>
                    <p class="text-base font-bold text-gray-800">{{ formatVnd(val) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select v-model="filters.employee_id" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả nhân viên</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name }}</option>
                </select>
                <input v-model="filters.period" @change="applyFilters" type="month" class="border rounded-lg px-3 py-2 text-sm" />
                <select v-model="filters.status" @change="applyFilters" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <button @click="applyFilters" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">Lọc</button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Nhân viên</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Vai trò</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Bệnh nhân</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Dịch vụ</th>
                            <th class="px-3 py-3 text-left text-xs text-gray-500 uppercase">Kỳ</th>
                            <th class="px-3 py-3 text-right text-xs text-gray-500 uppercase">KPI Pool</th>
                            <th class="px-3 py-3 text-right text-xs text-gray-500 uppercase">% Phần</th>
                            <th class="px-3 py-3 text-right text-xs text-gray-500 uppercase">KPI thực nhận</th>
                            <th class="px-3 py-3 text-center text-xs text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-3 py-3 text-right text-xs text-gray-500 uppercase">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="a in allocations.data" :key="a.id" class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-sm font-medium text-gray-800">{{ a.employee_name }}</td>
                            <td class="px-3 py-3 text-xs text-gray-500">{{ roleLabel(a.role) }}</td>
                            <td class="px-3 py-3 text-sm text-gray-600">{{ a.patient_name || '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-600 max-w-xs truncate">{{ a.service_name }}</td>
                            <td class="px-3 py-3 text-xs font-mono text-gray-500">{{ a.period }}</td>
                            <td class="px-3 py-3 text-sm text-right font-mono">{{ formatVnd(a.kpi_pool_amount) }}</td>
                            <td class="px-3 py-3 text-sm text-right">{{ a.share_percent }}%</td>
                            <td class="px-3 py-3 text-right font-bold text-gray-800">{{ formatVnd(a.final_kpi_amount) }}</td>
                            <td class="px-3 py-3 text-center">
                                <span :class="`inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-${a.status_color}-100 text-${a.status_color}-700`">
                                    {{ a.status_label }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex gap-1 justify-end">
                                    <button v-if="a.can_approve && can('dental.kpi.manage')"
                                        @click="approve(a)" class="px-2 py-1 text-xs bg-teal-600 text-white rounded">Duyệt</button>
                                    <button v-if="a.can_hold && can('dental.kpi.manage')"
                                        @click="openHold(a)" class="px-2 py-1 text-xs bg-orange-500 text-white rounded">Treo</button>
                                    <button v-if="a.status_label === 'Đang treo' && can('dental.kpi.manage')"
                                        @click="release(a)" class="px-2 py-1 text-xs bg-blue-500 text-white rounded">Mở treo</button>
                                    <button v-if="a.can_reverse && can('dental.kpi.manage')"
                                        @click="openReverse(a)" class="px-2 py-1 text-xs bg-red-500 text-white rounded">Đảo</button>
                                    <button v-if="a.status_label === 'Đã duyệt' && can('dental.kpi.manage')"
                                        @click="markPaid(a)" class="px-2 py-1 text-xs bg-green-600 text-white rounded">Trả</button>
                                </div>
                                <p v-if="a.reason" class="text-xs text-red-500 mt-1 text-right">{{ a.reason }}</p>
                            </td>
                        </tr>
                        <tr v-if="!allocations.data?.length">
                            <td colspan="10" class="px-4 py-8 text-center text-sm text-gray-400">Chưa có dữ liệu KPI</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="allocations.links" />
        </div>

        <!-- Hold Modal -->
        <div v-if="showHoldModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="text-lg font-bold text-orange-600">Treo KPI</h2>
                <textarea v-model="actionReason" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Lý do treo KPI..."></textarea>
                <div class="flex justify-end gap-3">
                    <button @click="showHoldModal = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                    <button @click="submitHold" class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm">Xác nhận treo</button>
                </div>
            </div>
        </div>

        <!-- Reverse Modal -->
        <div v-if="showReverseModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="text-lg font-bold text-red-600">Đảo KPI</h2>
                <p class="text-sm text-gray-600">Hành động này không thể hoàn tác. Nhập lý do để xác nhận.</p>
                <textarea v-model="actionReason" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Lý do đảo KPI..."></textarea>
                <div class="flex justify-end gap-3">
                    <button @click="showReverseModal = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                    <button @click="submitReverse" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Xác nhận đảo</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();
const props = defineProps({ allocations: Object, employees: Array, statuses: Array, filters: Object, summary: Object });

const filters = ref({ employee_id: props.filters?.employee_id ?? '', period: props.filters?.period ?? '', status: props.filters?.status ?? '' });
const showHoldModal = ref(false);
const showReverseModal = ref(false);
const actionReason = ref('');
const selectedAlloc = ref(null);

const statusLabels = { accrued: 'Tạm tính', pending_approval: 'Chờ duyệt', approved: 'Đã duyệt', paid: 'Đã trả', held: 'Đang treo', reversed: 'Đã đảo' };

function formatVnd(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' ₫'; }
function roleLabel(r) { return ({ main_doctor: 'BS chính', step_performer: 'Người thực hiện', assistant: 'Phụ tá' })[r] ?? r ?? '—'; }

function applyFilters() {
    router.get(route('dental.kpi.index'), filters.value, { preserveState: true });
}

function approve(a) { if (!confirm('Duyệt KPI này?')) return; router.post(route('dental.kpi.approve', a.id)); }
function release(a) { if (!confirm('Mở treo KPI?')) return; router.post(route('dental.kpi.release', a.id)); }
function markPaid(a) { if (!confirm('Đánh dấu đã trả KPI?')) return; router.post(route('dental.kpi.mark-paid', a.id)); }

function openHold(a) { selectedAlloc.value = a; actionReason.value = ''; showHoldModal.value = true; }
function submitHold() {
    if (!actionReason.value.trim()) { alert('Vui lòng nhập lý do.'); return; }
    router.post(route('dental.kpi.hold', selectedAlloc.value.id), { reason: actionReason.value }, { onSuccess: () => { showHoldModal.value = false; } });
}

function openReverse(a) { selectedAlloc.value = a; actionReason.value = ''; showReverseModal.value = true; }
function submitReverse() {
    if (!actionReason.value.trim()) { alert('Vui lòng nhập lý do.'); return; }
    router.post(route('dental.kpi.reverse', selectedAlloc.value.id), { reason: actionReason.value }, { onSuccess: () => { showReverseModal.value = false; } });
}
</script>
