<template>
    <AppLayout :title="`Thu tiền: ${invoice.code}`">
        <div class="space-y-4">

            <!-- ── Header ───────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <Link :href="route('cashier.invoices.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Hóa đơn</Link>
                            <span class="text-gray-300">/</span>
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ invoice.code }}</span>
                            <StatusBadge :color="invoice.status_color">{{ invoice.status_label }}</StatusBadge>
                            <span v-if="invoice.installment_index !== null && invoice.installment_index !== undefined"
                                class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                                Đợt {{ invoice.installment_index + 1 }}
                            </span>
                        </div>
                        <Link :href="route('patients.show', invoice.patient_id)"
                            class="text-xl font-bold text-gray-900 hover:text-indigo-600 transition-colors">
                            {{ invoice.patient }}
                        </Link>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ invoice.patient_phone }}
                            <span class="mx-1.5 text-gray-300">·</span>
                            {{ invoice.branch }}
                            <span v-if="invoice.plan_doctor" class="ml-2 text-gray-400">🦷 {{ invoice.plan_doctor }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0 flex-wrap">
                        <Link v-if="invoice.plan_id"
                            :href="route('clinical.treatment-plans.show', invoice.plan_id)"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium border border-indigo-300 bg-indigo-50 rounded-lg hover:bg-indigo-100 text-indigo-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Kế hoạch {{ invoice.plan_code }}
                        </Link>
                        <a :href="route('cashier.invoices.receipt', invoice.id)" target="_blank"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            In phiếu thu
                        </a>
                        <button v-if="!isPlanBeingDeleted && invoice.status !== 'cancelled' && invoice.status !== 'paid' && invoice.amount_paid === 0"
                            @click="doCancel"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Hủy HĐ
                        </button>
                    </div>
                </div>

                <!-- Financial summary bar -->
                <div class="mt-3 flex flex-wrap gap-4 text-sm bg-slate-800 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Tổng phải trả</span>
                        <span class="font-bold text-white text-base tabular-nums">{{ formatVnd(invoice.total) }}</span>
                    </div>
                    <div class="h-4 w-px bg-slate-600 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Đã thu</span>
                        <span class="font-bold text-emerald-400 text-base tabular-nums">{{ formatVnd(invoice.amount_paid) }}</span>
                    </div>
                    <div class="h-4 w-px bg-slate-600 hidden sm:block self-center"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Còn nợ</span>
                        <span :class="['font-bold text-base tabular-nums', invoice.amount_due > 0 ? 'text-rose-400' : 'text-emerald-400']">
                            {{ formatVnd(invoice.amount_due) }}
                        </span>
                    </div>
                    <span v-if="invoice.discount > 0" class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Giảm giá</span>
                        <span class="font-bold text-amber-300 tabular-nums">-{{ formatVnd(invoice.discount) }}</span>
                    </span>
                    <span v-if="invoice.due_date" class="flex items-center gap-2">
                        <span class="text-slate-400 text-xs">Đến hạn</span>
                        <span class="text-slate-200 text-sm">{{ invoice.due_date }}</span>
                    </span>
                    <span :class="['ml-auto self-center text-xs px-2.5 py-1 rounded-full font-semibold border',
                        invoice.amount_due <= 0
                            ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                            : 'bg-rose-500/20 text-rose-300 border-rose-500/30']">
                        {{ invoice.amount_due <= 0 ? '✓ Đã thanh toán đủ' : '⚠ Còn nợ' }}
                    </span>
                </div>
            </div>

            <!-- ── Cảnh báo KHDT đang bị xóa ──────────────────────────────────── -->
            <div v-if="isPlanBeingDeleted"
                class="mt-3 flex items-center justify-between gap-4 bg-red-50 border border-red-300 rounded-xl px-5 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-red-800">Kế hoạch điều trị đang trong thời gian xóa!</p>
                        <p class="text-xs text-red-600 mt-0.5">Hóa đơn sẽ bị xóa sau <strong>{{ countdownPlan() }}</strong>. Mọi thao tác đã bị khóa cho đến khi hoàn tác.</p>
                    </div>
                </div>
                <button @click="undoPlanDeletion"
                    class="flex-shrink-0 px-4 py-2 text-sm font-medium bg-amber-100 text-amber-700 border border-amber-300 rounded-lg hover:bg-amber-200 transition-colors whitespace-nowrap">
                    Hoàn tác xóa
                </button>
            </div>

            <!-- ── Cảnh báo thu thừa ──────────────────────────────────────────── -->
            <div v-if="invoice.overpaid_amount > 0"
                class="mt-3 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-5 py-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-sm text-amber-700">
                    Hóa đơn đang thu thừa <strong>{{ formatVnd(invoice.overpaid_amount) }}</strong> (do dịch vụ trong kế hoạch điều trị đã bị bớt/xóa). Vui lòng hoàn tiền cho khách ở form bên dưới.
                </p>
            </div>

            <!-- ── Main 3-col grid ───────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                <!-- LEFT: Payment form + history (spans 2 on lg, 3 on xl) -->
                <div class="lg:col-span-2 xl:col-span-3 space-y-4">

                    <!-- ── Payment form ────────────────────────────────────────── -->
                    <div v-if="!isPlanBeingDeleted && invoice.status !== 'cancelled' && (invoice.status !== 'paid' || invoice.overpaid_amount > 0)"
                        class="bg-white rounded-xl border border-indigo-100 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Thu tiền
                        </h3>

                        <!-- Payment method selector -->
                        <div class="mb-4">
                            <label class="text-xs text-gray-500 mb-2 block font-medium uppercase tracking-wide">Hình thức thanh toán *</label>
                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                <button v-for="m in methods" :key="m.value"
                                    @click="payForm.method = m.value"
                                    :class="['flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 text-sm font-medium transition-all',
                                        payForm.method === m.value
                                            ? methodActiveClass(m.value)
                                            : 'border-gray-100 bg-gray-50 text-gray-500 hover:border-gray-200 hover:bg-gray-100']">
                                    <span class="text-xl">{{ m.icon }}</span>
                                    <span class="text-xs">{{ m.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Amount + Date + Reference + Notes -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                            <div class="sm:col-span-1">
                                <label class="text-xs text-gray-500 mb-1.5 block font-medium">Số tiền thu (₫) *</label>
                                <input v-model="payForm.amount" type="number" :min="canRefund ? undefined : 1"
                                    :placeholder="`Còn nợ: ${formatVnd(invoice.amount_due)}`"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none tabular-nums text-right font-semibold" />
                                <p class="text-xs text-gray-400 mt-1 space-x-2">
                                    <button v-if="invoice.amount_due > 0" @click="fillFullAmount" class="text-indigo-500 hover:text-indigo-700 underline">Thu đủ: {{ formatVnd(invoice.amount_due) }}</button>
                                    <button v-if="canRefund && invoice.overpaid_amount > 0" @click="fillRefundAmount" class="text-amber-600 hover:text-amber-700 underline">Hoàn đủ: {{ formatVnd(invoice.overpaid_amount) }}</button>
                                </p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1.5 block font-medium">Ngày thu *</label>
                                <input v-model="payForm.payment_date" type="date"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1.5 block font-medium">Mã giao dịch / Tham chiếu</label>
                                <input v-model="payForm.reference" type="text" placeholder="Số chuyển khoản, mã QR..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1.5 block font-medium">Ghi chú</label>
                                <input v-model="payForm.notes" type="text" placeholder="Tùy chọn..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </div>
                        </div>



                        <p v-if="payForm.errors.amount" class="text-xs text-red-500 mb-3">{{ payForm.errors.amount }}</p>

                        <div class="flex flex-wrap gap-2 justify-end pt-3 border-t border-gray-100">
                            <Link :href="route('cashier.invoices.index')"
                                class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                                Thoát
                            </Link>
                            <button @click="submitPayment(false)" :disabled="payForm.processing"
                                class="px-4 py-2 text-sm text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 disabled:opacity-50 font-medium">
                                Lưu
                            </button>
                            <button @click="submitPayment(true)" :disabled="payForm.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Lưu &amp; In phiếu
                            </button>
                        </div>
                    </div>

                    <!-- ── Dịch vụ điều trị ────────────────────────────────────── -->
                    <div v-if="planItems.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Dịch vụ điều trị
                            </h3>
                            <span class="text-xs text-gray-400">{{ planItems.length }} dịch vụ</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-medium">Dịch vụ</th>
                                        <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">Răng</th>
                                        <th class="px-4 py-2.5 text-right font-medium">SL</th>
                                        <th class="px-4 py-2.5 text-right font-medium">Đơn giá</th>
                                        <th class="px-4 py-2.5 text-right font-medium hidden md:table-cell">Giảm giá</th>
                                        <th class="px-4 py-2.5 text-right font-medium">Thành tiền</th>
                                        <th class="px-4 py-2.5 text-left font-medium hidden lg:table-cell">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="item in planItems" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-gray-800">{{ item.service_name }}</p>
                                            <p v-if="item.notes" class="text-xs text-gray-400">{{ item.notes }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell">
                                            <span v-if="item.tooth_number"
                                                class="inline-block bg-blue-50 text-blue-700 text-xs font-mono px-1.5 py-0.5 rounded">
                                                {{ item.tooth_number }}
                                            </span>
                                            <span v-else class="text-gray-300">—</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-gray-600">{{ item.quantity }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums text-gray-600">{{ formatVnd(item.unit_price) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums text-rose-500 hidden md:table-cell">
                                            {{ item.discount > 0 ? '-' + formatVnd(item.discount) : '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-800">{{ formatVnd(item.quantity * item.unit_price - (item.discount ?? 0)) }}</td>
                                        <td class="px-4 py-2.5 hidden lg:table-cell">
                                            <StatusBadge :color="item.status_color" class="text-xs">{{ item.status_label }}</StatusBadge>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                                    <tr>
                                        <td class="px-4 py-2.5 text-xs font-medium text-gray-500">Tổng cộng</td>
                                        <td class="hidden sm:table-cell"></td>
                                        <td class="hidden md:table-cell"></td>
                                        <td class="hidden md:table-cell"></td>
                                        <td class="hidden md:table-cell"></td>
                                        <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">
                                            {{ formatVnd(planItems.reduce((s, i) => s + i.quantity * i.unit_price - (i.discount ?? 0), 0)) }}
                                        </td>
                                        <td class="hidden lg:table-cell"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- ── Lịch sử thanh toán ──────────────────────────────────── -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Lịch sử thanh toán
                            </h3>
                            <span class="text-xs text-gray-400">{{ payments.length }} lần</span>
                        </div>
                        <div v-if="payments.length === 0" class="text-center py-8 text-gray-400 text-sm">Chưa có thanh toán</div>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-medium">Ngày</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Hình thức</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Số tiền</th>
                                    <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">Tham chiếu</th>
                                    <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">Ghi chú</th>
                                    <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Người thu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="p in payments" :key="p.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        <button @click="openDateModal(p)"
                                            class="inline-flex items-center gap-1 hover:text-emerald-700 hover:underline cursor-pointer"
                                            title="Bấm để sửa ngày thanh toán">
                                            {{ p.payment_date }}
                                            <svg class="w-2.5 h-2.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button @click="openMethodModal(p)"
                                            :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium hover:opacity-75 transition-opacity cursor-pointer', methodBadgeClass(p.method)]"
                                            title="Bấm để sửa hình thức">
                                            {{ methodIcon(p.method) }} {{ p.method_label }}
                                            <svg class="w-2.5 h-2.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold whitespace-nowrap"
                                        :class="p.amount < 0 ? 'text-red-600' : 'text-emerald-700'">
                                        {{ p.amount < 0 ? '−' : '' }}{{ formatVnd(Math.abs(p.amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs hidden sm:table-cell">{{ p.reference ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-400 text-xs hidden sm:table-cell">{{ p.notes ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ p.creator }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: Invoice detail + Discount -->
                <div class="space-y-4">

                    <!-- Chi tiết hóa đơn -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">Chi tiết hóa đơn</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tổng dịch vụ</span>
                                <span class="tabular-nums">{{ formatVnd(itemsGross) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Giảm giá</span>
                                <span class="text-rose-600 tabular-nums">-{{ formatVnd(itemsDiscount) }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-gray-700 font-medium">Thực thu</span>
                                <span class="font-bold tabular-nums">{{ formatVnd(invoice.total) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Đã thanh toán</span>
                                <span class="text-emerald-600 font-semibold tabular-nums">{{ formatVnd(invoice.amount_paid) }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="font-semibold">Còn nợ</span>
                                <span :class="['font-bold tabular-nums', invoice.amount_due > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                    {{ formatVnd(invoice.amount_due) }}
                                </span>
                            </div>
                        </div>

                        <!-- Debt status -->
                        <div v-if="debt" class="pt-2 border-t space-y-1.5 text-xs text-gray-500">
                            <div class="flex justify-between">
                                <span>Công nợ</span>
                                <StatusBadge :color="debt.status_color" class="text-xs">{{ debt.status_label }}</StatusBadge>
                            </div>
                            <div class="flex justify-between">
                                <span>Đã trả</span>
                                <span class="text-emerald-600 tabular-nums">{{ formatVnd(debt.paid) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Còn lại</span>
                                <span class="text-rose-600 font-medium tabular-nums">{{ formatVnd(debt.remaining) }}</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="invoice.notes" class="pt-2 border-t text-xs text-gray-500">
                            <p class="font-medium mb-0.5">Ghi chú</p>
                            <p>{{ invoice.notes }}</p>
                        </div>

                        <!-- Cancel reason -->
                        <div v-if="invoice.cancel_reason" class="pt-2 border-t">
                            <p class="text-xs font-medium text-red-600 mb-0.5">Lý do hủy</p>
                            <p class="text-xs text-red-500">{{ invoice.cancel_reason }}</p>
                        </div>
                    </div>

                    <!-- Discount (gated) -->
                    <div v-if="canDiscount && invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                        class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Áp dụng giảm giá</h3>
                        <div class="flex gap-2">
                            <input v-model="discountAmount" type="number" min="0" placeholder="Số tiền giảm (₫)"
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none tabular-nums" />
                            <button @click="applyDiscount"
                                class="px-3 py-2 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-medium">
                                Áp dụng
                            </button>
                        </div>
                    </div>

                    <!-- Link back to plan -->
                    <div v-if="invoice.plan_id" class="bg-indigo-50 rounded-xl border border-indigo-100 p-4">
                        <p class="text-xs text-indigo-600 font-medium mb-2">Kế hoạch điều trị</p>
                        <Link :href="route('clinical.treatment-plans.show', invoice.plan_id)"
                            class="flex items-center justify-between gap-2 text-sm text-indigo-700 hover:text-indigo-900 font-medium">
                            <span class="font-mono">{{ invoice.plan_code }}</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                        <p v-if="invoice.plan_status" class="text-xs text-indigo-500 mt-1">{{ invoice.plan_status }}</p>
                        <p v-if="invoice.plan_net_total" class="text-xs text-indigo-500 mt-0.5 tabular-nums">
                            Tổng kế hoạch: {{ formatVnd(invoice.plan_net_total) }}
                        </p>
                    </div>

                    <!-- Lịch thanh toán -->
                    <div v-if="planSchedule.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Lịch thanh toán
                            </h3>
                            <span class="text-xs text-gray-400">{{ planSchedule.length }} đợt</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="(inst, idx) in planSchedule" :key="idx"
                                :class="['px-4 py-3', invoice.installment_index === idx ? 'bg-indigo-50 border-l-2 border-l-indigo-400' : '']">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span :class="['text-xs font-semibold', invoice.installment_index === idx ? 'text-indigo-700' : 'text-gray-700']">
                                                Đợt {{ idx + 1 }}
                                            </span>
                                            <span v-if="invoice.installment_index === idx"
                                                class="text-xs bg-indigo-100 text-indigo-600 border border-indigo-200 px-1.5 py-0.5 rounded-full font-medium">
                                                Hóa đơn này
                                            </span>
                                        </div>
                                        <p v-if="inst.due_date" class="text-xs text-gray-400 mt-0.5">📅 {{ formatDate(inst.due_date) }}</p>
                                        <p v-if="inst.note" class="text-xs text-gray-500 mt-0.5 italic truncate">{{ inst.note }}</p>
                                    </div>
                                    <span class="text-xs font-bold tabular-nums text-gray-800 whitespace-nowrap flex-shrink-0">
                                        {{ formatVnd(inst.amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- ── Cancel modal ───────────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="showCancelModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="showCancelModal = false">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">

                <!-- Header -->
                <div class="flex items-start gap-3 px-5 pt-5 pb-4 border-b border-gray-100">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Xác nhận hủy hóa đơn</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Hóa đơn <span class="font-mono font-medium text-gray-700">{{ invoice.code }}</span>
                            — {{ invoice.patient }}
                        </p>
                    </div>
                </div>

                <!-- Warning notice -->
                <div class="mx-5 mt-4 px-3 py-2.5 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                    ⚠ Hành động này <strong>không thể hoàn tác</strong>. Hóa đơn sẽ bị hủy vĩnh viễn và không thể thu tiền.
                </div>

                <!-- Reason input -->
                <div class="px-5 mt-4 pb-1">
                    <label class="text-xs font-medium text-gray-700 mb-1.5 block">
                        Lý do hủy <span class="text-red-500">*</span>
                    </label>
                    <textarea v-model="cancelReason" rows="3"
                        placeholder="Nhập lý do hủy hóa đơn (bắt buộc)..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 focus:outline-none resize-none"
                        :class="cancelReasonError ? 'border-red-400 bg-red-50' : ''">
                    </textarea>
                    <p v-if="cancelReasonError" class="text-xs text-red-500 mt-1">{{ cancelReasonError }}</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 justify-end px-5 py-4">
                    <button @click="showCancelModal = false"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 font-medium">
                        Đóng
                    </button>
                    <button @click="confirmCancel"
                        class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 font-medium disabled:opacity-50"
                        :disabled="!cancelReason.trim()">
                        Xác nhận hủy
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Edit payment method modal ───────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="methodModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
                <div class="px-5 pt-5 pb-4 border-b border-amber-200 bg-amber-50 rounded-t-2xl">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <h3 class="font-semibold text-base text-amber-800">Sửa hình thức thanh toán</h3>
                    </div>
                </div>
                <div class="px-5 py-4 space-y-4 text-sm text-gray-700">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
                        ⚠ Thay đổi hình thức thanh toán sẽ ảnh hưởng đến báo cáo tài chính. Chỉ thực hiện khi nhập sai.
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-2 font-medium">Chọn hình thức mới</p>
                        <div class="grid grid-cols-3 gap-2">
                            <button v-for="m in methods" :key="m.value"
                                @click="methodModal.selected = m.value"
                                :class="['flex flex-col items-center gap-1 px-2 py-2 rounded-lg border text-xs font-medium transition-all',
                                    methodModal.selected === m.value
                                        ? methodActiveClass(m.value)
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50']">
                                <span class="text-base">{{ m.icon }}</span>
                                {{ m.label }}
                            </button>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="methodModal.confirmed" type="checkbox" class="w-4 h-4 accent-amber-600 cursor-pointer" />
                        <span class="text-xs font-medium text-gray-700">Tôi xác nhận muốn thay đổi hình thức thanh toán</span>
                    </label>
                </div>
                <div class="px-5 pb-5 flex justify-end gap-2">
                    <button @click="methodModal.open = false"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Hủy bỏ
                    </button>
                    <button @click="submitMethodChange"
                        :disabled="!methodModal.confirmed || methodModal.selected === methodModal.current"
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Edit payment date modal ─────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="dateModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
                <div class="px-5 pt-5 pb-4 border-b border-amber-200 bg-amber-50 rounded-t-2xl">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <h3 class="font-semibold text-base text-amber-800">Sửa ngày thanh toán</h3>
                    </div>
                </div>
                <div class="px-5 py-4 space-y-4 text-sm text-gray-700">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
                        ⚠ Thay đổi ngày thanh toán sẽ ảnh hưởng đến báo cáo tài chính theo ngày. Chỉ thực hiện khi nhập sai.
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-2 font-medium">Chọn ngày mới</p>
                        <input v-model="dateModal.selected" type="date" :max="todayStr"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="dateModal.confirmed" type="checkbox" class="w-4 h-4 accent-amber-600 cursor-pointer" />
                        <span class="text-xs font-medium text-gray-700">Tôi xác nhận muốn thay đổi ngày thanh toán</span>
                    </label>
                </div>
                <div class="px-5 pb-5 flex justify-end gap-2">
                    <button @click="dateModal.open = false"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Hủy bỏ
                    </button>
                    <button @click="submitDateChange"
                        :disabled="!dateModal.confirmed || !dateModal.selected || dateModal.selected === dateModal.current"
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { useCurrency } from '@/composables/useCurrency';
import dayjs from 'dayjs';

const { formatVnd } = useCurrency();
const props = defineProps({
    invoice: Object,
    payments: Array,
    debt: Object,
    plan_items: { type: Array, default: () => [] },
    plan_payment_schedule: { type: Array, default: () => [] },
    methods: Array,
    canRefund: Boolean,
    canDiscount: Boolean,
    plan_pending_deletion: { type: Object, default: null },
});

const now = ref(Date.now());
let _timer = null;
onMounted(() => { _timer = setInterval(() => { now.value = Date.now(); }, 1000); });
onUnmounted(() => { clearInterval(_timer); });

const isPlanBeingDeleted = computed(() => {
    if (!props.plan_pending_deletion) return false;
    return new Date(props.plan_pending_deletion.execute_at).getTime() > now.value;
});

function countdownPlan() {
    const ms = new Date(props.plan_pending_deletion.execute_at).getTime() - now.value;
    if (ms <= 0) return '0:00';
    const m = Math.floor(ms / 60000);
    const s = Math.floor((ms % 60000) / 1000);
    return `${m}:${String(s).padStart(2, '0')}`;
}

function undoPlanDeletion() {
    router.delete(route('pending-deletions.undo', props.plan_pending_deletion.id), { preserveScroll: true });
}

const planItems      = props.plan_items ?? [];
const planSchedule   = props.plan_payment_schedule ?? [];
const discountAmount = ref(props.invoice.discount);

const itemsGross    = computed(() => planItems.length > 0
    ? planItems.reduce((s, i) => s + i.quantity * i.unit_price, 0)
    : props.invoice.subtotal);
const itemsDiscount = computed(() => planItems.length > 0
    ? planItems.reduce((s, i) => s + (i.discount ?? 0), 0)
    : props.invoice.discount);

const showCancelModal  = ref(false);
const cancelReason     = ref('');
const cancelReasonError = ref('');

// ── Edit payment method ────────────────────────────────────────────────────
const methodModal = ref({ open: false, paymentId: null, current: '', selected: '', confirmed: false });

function openMethodModal(p) {
    methodModal.value = { open: true, paymentId: p.id, current: p.method, selected: p.method, confirmed: false };
}
function submitMethodChange() {
    if (!methodModal.value.confirmed || methodModal.value.selected === methodModal.value.current) return;
    router.patch(route('cashier.payments.update-method', methodModal.value.paymentId), {
        method: methodModal.value.selected,
    }, {
        onSuccess: () => { methodModal.value.open = false; },
        preserveScroll: true,
    });
}

// ── Edit payment date ───────────────────────────────────────────────────────
const todayStr = dayjs().format('YYYY-MM-DD');
const dateModal = ref({ open: false, paymentId: null, current: '', selected: '', confirmed: false });

function openDateModal(p) {
    dateModal.value = { open: true, paymentId: p.id, current: p.payment_date_raw, selected: p.payment_date_raw, confirmed: false };
}
function submitDateChange() {
    if (!dateModal.value.confirmed || !dateModal.value.selected || dateModal.value.selected === dateModal.value.current) return;
    router.patch(route('cashier.payments.update-date', dateModal.value.paymentId), {
        payment_date: dateModal.value.selected,
    }, {
        onSuccess: () => { dateModal.value.open = false; },
        preserveScroll: true,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return dayjs(dateStr).format('DD/MM/YYYY');
}
const methods = (props.methods ?? []).map(m => ({
    ...m,
    icon: { cash: '💵', transfer: '🏦', card: '💳', ewallet: '📱', installment: '📅', voucher: '🎟️' }[m.value] ?? '💰',
}));

const payForm = useForm({
    amount:       '',
    method:       'cash',
    payment_date: dayjs().format('YYYY-MM-DD'),
    reference:    '',
    notes:        '',
});

function fillFullAmount() {
    payForm.amount = props.invoice.amount_due;
}

function fillRefundAmount() {
    payForm.amount = -props.invoice.overpaid_amount;
}

function submitPayment(printAfter = false) {
    payForm.post(route('cashier.invoices.payments.store', props.invoice.id), {
        onSuccess: () => {
            payForm.reset('amount', 'reference', 'notes');
            if (printAfter) {
                window.open(route('cashier.invoices.receipt', props.invoice.id), '_blank');
            }
        },
    });
}

function applyDiscount() {
    router.post(route('cashier.invoices.discount', props.invoice.id), { discount: discountAmount.value });
}

function doCancel() {
    cancelReason.value     = '';
    cancelReasonError.value = '';
    showCancelModal.value  = true;
}

function confirmCancel() {
    if (!cancelReason.value.trim()) {
        cancelReasonError.value = 'Vui lòng nhập lý do hủy.';
        return;
    }
    router.post(route('cashier.invoices.cancel', props.invoice.id), {
        cancel_reason: cancelReason.value.trim(),
    }, {
        onSuccess: () => { showCancelModal.value = false; },
    });
}

function methodActiveClass(method) {
    return {
        cash:        'border-emerald-400 bg-emerald-50 text-emerald-700',
        transfer:    'border-blue-400 bg-blue-50 text-blue-700',
        card:        'border-purple-400 bg-purple-50 text-purple-700',
        ewallet:     'border-teal-400 bg-teal-50 text-teal-700',
        installment: 'border-orange-400 bg-orange-50 text-orange-700',
        voucher:     'border-pink-400 bg-pink-50 text-pink-700',
    }[method] ?? 'border-indigo-400 bg-indigo-50 text-indigo-700';
}

function methodBadgeClass(method) {
    return {
        cash:        'bg-emerald-100 text-emerald-700',
        transfer:    'bg-blue-100 text-blue-700',
        card:        'bg-purple-100 text-purple-700',
        ewallet:     'bg-teal-100 text-teal-700',
        installment: 'bg-orange-100 text-orange-700',
        voucher:     'bg-pink-100 text-pink-700',
    }[method] ?? 'bg-gray-100 text-gray-600';
}

function methodIcon(method) {
    return { cash: '💵', transfer: '🏦', card: '💳', ewallet: '📱', installment: '📅', voucher: '🎟️' }[method] ?? '💰';
}
</script>
