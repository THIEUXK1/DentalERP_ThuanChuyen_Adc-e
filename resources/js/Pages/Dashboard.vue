<template>
    <AppLayout title="Tổng quan">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tổng quan</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ dateLabel }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Date nav -->
                    <div class="flex items-center gap-1">
                        <button @click="changeDate(-1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <input v-model="dateSel" @change="changeToDate" type="date"
                            class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white" />
                        <button @click="changeDate(1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <button v-if="!isToday" @click="goToday"
                            class="px-3 py-1.5 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                            Hôm nay
                        </button>
                    </div>
                    <div v-if="branches.length > 0" class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">Chi nhánh:</span>
                        <select v-model="branchSel" @change="changeBranch"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                            <option :value="null">Tất cả</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-sm">
                    <svg class="w-5 h-5 text-blue-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-3xl font-bold">{{ kpis.todayAppts }}</p>
                    <p class="text-blue-100 text-xs mt-1">{{ isToday ? 'Lịch hẹn hôm nay' : `Lịch hẹn ngày ${shortDateLabel}` }}</p>
                </div>

                <div v-if="canFinancial" class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 text-white shadow-sm">
                    <svg class="w-5 h-5 text-emerald-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-2xl font-bold truncate">{{ formatVndShort(kpis.todayRevenue) }}</p>
                    <p class="text-emerald-100 text-xs mt-1">{{ isToday ? 'Doanh thu hôm nay' : `Doanh thu ngày ${shortDateLabel}` }}</p>
                </div>

                <div v-if="canFinancial" class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-5 text-white shadow-sm">
                    <svg class="w-5 h-5 text-rose-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-2xl font-bold truncate">{{ formatVndShort(kpis.totalOutstanding) }}</p>
                    <p class="text-rose-100 text-xs mt-1">Tổng công nợ</p>
                </div>

                <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-5 text-white shadow-sm">
                    <svg class="w-5 h-5 text-amber-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-3xl font-bold">{{ kpis.newLeads }}</p>
                    <p class="text-amber-100 text-xs mt-1">Lead mới (7 ngày)</p>
                </div>

                <div class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-2xl p-5 text-white shadow-sm">
                    <svg class="w-5 h-5 text-teal-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-3xl font-bold">{{ kpis.activePatients }}</p>
                    <p class="text-teal-100 text-xs mt-1">Khách hàng</p>
                </div>

                <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-5 text-white shadow-sm">
                    <svg class="w-5 h-5 text-violet-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <p class="text-3xl font-bold">{{ pendingTasksCount }}</p>
                    <p class="text-violet-100 text-xs mt-1">Công việc Follow-up</p>
                </div>
            </div>

            <!-- Revenue trend + Appointment breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                    <ChartCard v-if="canFinancial && revenueTrend.length > 0"
                        title="Doanh thu 30 ngày qua"
                        type="line"
                        :data="revenueChartData"
                        :height="240"
                        :options="revenueChartOptions"
                    />
                    <div v-else-if="canFinancial"
                        class="bg-white rounded-xl border border-gray-200 h-[300px] flex items-center justify-center text-gray-400 text-sm">
                        Chưa có dữ liệu doanh thu
                    </div>
                    <div v-else
                        class="bg-white rounded-xl border border-gray-200 h-[300px] flex items-center justify-center text-gray-400 text-sm">
                        Không có quyền xem doanh thu
                    </div>
                </div>

                <div>
                    <ChartCard v-if="canClinical && apptBreakdown.length > 0"
                        title="Lịch hẹn theo trạng thái"
                        type="doughnut"
                        :data="apptChartData"
                        :height="240"
                        :options="{ cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 10 } } } } }"
                    />
                    <div v-else
                        class="bg-white rounded-xl border border-gray-200 h-[300px] flex items-center justify-center text-gray-400 text-sm">
                        Chưa có lịch hẹn
                    </div>
                </div>
            </div>

            <!-- Today's schedule + Lead funnel -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- Today's schedule -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">
                            {{ isToday ? 'Lịch hẹn hôm nay' : `Lịch hẹn ngày ${shortDateLabel}` }}
                            <span v-if="todaySchedule.length" class="ml-2 text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full font-medium">
                                {{ todaySchedule.length }}
                            </span>
                        </h3>
                        <Link :href="route('schedule.appointments.index')"
                            class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                            Xem tất cả →
                        </Link>
                    </div>
                    <div v-if="todaySchedule.length === 0"
                        class="flex flex-col items-center py-12 text-gray-400">
                        <svg class="w-10 h-10 mb-2 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm">{{ isToday ? 'Không có lịch hẹn hôm nay' : 'Không có lịch hẹn ngày này' }}</p>
                    </div>
                    <ul v-else class="divide-y divide-gray-50">
                        <li v-for="a in todaySchedule" :key="a.id"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                            <span class="text-sm font-mono font-bold text-indigo-600 w-11 shrink-0">{{ a.time }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ a.patient }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ a.doctor }}</p>
                            </div>
                            <span :class="['text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap', apptBadgeClass(a.status_color)]">
                                {{ a.status_label }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Lead funnel -->
                <div>
                    <ChartCard v-if="leadFunnel.length > 0"
                        title="Pipeline Lead (30 ngày)"
                        type="bar"
                        :data="leadChartData"
                        :height="240"
                        :options="leadChartOptions"
                    />
                    <div v-else class="bg-white rounded-xl border border-gray-200 h-[300px] flex items-center justify-center text-gray-400 text-sm">
                        Chưa có dữ liệu lead
                    </div>
                </div>
            </div>

            <!-- Lịch sử thanh toán — đối chiếu trực tiếp với "Doanh thu hôm nay" -->
            <div v-if="canFinancial" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <h3 class="text-sm font-semibold text-gray-800">
                        {{ isToday ? 'Lịch sử thanh toán hôm nay' : `Lịch sử thanh toán ngày ${shortDateLabel}` }}
                        <span v-if="todayPayments.length" class="ml-2 text-xs bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full font-medium">
                            {{ todayPayments.length }}
                        </span>
                    </h3>
                    <span class="text-sm font-bold text-emerald-700 tabular-nums">{{ formatVnd(todayPaymentsTotal) }}</span>
                </div>
                <div v-if="todayPayments.length === 0" class="flex flex-col items-center py-10 text-gray-400">
                    <svg class="w-10 h-10 mb-2 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <p class="text-sm">{{ isToday ? 'Chưa có thanh toán nào hôm nay' : 'Không có thanh toán nào ngày này' }}</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/60 text-gray-500 text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Giờ</th>
                                <th class="px-4 py-2.5 text-left font-medium">Khách hàng</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">Hóa đơn</th>
                                <th class="px-4 py-2.5 text-left font-medium">Hình thức</th>
                                <th class="px-4 py-2.5 text-right font-medium">Số tiền</th>
                                <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Người thu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="p in todayPayments" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-500 whitespace-nowrap">{{ p.time }}</td>
                                <td class="px-4 py-2.5">
                                    <Link v-if="p.patient_id" :href="route('patients.show', p.patient_id)"
                                        class="text-gray-800 hover:text-indigo-600 font-medium">{{ p.patient }}</Link>
                                    <span v-else class="text-gray-800 font-medium">{{ p.patient }}</span>
                                </td>
                                <td class="px-4 py-2.5 hidden sm:table-cell">
                                    <Link v-if="p.invoice_id" :href="route('cashier.invoices.show', p.invoice_id)"
                                        class="font-mono text-xs text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ p.invoice_code }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', apptBadgeClass(p.method_color)]">
                                        {{ p.method_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold whitespace-nowrap"
                                    :class="p.amount < 0 ? 'text-red-600' : 'text-emerald-700'">
                                    {{ p.amount < 0 ? '−' : '' }}{{ formatVnd(Math.abs(p.amount)) }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs hidden md:table-cell">{{ p.creator }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Treatment plan conversion + Revenue by doctor + Revenue by service -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- Treatment plan conversion: radial progress -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-5">Tỷ lệ chốt kế hoạch điều trị</h3>
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative w-32 h-32">
                            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3.5"/>
                                <circle cx="18" cy="18" r="15.9" fill="none"
                                    stroke="#0d9488"
                                    stroke-width="3.5"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${treatmentPlanConversion.rate} ${100 - treatmentPlanConversion.rate}`"
                                    stroke-dashoffset="0"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-gray-800">{{ treatmentPlanConversion.rate }}%</span>
                                <span class="text-xs text-gray-400">chốt</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-teal-50 rounded-xl p-3 text-center">
                            <p class="text-xl font-bold text-teal-700">{{ treatmentPlanConversion.approved }}</p>
                            <p class="text-xs text-teal-500 mt-0.5">Đã duyệt</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xl font-bold text-gray-700">{{ treatmentPlanConversion.total }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Tổng kế hoạch</p>
                        </div>
                    </div>
                </div>

                <!-- Revenue by doctor -->
                <div>
                    <ChartCard v-if="canFinancial && revenueByDoctor.length > 0"
                        title="Doanh thu theo bác sĩ (30 ngày)"
                        type="bar"
                        :data="doctorChartData"
                        :height="220"
                        :options="{ indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => formatVndShort(v) }, grid: { display: false } }, y: { grid: { display: false } } } }"
                    />
                    <div v-else-if="canFinancial"
                        class="bg-white rounded-xl border border-gray-200 p-5 h-full min-h-[200px] flex items-center justify-center text-sm text-gray-400">
                        Chưa có dữ liệu
                    </div>
                </div>

                <!-- Revenue by service -->
                <div>
                    <ChartCard v-if="canFinancial && revenueByService.length > 0"
                        title="Dịch vụ cao nhất (30 ngày)"
                        type="bar"
                        :data="serviceChartData"
                        :height="220"
                        :options="{ indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => formatVndShort(v) }, grid: { display: false } }, y: { grid: { display: false } } } }"
                    />
                    <div v-else-if="canFinancial"
                        class="bg-white rounded-xl border border-gray-200 p-5 h-full min-h-[200px] flex items-center justify-center text-sm text-gray-400">
                        Chưa có dữ liệu
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Thao tác nhanh</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Link :href="route('schedule.appointments.create')"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 hover:border-blue-300 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-blue-50 group-hover:bg-blue-100 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Đặt lịch hẹn</p>
                    </Link>
                    <Link :href="route('patients.create')"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 hover:border-teal-300 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-teal-50 group-hover:bg-teal-100 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Thêm khách hàng</p>
                    </Link>
                    <Link :href="route('crm.leads.create')"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 hover:border-amber-300 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-amber-50 group-hover:bg-amber-100 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Thêm lead mới</p>
                    </Link>
                    <Link v-if="canFinancial" :href="route('cashier.debts.index')"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 hover:border-rose-300 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-rose-50 group-hover:bg-rose-100 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Thu công nợ</p>
                    </Link>
                    <Link :href="route('crm.tasks.index')"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 hover:border-violet-300 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-violet-50 group-hover:bg-violet-100 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Follow-up tasks</p>
                    </Link>
                    <Link :href="route('clinical.treatment-plans.index')"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 hover:border-indigo-300 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-indigo-50 group-hover:bg-indigo-100 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Kế hoạch điều trị</p>
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import ChartCard from '@/Components/Shared/ChartCard.vue';
import { useCurrency } from '@/composables/useCurrency';

const { formatVnd } = useCurrency();

const props = defineProps({
    kpis:                    Object,
    revenueTrend:            Array,
    apptBreakdown:           Array,
    leadFunnel:              Array,
    treatmentPlanConversion: Object,
    revenueByDoctor:         Array,
    revenueByService:        Array,
    todaySchedule:           Array,
    todayPayments:           { type: Array, default: () => [] },
    pendingTasksCount:       Number,
    branches:                Array,
    canFinancial:            Boolean,
    canClinical:             Boolean,
    selectedBranch:          [Number, null],
    selectedDate:            String,
    isToday:                 Boolean,
});

const dateLabel = computed(() =>
    dayjs(props.selectedDate).toDate().toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);
const shortDateLabel = computed(() => dayjs(props.selectedDate).format('DD/MM'));

const todayPaymentsTotal = computed(() => props.todayPayments.reduce((sum, p) => sum + p.amount, 0));

function formatVndShort(v) {
    if (v >= 1_000_000_000) return (v / 1_000_000_000).toFixed(1) + ' tỷ';
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(0) + ' tr';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + 'k';
    return v;
}

const branchSel = ref(props.selectedBranch);
const dateSel   = ref(props.selectedDate);

function goTo(date, branchId) {
    router.get(route('dashboard'), { branch_id: branchId, date }, { preserveState: true });
}
function changeBranch() {
    goTo(dateSel.value, branchSel.value);
}
function changeToDate() {
    goTo(dateSel.value, branchSel.value);
}
function changeDate(delta) {
    dateSel.value = dayjs(dateSel.value).add(delta, 'day').format('YYYY-MM-DD');
    goTo(dateSel.value, branchSel.value);
}
function goToday() {
    dateSel.value = dayjs().format('YYYY-MM-DD');
    goTo(dateSel.value, branchSel.value);
}

const apptColorMap = {
    blue:   'bg-blue-100 text-blue-700',
    indigo: 'bg-indigo-100 text-indigo-700',
    teal:   'bg-teal-100 text-teal-700',
    green:  'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    orange: 'bg-orange-100 text-orange-700',
    red:    'bg-red-100 text-red-700',
    purple: 'bg-purple-100 text-purple-700',
    pink:   'bg-pink-100 text-pink-700',
    gray:   'bg-gray-100 text-gray-600',
};
function apptBadgeClass(color) {
    return apptColorMap[color] ?? 'bg-gray-100 text-gray-600';
}

// Revenue trend
const revenueChartData = computed(() => ({
    labels: props.revenueTrend.map(r => r.day.slice(5)),
    datasets: [{
        label: 'Doanh thu',
        data: props.revenueTrend.map(r => r.revenue),
        borderColor: '#0d9488',
        backgroundColor: 'rgba(13,148,136,0.07)',
        fill: true,
        tension: 0.4,
        pointRadius: 2,
        pointHoverRadius: 5,
    }],
}));

const revenueChartOptions = {
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + formatVnd(ctx.raw) } },
    },
    scales: {
        y: { ticks: { callback: v => formatVndShort(v) }, grid: { color: 'rgba(0,0,0,0.04)' } },
        x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
    },
};

// Appointment donut
const apptDotColors = {
    booked: '#93c5fd', confirmed: '#6366f1', checked_in: '#0d9488',
    in_treatment: '#8b5cf6', completed: '#22c55e', cancelled: '#ef4444',
    no_show: '#f97316', rescheduled: '#eab308', pending: '#9ca3af',
    arrived_early: '#14b8a6', arrived_late: '#fb923c',
};
const apptStatusLabels = {
    booked: 'Đang hẹn', confirmed: 'Xác nhận', checked_in: 'Đã đến',
    in_treatment: 'Đang KT', completed: 'Xong', cancelled: 'Hủy',
    no_show: 'Không đến', rescheduled: 'Dời', pending: 'Tạm hẹn',
    arrived_early: 'Đến sớm', arrived_late: 'Đến muộn',
};
const apptChartData = computed(() => ({
    labels: props.apptBreakdown.map(r => apptStatusLabels[r.status] ?? r.status),
    datasets: [{
        data: props.apptBreakdown.map(r => r.count),
        backgroundColor: props.apptBreakdown.map(r => apptDotColors[r.status] ?? '#9ca3af'),
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

// Lead funnel
const leadStatusLabels = {
    new: 'Mới', contacted: 'Đã LH', no_answer: 'Không nghe',
    consulting: 'Tư vấn', appointment_booked: 'Đặt lịch',
    visited: 'Đã đến', quoted: 'Báo giá', considering: 'Cân nhắc',
    closed_won: 'Chốt', closed_lost: 'Mất', follow_up: 'Follow-up',
};
const leadPalette = ['#6366f1','#8b5cf6','#a78bfa','#3b82f6','#60a5fa','#0ea5e9','#14b8a6','#22d3ee','#f59e0b','#f97316','#ef4444'];
const leadChartData = computed(() => ({
    labels: props.leadFunnel.map(r => leadStatusLabels[r.status] ?? r.status),
    datasets: [{
        label: 'Lead',
        data: props.leadFunnel.map(r => r.count),
        backgroundColor: props.leadFunnel.map((_, i) => leadPalette[i % leadPalette.length]),
        borderRadius: 6,
    }],
}));
const leadChartOptions = {
    plugins: { legend: { display: false } },
    scales: {
        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { precision: 0 } },
        x: { grid: { display: false } },
    },
};

// Revenue by doctor
const doctorChartData = computed(() => ({
    labels: props.revenueByDoctor.map(r => r.name),
    datasets: [{
        label: 'Doanh thu',
        data: props.revenueByDoctor.map(r => r.revenue),
        backgroundColor: '#0d9488',
        borderRadius: 4,
    }],
}));

// Revenue by service
const serviceChartData = computed(() => ({
    labels: props.revenueByService.map(r => r.name),
    datasets: [{
        label: 'Doanh thu',
        data: props.revenueByService.map(r => r.revenue),
        backgroundColor: '#6366f1',
        borderRadius: 4,
    }],
}));
</script>
