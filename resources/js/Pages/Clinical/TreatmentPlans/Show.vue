<template>
    <AppLayout :title="`KHDT: ${plan.code}`">
        <div class="space-y-4">

            <!-- ── Header ───────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <Link :href="route('clinical.treatment-plans.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Kế hoạch</Link>
                            <span class="text-gray-300">/</span>
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ plan.code }}</span>
                            <StatusBadge :color="plan.status_color">{{ plan.status_label }}</StatusBadge>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ plan.patient }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">
                            <span v-if="plan.doctor">🦷 {{ plan.doctor }}</span>
                            <span v-if="plan.consultant" class="ml-3">💬 {{ plan.consultant }}</span>
                            <span class="ml-3 text-gray-400">{{ plan.created_at }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <a :href="route('clinical.treatment-plans.pdf', plan.id)" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            PDF
                        </a>
                        <button v-if="canApprove && plan.status === 'quoted'" @click="doApprove"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Duyệt kế hoạch
                        </button>
                    </div>
                </div>

                <!-- Financial summary bar -->
                <div class="mt-3 flex flex-wrap gap-4 text-sm bg-slate-800 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Tổng dịch vụ</span>
                        <span class="font-bold text-white tabular-nums">{{ formatVnd(plan.total_amount) }}</span>
                    </div>
                    <div class="h-4 w-px bg-slate-600 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Giảm giá</span>
                        <span class="font-bold text-rose-400 tabular-nums">-{{ formatVnd(plan.discount_amount) }}</span>
                    </div>
                    <div class="h-4 w-px bg-slate-600 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Thực thu</span>
                        <span class="font-bold text-emerald-400 tabular-nums text-base">{{ formatVnd(plan.net_total) }}</span>
                    </div>
                    <div class="h-4 w-px bg-slate-600 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Đặt cọc</span>
                        <span class="font-bold text-amber-300 tabular-nums">{{ formatVnd(plan.deposit_amount) }}</span>
                    </div>
                    <span v-if="plan.approved_at"
                        class="ml-auto text-xs bg-teal-500/20 text-teal-300 border border-teal-500/30 px-2.5 py-1 rounded-full font-medium self-center">
                        ✓ Đã duyệt {{ plan.approved_at }}
                    </span>
                </div>
            </div>

            <!-- ── Main Grid ─────────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- LEFT: Dental chart + Add item + Items table -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Dental chart -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
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

                    <!-- Add item form — Bambu style -->
                    <div v-if="plan.is_editable && can('treatment_plans.edit')" class="bg-white rounded-xl border border-indigo-100 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Thêm dịch vụ điều trị
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <!-- Service -->
                            <div class="sm:col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Dịch vụ / Thủ thuật *</label>
                                <select v-model="addForm.service_id" @change="onServiceChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- Chọn dịch vụ --</option>
                                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <!-- Qty -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Số lượng</label>
                                <input v-model="addForm.quantity" type="number" min="1"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <!-- Tooth position -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Vị trí răng</label>
                                <input v-model="addForm.tooth_number" type="text" placeholder="VD: 11,12,21"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono" />
                            </div>
                            <!-- Unit price (auto-filled) -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Đơn giá (₫)</label>
                                <input v-model="addForm.unit_price" type="number" min="0"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none tabular-nums" />
                            </div>
                            <!-- Auto total -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Thành tiền</label>
                                <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-semibold text-indigo-700 tabular-nums">
                                    {{ formatVnd((addForm.unit_price || 0) * (addForm.quantity || 1)) }}
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <!-- Discount -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Giảm giá (₫)</label>
                                <input v-model="addForm.discount" type="number" min="0"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none tabular-nums" />
                            </div>
                            <!-- Estimated sessions -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Số buổi dự kiến</label>
                                <input v-model="addForm.estimated_sessions" type="number" min="1" placeholder="VD: 3"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <!-- Stage name -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Giai đoạn / Đợt</label>
                                <input v-model="addForm.stage_name" type="text" placeholder="VD: Giai đoạn 1"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <!-- Doctor (người thực hiện) -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Người thực hiện</label>
                                <select v-model="addForm.responsible_doctor_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn bác sĩ --</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <!-- Assistant (trợ thủ) -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Trợ thủ</label>
                                <select v-model="addForm.assistant_doctor_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn trợ thủ --</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <!-- Diagnosis -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Chẩn đoán</label>
                                <input v-model="addForm.diagnosis" type="text" placeholder="Chẩn đoán dịch vụ này..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>
                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                            <input v-model="addForm.notes" type="text" placeholder="Ghi chú nội dung điều trị..."
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        </div>
                        <div class="flex justify-end">
                            <button @click="submitAddItem" :disabled="addForm.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Thêm dịch vụ
                            </button>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden overflow-x-auto">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Danh sách dịch vụ điều trị</h3>
                            <span class="text-xs text-gray-400">{{ items.length }} dịch vụ</span>
                        </div>
                        <div v-if="items.length === 0" class="flex flex-col items-center py-10 text-gray-400">
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
                                    <th class="px-4 py-2.5 text-center font-medium">Trạng thái</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(item, idx) in items" :key="item.id" class="hover:bg-blue-50/20 transition-colors">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ item.service_name }}</p>
                                        <p v-if="item.stage_name" class="text-xs text-indigo-500 mt-0.5">📋 {{ item.stage_name }}</p>
                                        <p v-if="item.diagnosis" class="text-xs text-amber-600 mt-0.5">🔍 {{ item.diagnosis }}</p>
                                        <p v-if="item.notes" class="text-xs text-gray-400 mt-0.5">{{ item.notes }}</p>
                                        <p v-if="item.estimated_sessions" class="text-xs text-gray-400 mt-0.5">{{ item.estimated_sessions }} buổi</p>
                                        <p v-if="item.doctor_name" class="text-xs text-indigo-500 mt-0.5">
                                            🦷 {{ item.doctor_name }}
                                            <span v-if="item.assistant_name" class="text-gray-400"> · Trợ: {{ item.assistant_name }}</span>
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
                                    <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ formatVnd(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        <span v-if="item.discount" class="text-rose-500">-{{ formatVnd(item.discount) }}</span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800 tabular-nums">{{ formatVnd(item.amount || item.subtotal) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <!-- Inline status radio for in_progress plans -->
                                        <div v-if="plan.status === 'in_progress' && item.status !== 'completed'" class="flex gap-1 justify-center flex-wrap">
                                            <button v-for="st in itemStatuses" :key="st.value"
                                                :class="['text-xs px-2 py-0.5 rounded-full border transition-colors',
                                                    item.status === st.value
                                                        ? st.activeClass
                                                        : 'border-gray-200 text-gray-400 hover:border-gray-300']"
                                                @click="changeItemStatus(item.id, st.value)">
                                                {{ st.label }}
                                            </button>
                                        </div>
                                        <StatusBadge v-else :color="item.status_color">{{ item.status_label }}</StatusBadge>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button @click="openDetail(item)"
                                            class="text-indigo-500 hover:text-indigo-700 text-xs font-medium hover:underline mr-2">Chi tiết</button>
                                        <button v-if="item.status !== 'completed' && plan.status === 'in_progress'"
                                            @click="completeItem(item.id)"
                                            class="text-emerald-600 hover:text-emerald-800 text-xs font-medium mr-2 hover:underline">✓ Xong</button>
                                        <button v-if="plan.is_editable"
                                            @click="removeItem(item.id)"
                                            class="text-red-400 hover:text-red-600 text-xs hover:underline">Xóa</button>
                                    </td>
                                </tr>
                                <!-- Total row -->
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td colspan="6" class="px-4 py-3 text-right text-gray-500 text-xs font-medium">Tổng:</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 tabular-nums">{{ formatVnd(plan.total_amount) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: Summary + Status transitions + Payment schedule -->
                <div class="space-y-4">

                    <!-- Status transitions -->
                    <div v-if="transitions.length > 0" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Chuyển trạng thái
                        </h3>
                        <div class="space-y-2">
                            <button v-for="t in transitions" :key="t.value"
                                @click="doTransition(t.value)"
                                class="w-full px-3 py-2 text-xs text-left bg-white border border-gray-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors font-medium">
                                → {{ t.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Discount + Deposit + Notes edit -->
                    <div v-if="plan.is_editable" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Giảm giá / Đặt cọc / Ghi chú</h3>
                        <div class="space-y-2.5">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Giảm giá (₫)</label>
                                <input v-model="updateForm.discount_amount" type="number" min="0"
                                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none tabular-nums" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Đặt cọc (₫)</label>
                                <input v-model="updateForm.deposit_amount" type="number" min="0"
                                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none tabular-nums" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Ghi chú kế hoạch</label>
                                <textarea v-model="updateForm.notes" rows="3" placeholder="Nhập ghi chú..."
                                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                            </div>
                            <button @click="saveFinancials" :disabled="updateForm.processing"
                                class="w-full py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                                Lưu
                            </button>
                        </div>
                    </div>
                    <!-- Notes readonly when not editable -->
                    <div v-else-if="plan.notes" class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Ghi chú</h3>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ plan.notes }}</p>
                    </div>

                    <!-- Payment schedule -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Lịch thanh toán
                            </h3>
                            <button v-if="!isScheduleLocked" @click="showScheduleForm = !showScheduleForm"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Thêm đợt</button>
                        </div>
                        <div v-if="showScheduleForm" class="mb-3 space-y-2 border-b pb-3">
                            <div class="flex gap-2">
                                <input v-model="newInst.due_date" type="date"
                                    class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                                <input v-model="newInst.amount" type="number" min="0" placeholder="Số tiền ₫"
                                    class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none tabular-nums" />
                            </div>
                            <input v-model="newInst.note" type="text" placeholder="Ghi chú..."
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                            <button @click="addInstallment"
                                class="w-full py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">Thêm đợt</button>
                        </div>
                        <div v-if="schedule.length === 0" class="text-xs text-gray-400 text-center py-3">Chưa có lịch thanh toán</div>
                        <div v-else class="space-y-2">
                            <div v-for="(inst, idx) in schedule" :key="idx"
                                :class="['text-xs p-2 rounded-lg border', instInvoice(idx)?.locked ? 'bg-emerald-50 border-emerald-200' : 'bg-gray-50 border-transparent']">
                                <div class="flex items-start justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-gray-500 font-medium">Đợt {{ idx + 1 }}</span>
                                            <span v-if="instInvoice(idx)" :class="[
                                                'px-1.5 py-0.5 rounded-full font-semibold text-[10px]',
                                                instInvoice(idx).status === 'paid' ? 'bg-emerald-100 text-emerald-700' :
                                                instInvoice(idx).status === 'partial_paid' ? 'bg-amber-100 text-amber-700' :
                                                'bg-gray-100 text-gray-500'
                                            ]">{{ instInvoice(idx).status_label }}</span>
                                        </div>
                                        <p class="text-gray-700 font-medium mt-0.5">{{ inst.due_date || 'Chưa có ngày' }}</p>
                                        <p v-if="inst.note" class="text-gray-400 mt-0.5">{{ inst.note }}</p>
                                        <p v-if="instInvoice(idx)?.locked" class="text-emerald-600 mt-0.5 font-medium">
                                            Đã thu: {{ formatVnd(instInvoice(idx).amount_paid) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1.5 ml-2 flex-shrink-0">
                                        <span class="font-bold text-indigo-700 tabular-nums">{{ formatVnd(inst.amount) }}</span>
                                        <button v-if="!isScheduleLocked && !instInvoice(idx)?.locked"
                                            @click="removeInstallment(idx)"
                                            class="text-gray-300 hover:text-red-500 ml-1">✕</button>
                                        <span v-else-if="instInvoice(idx)?.locked"
                                            title="Đã có thanh toán, không thể xóa"
                                            class="text-emerald-400 ml-1 cursor-not-allowed">🔒</span>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t pt-2 flex justify-between text-xs font-bold">
                                <span class="text-gray-600">Tổng lịch</span>
                                <span class="text-indigo-700 tabular-nums">{{ formatVnd(scheduleTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Item Detail Slide-over ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="detailItem" class="fixed inset-0 z-50 flex">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/30" @click="closeDetail"></div>
                    <!-- Panel -->
                    <Transition
                        enter-active-class="transition-transform duration-300 ease-out"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transition-transform duration-200 ease-in"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full">
                        <div v-if="detailItem" class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl flex flex-col">
                            <!-- Header -->
                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">{{ detailItem.service_name }}</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Chi tiết dịch vụ điều trị</p>
                                </div>
                                <button @click="closeDetail" class="p-1.5 rounded-lg hover:bg-gray-200 text-gray-500">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

                                <!-- Status badge (readonly) -->
                                <div class="flex items-center gap-2">
                                    <StatusBadge :color="detailItem.status_color">{{ detailItem.status_label }}</StatusBadge>
                                </div>

                                <!-- Row: SL + Đơn giá -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Số lượng *</label>
                                        <input v-model="detailForm.quantity" type="number" min="1"
                                            :readonly="!plan.is_editable"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:bg-gray-50"
                                            :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Đơn giá (₫) *</label>
                                        <input v-model="detailForm.unit_price" type="number" min="0"
                                            :readonly="!plan.is_editable"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm tabular-nums focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                            :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                    </div>
                                </div>

                                <!-- Row: Giảm giá + Thành tiền -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Giảm giá (₫)</label>
                                        <input v-model="detailForm.discount" type="number" min="0"
                                            :readonly="!plan.is_editable"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm tabular-nums focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                            :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Thành tiền</label>
                                        <div class="rounded-lg border border-gray-100 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 tabular-nums">
                                            {{ formatVnd((detailForm.unit_price || 0) * (detailForm.quantity || 1) - (detailForm.discount || 0)) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Vị trí răng -->
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Vị trí răng</label>
                                    <input v-model="detailForm.tooth_number" type="text" placeholder="VD: 11,12,21"
                                        :readonly="!plan.is_editable"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                </div>

                                <!-- Giai đoạn + Số buổi -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Giai đoạn / Đợt</label>
                                        <input v-model="detailForm.stage_name" type="text" placeholder="VD: Giai đoạn 1"
                                            :readonly="!plan.is_editable"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                            :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Số buổi dự kiến</label>
                                        <input v-model="detailForm.estimated_sessions" type="number" min="1" placeholder="VD: 3"
                                            :readonly="!plan.is_editable"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                            :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                    </div>
                                </div>

                                <!-- Người thực hiện + Trợ thủ -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Người thực hiện</label>
                                        <select v-if="plan.is_editable" v-model="detailForm.responsible_doctor_id"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                            <option :value="null">-- Chọn bác sĩ --</option>
                                            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                        <div v-else class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                            {{ detailItem.doctor_name || '—' }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Trợ thủ</label>
                                        <select v-if="plan.is_editable" v-model="detailForm.assistant_doctor_id"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                            <option :value="null">-- Chọn trợ thủ --</option>
                                            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                        <div v-else class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                            {{ detailItem.assistant_name || '—' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Chẩn đoán -->
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Chẩn đoán</label>
                                    <input v-model="detailForm.diagnosis" type="text" placeholder="Chẩn đoán..."
                                        :readonly="!plan.is_editable"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'" />
                                </div>

                                <!-- Ghi chú -->
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Ghi chú</label>
                                    <textarea v-model="detailForm.notes" rows="3" placeholder="Ghi chú..."
                                        :readonly="!plan.is_editable"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"
                                        :class="plan.is_editable ? '' : 'bg-gray-50 text-gray-500'"></textarea>
                                </div>
                            </div>

                            <!-- Footer actions -->
                            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 flex items-center gap-2">
                                <button v-if="plan.is_editable" @click="saveDetail" :disabled="detailForm.processing"
                                    class="flex-1 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                                    Lưu thay đổi
                                </button>
                                <button v-if="detailItem.status !== 'completed' && plan.status === 'in_progress'"
                                    @click="completeItem(detailItem.id); closeDetail()"
                                    class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">
                                    ✓ Xong
                                </button>
                                <button v-if="plan.is_editable"
                                    @click="removeItem(detailItem.id); closeDetail()"
                                    class="px-4 py-2 text-sm border border-red-200 text-red-500 rounded-lg hover:bg-red-50 font-medium">
                                    Xóa
                                </button>
                                <button @click="closeDetail"
                                    class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-100">
                                    Đóng
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import ToothChart from '@/Components/Shared/ToothChart.vue';
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();
const props = defineProps({
    plan: Object, items: Array, services: Array,
    priceLists: Array, transitions: Array, canApprove: Boolean,
    doctors: Array,
});

const selectedTeeth    = ref([]);
const treatedTeethList = props.items.filter(i => i.tooth_number).map(i => i.tooth_number);

// Detail slide-over
const detailItem = ref(null);
const detailForm = useForm({
    quantity:               1,
    unit_price:             0,
    discount:               0,
    tooth_number:           '',
    stage_name:             '',
    estimated_sessions:     null,
    responsible_doctor_id:  null,
    assistant_doctor_id:    null,
    diagnosis:              '',
    notes:                  '',
});

function openDetail(item) {
    detailItem.value = item;
    detailForm.quantity              = item.quantity;
    detailForm.unit_price            = item.unit_price;
    detailForm.discount              = item.discount ?? 0;
    detailForm.tooth_number          = item.tooth_number ?? '';
    detailForm.stage_name            = item.stage_name ?? '';
    detailForm.estimated_sessions    = item.estimated_sessions ?? null;
    detailForm.responsible_doctor_id = item.responsible_doctor_id ?? null;
    detailForm.assistant_doctor_id   = item.assistant_doctor_id ?? null;
    detailForm.diagnosis             = item.diagnosis ?? '';
    detailForm.notes                 = item.notes ?? '';
}

function closeDetail() {
    detailItem.value = null;
}

function saveDetail() {
    detailForm.put(route('clinical.treatment-plan-items.update', detailItem.value.id), {
        onSuccess: () => closeDetail(),
    });
}

const addForm = useForm({
    service_id:           '',
    quantity:             1,
    tooth_number:         '',
    unit_price:           0,
    discount:             0,
    stage_name:           '',
    estimated_sessions:   null,
    diagnosis:            '',
    responsible_doctor_id: null,
    assistant_doctor_id:  null,
    notes:                '',
});

const updateForm = useForm({
    discount_amount: props.plan.discount_amount,
    deposit_amount:  props.plan.deposit_amount,
    notes:           props.plan.notes ?? '',
});

// Item status options for inline change
const itemStatuses = [
    { value: 'pending',     label: 'Chờ',       activeClass: 'bg-gray-100 border-gray-300 text-gray-600' },
    { value: 'in_progress', label: 'Đang làm',  activeClass: 'bg-amber-100 border-amber-300 text-amber-700' },
    { value: 'completed',   label: 'Xong',       activeClass: 'bg-emerald-100 border-emerald-400 text-emerald-700' },
];

// Payment schedule
const schedule         = ref(props.plan.payment_schedule ?? []);
const showScheduleForm = ref(false);
const newInst          = ref({ due_date: '', amount: '', note: '' });
const isScheduleLocked = computed(() => ['completed', 'cancelled'].includes(props.plan.status));
const scheduleTotal    = computed(() => schedule.value.reduce((s, i) => s + (parseInt(i.amount) || 0), 0));

// Returns invoice info for a given installment index (null if not yet invoiced)
function instInvoice(idx) {
    return props.plan.installment_invoice_map?.[idx] ?? null;
}

// Auto-fill unit price when service selected
function onServiceChange() {
    const svc = props.services.find(s => s.id == addForm.service_id);
    if (svc) addForm.unit_price = svc.selling_price ?? 0;
}

function onTeethSelect(teeth) {
    selectedTeeth.value  = teeth;
    addForm.tooth_number = teeth.join(',');
}

function submitAddItem() {
    addForm.post(route('clinical.treatment-plans.items.store', props.plan.id), {
        onSuccess: () => {
            addForm.reset('service_id', 'tooth_number', 'unit_price', 'discount', 'stage_name', 'estimated_sessions', 'diagnosis', 'responsible_doctor_id', 'assistant_doctor_id', 'notes');
            addForm.quantity = 1;
            selectedTeeth.value = [];
        },
    });
}

function removeItem(id) {
    if (confirm('Xóa dịch vụ này?')) router.delete(route('clinical.treatment-plan-items.destroy', id));
}

function completeItem(id) {
    router.post(route('clinical.treatment-plan-items.complete', id));
}

function changeItemStatus(id, status) {
    router.put(route('clinical.treatment-plan-items.update', id), { status }, { preserveState: true });
}

function addInstallment() {
    if (!newInst.value.due_date || !newInst.value.amount) return;
    schedule.value.push({ due_date: newInst.value.due_date, amount: parseInt(newInst.value.amount), note: newInst.value.note });
    newInst.value = { due_date: '', amount: '', note: '' };
    showScheduleForm.value = false;
    saveSchedule();
}

function removeInstallment(idx) {
    schedule.value.splice(idx, 1);
    saveSchedule();
}

function saveSchedule() {
    router.patch(route('clinical.treatment-plans.payment-schedule', props.plan.id), { schedule: schedule.value }, { preserveState: true });
}

function doTransition(status) {
    router.post(route('clinical.treatment-plans.transition', props.plan.id), { status });
}

function doApprove() {
    router.post(route('clinical.treatment-plans.approve', props.plan.id));
}

function saveFinancials() {
    updateForm.put(route('clinical.treatment-plans.update', props.plan.id));
}
</script>
