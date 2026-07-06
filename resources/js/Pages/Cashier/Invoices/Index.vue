<template>
    <AppLayout title="Hóa đơn khách hàng">
        <div class="space-y-4">

            <!-- ── Header ─────────────────────────────────────────────── -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-800">
                    Hóa đơn khách hàng
                    <span class="ml-1.5 text-sm font-normal text-gray-400">({{ filtered.length }})</span>
                </h2>
                <div class="flex items-center gap-2">
                    <!-- Export CSV -->
                    <button @click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Xuất CSV
                    </button>
                    <!-- View toggle -->
                    <div class="flex border border-gray-200 rounded-lg overflow-hidden">
                        <button @click="viewMode = 'list'"
                            :class="['p-1.5 transition-colors', viewMode === 'list' ? 'bg-primary-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600']">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </button>
                        <button @click="viewMode = 'grid'"
                            :class="['p-1.5 transition-colors', viewMode === 'grid' ? 'bg-primary-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600']">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Summary stats ──────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500">Hóa đơn</p>
                    <p class="text-xl font-bold text-gray-900 mt-0.5">{{ summary.count }}</p>
                    <p v-if="summary.overdue > 0" class="text-xs text-red-500 mt-0.5">{{ summary.overdue }} quá hạn</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500">Tổng giá trị</p>
                    <p class="text-xl font-bold text-gray-900 mt-0.5 tabular-nums">{{ formatVnd(summary.total) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-xs text-emerald-600">Đã thanh toán</p>
                    <p class="text-xl font-bold text-emerald-700 mt-0.5 tabular-nums">{{ formatVnd(summary.paid) }}</p>
                </div>
                <div class="bg-white rounded-xl border px-4 py-3"
                    :class="summary.due > 0 ? 'border-red-200 bg-red-50' : 'border-gray-200'">
                    <p class="text-xs" :class="summary.due > 0 ? 'text-red-600' : 'text-gray-500'">Còn nợ</p>
                    <p class="text-xl font-bold mt-0.5 tabular-nums" :class="summary.due > 0 ? 'text-red-700' : 'text-gray-400'">{{ formatVnd(summary.due) }}</p>
                </div>
            </div>

            <!-- ── Filters ────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                <!-- Row 1: search + status + branch -->
                <div class="flex flex-wrap gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input v-model="search" type="text" placeholder="Tên BN, SĐT, mã HĐ, mã kế hoạch..."
                            class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"/>
                        <button v-if="search" @click="search = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <select v-model="filterStatus"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">Tất cả trạng thái</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <select v-model="filterBranch"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>

                <!-- Row 2: date range + presets + quick filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs text-gray-500 font-medium">Đến hạn:</span>
                    <input v-model="dueDateFrom" type="date"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"/>
                    <span class="text-gray-400 text-xs">→</span>
                    <input v-model="dueDateTo" type="date"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"/>

                    <!-- Date presets -->
                    <div class="flex gap-1 flex-wrap">
                        <button v-for="p in datePresets" :key="p.label"
                            @click="applyPreset(p)"
                            :class="['text-xs px-2 py-1 rounded border transition-colors',
                                activePreset === p.label
                                    ? 'bg-primary-600 text-white border-primary-600'
                                    : 'border-gray-200 text-gray-500 hover:border-primary-400 hover:text-primary-600']">
                            {{ p.label }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2 ml-auto flex-wrap">
                        <!-- Quick: overdue -->
                        <button @click="filterOverdue = !filterOverdue"
                            :class="['inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg border font-medium transition-colors',
                                filterOverdue ? 'bg-red-600 text-white border-red-600' : 'border-red-200 text-red-600 hover:bg-red-50']">
                            <span>🔴</span> Quá hạn ({{ overdueCount }})
                        </button>
                        <!-- Quick: need collection -->
                        <button @click="filterNeedCollection = !filterNeedCollection"
                            :class="['inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg border font-medium transition-colors',
                                filterNeedCollection ? 'bg-amber-500 text-white border-amber-500' : 'border-amber-300 text-amber-700 hover:bg-amber-50']">
                            <span>💰</span> Còn nợ
                        </button>
                        <!-- Clear all -->
                        <button v-if="hasFilters" @click="clearFilters"
                            class="text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Xóa lọc
                        </button>
                    </div>
                </div>

                <!-- Patient banner -->
                <div v-if="filterPatientId" class="flex items-center justify-between text-sm bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-2">
                    <span class="text-indigo-800">Đang lọc hóa đơn của: <strong>{{ patientName }}</strong></span>
                    <button @click="filterPatientId = null" class="text-indigo-500 hover:text-indigo-700 text-xs font-medium">Xóa lọc BN</button>
                </div>

                <!-- Plan banner -->
                <div v-if="filterPlanId" class="flex items-center justify-between text-sm bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                    <span class="text-emerald-800">
                        Đang lọc theo kế hoạch:
                        <strong class="font-mono">{{ planCode }}</strong>
                    </span>
                    <button @click="filterPlanId = null" class="text-emerald-600 hover:text-emerald-800 text-xs font-medium">Xóa lọc</button>
                </div>
            </div>

            <!-- ── Controls row ───────────────────────────────────────── -->
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>Hiển thị <strong class="text-gray-700">{{ paginated.length }}</strong> / {{ filtered.length }}</span>
                <div class="flex items-center gap-2">
                    <select v-model="perPage"
                        class="border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none">
                        <option :value="10">10/trang</option>
                        <option :value="20">20/trang</option>
                        <option :value="50">50/trang</option>
                        <option :value="100">100/trang</option>
                        <option value="all">Tất cả</option>
                    </select>
                </div>
            </div>

            <!-- ── Loading ────────────────────────────────────────────────── -->
            <div v-if="loading" class="bg-white rounded-xl border border-gray-200 py-16 flex flex-col items-center gap-3 text-gray-400">
                <svg class="w-8 h-8 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <p class="text-sm">Đang tải dữ liệu...</p>
            </div>

            <!-- ── Error ──────────────────────────────────────────────────── -->
            <div v-else-if="loadError" class="bg-white rounded-xl border border-red-200 py-12 flex flex-col items-center gap-3 text-red-400">
                <p class="text-sm font-medium">Không thể tải dữ liệu</p>
                <button @click="loadData" class="text-xs px-4 py-2 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 text-red-600">Thử lại</button>
            </div>

            <!-- ── Empty ──────────────────────────────────────────────── -->
            <div v-else-if="filtered.length === 0"
                class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400">
                {{ hasFilters ? 'Không tìm thấy hóa đơn phù hợp' : 'Chưa có hóa đơn nào' }}
            </div>

            <!-- ── LIST VIEW ──────────────────────────────────────────── -->
            <div v-else-if="viewMode === 'list'" class="bg-white rounded-xl border border-gray-200 overflow-hidden overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <button @click="toggleSort('code')" class="flex items-center gap-1 font-medium hover:text-gray-900">
                                    Mã HĐ <SortIcon :field="'code'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button @click="toggleSort('patient')" class="flex items-center gap-1 font-medium hover:text-gray-900">
                                    Khách hàng <SortIcon :field="'patient'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium hidden xl:table-cell">Kế hoạch / Đợt</th>
                            <th class="px-4 py-3 text-left">
                                <button @click="toggleSort('due_date_raw')" class="flex items-center gap-1 font-medium hover:text-gray-900">
                                    Đến hạn <SortIcon :field="'due_date_raw'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right">
                                <button @click="toggleSort('total')" class="flex items-center gap-1 font-medium hover:text-gray-900 ml-auto">
                                    Tổng tiền <SortIcon :field="'total'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right hidden md:table-cell">
                                <button @click="toggleSort('amount_paid')" class="flex items-center gap-1 font-medium hover:text-gray-900 ml-auto">
                                    Đã TT <SortIcon :field="'amount_paid'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right hidden md:table-cell">
                                <button @click="toggleSort('amount_due')" class="flex items-center gap-1 font-medium hover:text-gray-900 ml-auto">
                                    Còn nợ <SortIcon :field="'amount_due'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template v-for="inv in paginated" :key="inv.id">
                            <!-- Master / standalone row -->
                            <tr :class="['hover:bg-gray-50 transition-colors', isOverdue(inv) ? 'bg-red-50/40' : isNearDue(inv) ? 'bg-amber-50/50' : '']">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ inv.code }}</td>
                                <td class="px-4 py-3">
                                    <Link :href="route('patients.show', inv.patient_id)"
                                        class="font-medium text-gray-900 hover:text-indigo-600 hover:underline">
                                        {{ inv.patient }}
                                    </Link>
                                    <p v-if="inv.patient_phone" class="text-xs text-gray-400">{{ inv.patient_phone }}</p>
                                </td>
                                <td class="px-4 py-3 hidden xl:table-cell">
                                    <div class="flex flex-col gap-0.5">
                                        <Link v-if="inv.plan_id && inv.treatment_plan_code"
                                            :href="route('clinical.treatment-plans.show', inv.plan_id)"
                                            class="font-mono text-xs text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded w-fit hover:bg-indigo-100 hover:underline">
                                            {{ inv.treatment_plan_code }}
                                        </Link>
                                        <button v-if="inv.plan_id && installmentCount(inv.plan_id) > 0"
                                            @click="toggleExpand(inv.plan_id)"
                                            class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded w-fit font-medium hover:bg-amber-100 transition-colors text-left">
                                            {{ expandedPlans.has(inv.plan_id) ? '▲' : '▼' }} {{ installmentCount(inv.plan_id) }} đợt
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="inv.due_date" :class="['text-sm', isOverdue(inv) ? 'text-red-600 font-semibold' : isNearDue(inv) ? 'text-amber-700 font-semibold' : 'text-gray-700']">
                                        {{ inv.due_date }}
                                        <span v-if="isOverdue(inv)" class="ml-1 text-xs">⚠</span>
                                    </span>
                                    <span v-if="isNearDue(inv)" class="block mt-0.5 text-xs bg-amber-100 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded-full font-medium w-fit">⏰ Sắp đến hạn</span>
                                    <span v-else-if="!inv.due_date" class="text-gray-300 text-sm">—</span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 tabular-nums">{{ formatVnd(inv.total) }}</td>
                                <td class="px-4 py-3 text-right text-green-600 tabular-nums hidden md:table-cell">{{ formatVnd(inv.amount_paid) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums hidden md:table-cell"
                                    :class="inv.amount_due > 0 ? 'text-red-600 font-medium' : 'text-gray-300'">
                                    {{ formatVnd(inv.amount_due) }}
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :color="displayStatusColor(inv)">{{ displayStatusLabel(inv) }}</StatusBadge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 justify-end flex-wrap">
                                        <!-- Hồ sơ BN -->
                                        <Link :href="route('patients.show', inv.patient_id)"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                            title="Hồ sơ bệnh nhân">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </Link>
                                        <!-- Kế hoạch điều trị -->
                                        <Link v-if="inv.plan_id"
                                            :href="route('clinical.treatment-plans.show', inv.plan_id)"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                            title="Kế hoạch điều trị">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </Link>
                                        <!-- Expand toggle (small screens) -->
                                        <button v-if="inv.plan_id && installmentCount(inv.plan_id) > 0"
                                            @click="toggleExpand(inv.plan_id)"
                                            class="text-primary-600 text-xs font-medium hover:underline xl:hidden">
                                            {{ expandedPlans.has(inv.plan_id) ? '▲' : '▼ Đợt' }}
                                        </button>
                                        <!-- Làm thanh toán -->
                                        <Link v-if="installmentCount(inv.plan_id) === 0"
                                            :href="route('cashier.invoices.show', inv.id)"
                                            class="text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 rounded-lg whitespace-nowrap transition-colors">
                                            Làm thanh toán
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Child installment rows (expanded) -->
                            <template v-if="inv.plan_id && expandedPlans.has(inv.plan_id)">
                                <tr v-for="child in getInstallments(inv.plan_id)" :key="'inst-' + child.id"
                                    class="bg-amber-50/60 border-l-4 border-l-amber-300 hover:bg-amber-50 transition-colors">
                                    <td class="px-4 py-2 pl-8 font-mono text-xs text-gray-400">{{ child.code }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs font-semibold text-amber-700">Đợt {{ child.installment_index + 1 }}</span>
                                    </td>
                                    <td class="px-4 py-2 hidden xl:table-cell text-xs text-gray-400">—</td>
                                    <td class="px-4 py-2">
                                        <span v-if="child.due_date" :class="['text-xs', isOverdue(child) ? 'text-red-600 font-semibold' : isNearDue(child) ? 'text-amber-700 font-semibold' : 'text-gray-600']">
                                            {{ child.due_date }}
                                            <span v-if="isOverdue(child)" class="ml-0.5">⚠</span>
                                        </span>
                                        <span v-if="isNearDue(child)" class="block mt-0.5 text-xs bg-amber-100 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded-full font-medium w-fit">⏰ Sắp đến hạn</span>
                                        <span v-else-if="!child.due_date" class="text-gray-300 text-xs">—</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs font-medium text-gray-700 tabular-nums">{{ formatVnd(child.total) }}</td>
                                    <td class="px-4 py-2 text-right text-green-600 text-xs tabular-nums hidden md:table-cell">{{ formatVnd(child.amount_paid) }}</td>
                                    <td class="px-4 py-2 text-right text-xs tabular-nums hidden md:table-cell"
                                        :class="child.amount_due > 0 ? 'text-red-600 font-medium' : 'text-gray-300'">
                                        {{ formatVnd(child.amount_due) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <StatusBadge :color="displayStatusColor(child)">{{ displayStatusLabel(child) }}</StatusBadge>
                                    </td>
                                    <td class="px-4 py-2">
                                        <Link :href="route('cashier.invoices.show', child.id)"
                                            class="text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 rounded-lg whitespace-nowrap transition-colors">
                                            Làm thanh toán
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- ── GRID VIEW ──────────────────────────────────────────── -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <div v-for="inv in paginated" :key="inv.id"
                    :class="['bg-white rounded-xl border transition-all p-4 flex flex-col gap-2',
                        isOverdue(inv) ? 'border-red-200' : isNearDue(inv) ? 'border-amber-300 shadow-sm shadow-amber-100' : 'border-gray-200']">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-mono text-xs text-gray-400">{{ inv.code }}</span>
                        <StatusBadge :color="inv.status_color" class="flex-shrink-0 text-xs">{{ inv.status_label }}</StatusBadge>
                    </div>
                    <div>
                        <Link :href="route('patients.show', inv.patient_id)"
                            class="font-semibold text-gray-900 hover:text-indigo-600 hover:underline truncate block">
                            {{ inv.patient }}
                        </Link>
                        <p v-if="inv.patient_phone" class="text-xs text-gray-400">{{ inv.patient_phone }}</p>
                    </div>
                    <div v-if="inv.treatment_plan_code" class="flex gap-1 flex-wrap">
                        <Link v-if="inv.plan_id"
                            :href="route('clinical.treatment-plans.show', inv.plan_id)"
                            class="font-mono text-xs text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded hover:bg-indigo-100 hover:underline">
                            {{ inv.treatment_plan_code }}
                        </Link>
                        <span v-if="installmentCount(inv.plan_id) > 0"
                            class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                            {{ installmentCount(inv.plan_id) }} đợt
                        </span>
                    </div>
                    <div v-if="inv.due_date" :class="['text-xs', isOverdue(inv) ? 'text-red-600 font-semibold' : isNearDue(inv) ? 'text-amber-700 font-semibold' : 'text-gray-500']">
                        📅 Đến hạn: {{ inv.due_date }}
                        <span v-if="isOverdue(inv)">⚠</span>
                        <span v-else-if="isNearDue(inv)" class="ml-1 inline-block bg-amber-100 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded-full font-medium">⏰ Sắp đến hạn</span>
                    </div>
                    <div class="mt-auto pt-2 border-t border-gray-100 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">Tổng</span>
                            <span class="font-semibold text-gray-800 tabular-nums">{{ formatVnd(inv.total) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">Đã TT</span>
                            <span class="text-green-600 tabular-nums">{{ formatVnd(inv.amount_paid) }}</span>
                        </div>
                        <div v-if="inv.amount_due > 0" class="flex justify-between text-xs">
                            <span class="text-red-500">Còn nợ</span>
                            <span class="text-red-600 font-semibold tabular-nums">{{ formatVnd(inv.amount_due) }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <Link :href="route('cashier.invoices.show', inv.id)"
                            class="flex-1 text-center py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                            Làm thanh toán
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Pagination ─────────────────────────────────────────── -->
            <div v-if="!loading && !loadError && totalPages > 1" class="flex items-center justify-center gap-1 py-2">
                <button @click="page = 1" :disabled="page === 1"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">«</button>
                <button @click="page--" :disabled="page === 1"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">‹</button>
                <template v-for="n in pageNumbers" :key="n">
                    <span v-if="n === '...'" class="px-2 py-1.5 text-xs text-gray-400">…</span>
                    <button v-else @click="page = n"
                        :class="['px-2.5 py-1.5 text-xs border rounded-lg transition-colors',
                            n === page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-200 hover:bg-gray-50']">
                        {{ n }}
                    </button>
                </template>
                <button @click="page++" :disabled="page === totalPages"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">›</button>
                <button @click="page = totalPages" :disabled="page === totalPages"
                    class="px-2 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">»</button>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { useCurrency } from '@/composables/useCurrency';

// ── Inline sort icon component ──────────────────────────────────────────────
const SortIcon = {
    props: ['field', 'current', 'dir'],
    template: `<span class="inline-block w-3 text-gray-300" :class="field === current ? 'text-primary-600' : ''">
        {{ field === current ? (dir === 'asc' ? '↑' : '↓') : '↕' }}
    </span>`,
};

const { formatVnd } = useCurrency();
const props = defineProps({ statuses: Array, branches: Array, init_patient_id: Number, init_plan_id: Number });

// ── Data fetch ────────────────────────────────────────────────────────────────
const invoices  = ref([]);
const loading   = ref(true);
const loadError = ref(false);

async function loadData() {
    loading.value   = true;
    loadError.value = false;
    try {
        const res = await fetch(route('cashier.invoices.data'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        invoices.value = await res.json();
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(loadData);

// ── State ───────────────────────────────────────────────────────────────────
const viewMode           = ref(localStorage.getItem('inv_view') || 'list');
const perPage            = ref(localStorage.getItem('inv_per') === 'all' ? 'all' : Number(localStorage.getItem('inv_per') || 20));
const page               = ref(1);
const search             = ref('');
const filterStatus       = ref('');
const filterBranch       = ref('');
const dueDateFrom        = ref('');
const dueDateTo          = ref('');
const filterOverdue      = ref(false);
const filterNeedCollection = ref(false);
const filterPatientId    = ref(props.init_patient_id ?? null);
const filterPlanId       = ref(props.init_plan_id    ?? null);
const sortBy             = ref('due_date_raw');
const sortDir            = ref('asc');
const activePreset       = ref('');

watch(viewMode, v => localStorage.setItem('inv_view', v));
watch(perPage,  v => localStorage.setItem('inv_per', String(v)));

// Reset to page 1 when filters change
watch([search, filterStatus, filterBranch, dueDateFrom, dueDateTo, filterOverdue, filterNeedCollection, filterPatientId, filterPlanId, perPage],
    () => { page.value = 1; });

// ── Date helpers ─────────────────────────────────────────────────────────────
const today = new Date().toISOString().split('T')[0];

function isOverdue(inv) {
    return inv.due_date_raw && inv.due_date_raw < today && inv.amount_due > 0;
}

function isNearDue(inv) {
    if (!inv.due_date_raw || inv.amount_due <= 0) return false;
    const in3Days = new Date(Date.now() + 3 * 86400000).toISOString().split('T')[0];
    return inv.due_date_raw >= today && inv.due_date_raw <= in3Days;
}

const datePresets = [
    {
        label: 'Hôm nay',
        from: today,
        to: today,
    },
    {
        label: 'Tuần này',
        from: (() => { const d = new Date(); d.setDate(d.getDate() - d.getDay() + 1); return d.toISOString().split('T')[0]; })(),
        to:   (() => { const d = new Date(); d.setDate(d.getDate() - d.getDay() + 7); return d.toISOString().split('T')[0]; })(),
    },
    {
        label: 'Tháng này',
        from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        to:   new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0],
    },
    {
        label: 'Tháng trước',
        from: new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1).toISOString().split('T')[0],
        to:   new Date(new Date().getFullYear(), new Date().getMonth(), 0).toISOString().split('T')[0],
    },
];

function applyPreset(p) {
    if (activePreset.value === p.label) {
        dueDateFrom.value = '';
        dueDateTo.value   = '';
        activePreset.value = '';
    } else {
        dueDateFrom.value  = p.from;
        dueDateTo.value    = p.to;
        activePreset.value = p.label;
    }
}

// Reset preset label when user manually changes dates
watch([dueDateFrom, dueDateTo], () => {
    const preset = datePresets.find(p => p.from === dueDateFrom.value && p.to === dueDateTo.value);
    activePreset.value = preset ? preset.label : '';
});

// ── Patient / plan name for banners ─────────────────────────────────────────
const patientName = computed(() => {
    if (!filterPatientId.value) return '';
    return invoices.value.find(i => i.patient_id === filterPatientId.value)?.patient ?? '';
});

const planCode = computed(() => {
    if (!filterPlanId.value) return '';
    return invoices.value.find(i => i.plan_id === filterPlanId.value)?.treatment_plan_code ?? `#${filterPlanId.value}`;
});

// ── Expand installment rows ──────────────────────────────────────────────────
const expandedPlans = ref(new Set());

function toggleExpand(planId) {
    const s = new Set(expandedPlans.value);
    s.has(planId) ? s.delete(planId) : s.add(planId);
    expandedPlans.value = s;
}

function getInstallments(planId) {
    if (!planId) return [];
    return [...invoices.value]
        .filter(i => i.plan_id === planId && i.installment_index !== null && i.installment_index !== undefined)
        .sort((a, b) => a.installment_index - b.installment_index);
}

function installmentCount(planId) {
    return getInstallments(planId).length;
}

// ── Filtering ────────────────────────────────────────────────────────────────
const filtered = computed(() => {
    // Installment-specific invoices are rendered as child rows under their master; exclude from main list
    let list = invoices.value.filter(i => i.installment_index === null || i.installment_index === undefined);

    if (filterPatientId.value) {
        list = list.filter(i => i.patient_id === filterPatientId.value);
    }
    if (filterPlanId.value) {
        list = list.filter(i => i.plan_id === filterPlanId.value);
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(i =>
            i.patient.toLowerCase().includes(q) ||
            (i.patient_phone || '').toLowerCase().includes(q) ||
            i.code.toLowerCase().includes(q) ||
            (i.treatment_plan_code || '').toLowerCase().includes(q)
        );
    }
    if (filterStatus.value) {
        list = list.filter(i => i.status === filterStatus.value);
    }
    if (filterBranch.value) {
        list = list.filter(i => i.branch_id == filterBranch.value);
    }
    if (dueDateFrom.value) {
        list = list.filter(i => i.due_date_raw && i.due_date_raw >= dueDateFrom.value);
    }
    if (dueDateTo.value) {
        list = list.filter(i => i.due_date_raw && i.due_date_raw <= dueDateTo.value);
    }
    if (filterOverdue.value) {
        list = list.filter(i => isOverdue(i));
    }
    if (filterNeedCollection.value) {
        list = list.filter(i => i.amount_due > 0);
    }

    return list;
});

// ── Sorting ──────────────────────────────────────────────────────────────────
const sorted = computed(() => {
    const base = sortBy.value
        ? [...filtered.value].sort((a, b) => {
            let va = a[sortBy.value] ?? '';
            let vb = b[sortBy.value] ?? '';
            if (typeof va === 'string') va = va.toLowerCase();
            if (typeof vb === 'string') vb = vb.toLowerCase();
            if (va < vb) return sortDir.value === 'asc' ? -1 : 1;
            if (va > vb) return sortDir.value === 'asc' ? 1 : -1;
            return 0;
          })
        : [...filtered.value];
    // Pin invoices due within 3 days to the top of the list
    const nearDue = base.filter(i => isNearDue(i));
    const rest    = base.filter(i => !isNearDue(i));
    return [...nearDue, ...rest];
});

function toggleSort(field) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value  = field;
        sortDir.value = 'asc';
    }
    page.value = 1;
}

// ── Pagination ────────────────────────────────────────────────────────────────
const paginated = computed(() => {
    if (perPage.value === 'all') return sorted.value;
    const size  = Number(perPage.value);
    const start = (page.value - 1) * size;
    return sorted.value.slice(start, start + size);
});

const totalPages = computed(() =>
    perPage.value === 'all' ? 1 : Math.max(1, Math.ceil(filtered.value.length / Number(perPage.value)))
);

const pageNumbers = computed(() => {
    const total = totalPages.value;
    const cur   = page.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    if (cur > 3) pages.push('...');
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
    if (cur < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

// ── Summary stats ─────────────────────────────────────────────────────────────
const summary = computed(() => ({
    count:   filtered.value.length,
    total:   filtered.value.reduce((s, i) => s + i.total,       0),
    paid:    filtered.value.reduce((s, i) => s + i.amount_paid,  0),
    due:     filtered.value.reduce((s, i) => s + i.amount_due,   0),
    overdue: filtered.value.filter(i => isOverdue(i)).length,
}));

const overdueCount = computed(() => invoices.value.filter(i => isOverdue(i)).length);

// ── Display status (simplified for cashier view) ─────────────────────────────
function displayStatusLabel(inv) {
    if (inv.status === 'cancelled') return inv.status_label;
    if (inv.amount_due <= 0)        return 'Thanh toán đủ';
    if (inv.amount_due > 0)         return 'Còn nợ';
    return inv.status_label;
}
function displayStatusColor(inv) {
    if (inv.status === 'cancelled') return inv.status_color;
    if (inv.amount_due <= 0)        return 'green';
    if (inv.amount_due > 0)         return 'red';
    return inv.status_color;
}

// ── Helpers ────────────────────────────────────────────────────────────────────
const hasFilters = computed(() =>
    !!(search.value || filterStatus.value || filterBranch.value ||
       dueDateFrom.value || dueDateTo.value || filterOverdue.value ||
       filterNeedCollection.value || filterPatientId.value || filterPlanId.value)
);

function clearFilters() {
    search.value             = '';
    filterStatus.value       = '';
    filterBranch.value       = '';
    dueDateFrom.value        = '';
    dueDateTo.value          = '';
    filterOverdue.value      = false;
    filterNeedCollection.value = false;
    filterPatientId.value    = null;
    filterPlanId.value       = null;
    activePreset.value       = '';
}

// ── Export CSV ─────────────────────────────────────────────────────────────────
function exportCsv() {
    const headers = ['Mã HĐ','Khách hàng','SĐT','Kế hoạch','Đợt','Đến hạn','Tổng tiền','Đã TT','Còn nợ','Trạng thái','Chi nhánh'];
    const rows = sorted.value.map(i => [
        i.code,
        i.patient,
        i.patient_phone,
        i.treatment_plan_code || '',
        i.installment_index !== null && i.installment_index !== undefined ? `Đợt ${i.installment_index + 1}` : '',
        i.due_date || '',
        i.total,
        i.amount_paid,
        i.amount_due,
        i.status_label,
        i.branch,
    ]);
    const csv = [headers, ...rows].map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `hoa-don-${today}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
