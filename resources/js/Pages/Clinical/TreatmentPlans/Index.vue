<template>
    <AppLayout title="Kế hoạch điều trị">
        <div class="space-y-4">

            <!-- ── Header ──────────────────────────────────────────────── -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-800">
                    Kế hoạch điều trị
                    <span class="ml-1.5 text-sm font-normal text-gray-400">({{ filtered.length }})</span>
                </h2>
                <div class="flex items-center gap-2">
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
                    <Link v-if="can('treatment_plans.create')" :href="route('clinical.treatment-plans.create')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                        + Tạo kế hoạch
                    </Link>
                </div>
            </div>

            <!-- ── Summary stats ────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Nháp -->
                <button @click="toggleStatusGroup('draft')"
                    :class="['text-left rounded-xl border px-4 py-3 transition-all',
                        activeStatusGroup === 'draft'
                            ? 'bg-gray-700 border-gray-700 text-white shadow-md'
                            : 'bg-white border-gray-200 hover:border-gray-400']">
                    <p :class="['text-xs font-medium', activeStatusGroup === 'draft' ? 'text-gray-300' : 'text-gray-500']">Nháp</p>
                    <p :class="['text-2xl font-bold mt-0.5', activeStatusGroup === 'draft' ? 'text-white' : 'text-gray-700']">{{ summary.draft }}</p>
                    <p :class="['text-xs mt-0.5', activeStatusGroup === 'draft' ? 'text-gray-400' : 'text-gray-400']">kế hoạch</p>
                </button>
                <!-- Chưa điều trị -->
                <button @click="toggleStatusGroup('not_started')"
                    :class="['text-left rounded-xl border px-4 py-3 transition-all',
                        activeStatusGroup === 'not_started'
                            ? 'bg-amber-500 border-amber-500 text-white shadow-md'
                            : 'bg-white border-amber-200 hover:border-amber-400']">
                    <p :class="['text-xs font-medium', activeStatusGroup === 'not_started' ? 'text-amber-100' : 'text-amber-600']">Chưa điều trị</p>
                    <p :class="['text-2xl font-bold mt-0.5', activeStatusGroup === 'not_started' ? 'text-white' : 'text-amber-700']">{{ summary.notStarted }}</p>
                    <p :class="['text-xs mt-0.5', activeStatusGroup === 'not_started' ? 'text-amber-100' : 'text-amber-400']">chờ điều trị</p>
                </button>
                <!-- Đang điều trị -->
                <button @click="toggleStatusGroup('in_progress')"
                    :class="['text-left rounded-xl border px-4 py-3 transition-all',
                        activeStatusGroup === 'in_progress'
                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-md'
                            : 'bg-white border-indigo-200 hover:border-indigo-400']">
                    <p :class="['text-xs font-medium', activeStatusGroup === 'in_progress' ? 'text-indigo-200' : 'text-indigo-600']">Đang điều trị</p>
                    <p :class="['text-2xl font-bold mt-0.5', activeStatusGroup === 'in_progress' ? 'text-white' : 'text-indigo-700']">{{ summary.inProgress }}</p>
                    <p :class="['text-xs mt-0.5', activeStatusGroup === 'in_progress' ? 'text-indigo-200' : 'text-indigo-400']">đang thực hiện</p>
                </button>
                <!-- Hoàn thành -->
                <button @click="toggleStatusGroup('completed')"
                    :class="['text-left rounded-xl border px-4 py-3 transition-all',
                        activeStatusGroup === 'completed'
                            ? 'bg-emerald-600 border-emerald-600 text-white shadow-md'
                            : 'bg-white border-emerald-200 hover:border-emerald-400']">
                    <p :class="['text-xs font-medium', activeStatusGroup === 'completed' ? 'text-emerald-100' : 'text-emerald-600']">Hoàn thành</p>
                    <p :class="['text-2xl font-bold mt-0.5', activeStatusGroup === 'completed' ? 'text-white' : 'text-emerald-700']">{{ summary.completed }}</p>
                    <p :class="['text-xs mt-0.5', activeStatusGroup === 'completed' ? 'text-emerald-100' : 'text-emerald-400']">kế hoạch</p>
                </button>
            </div>

            <!-- ── Filters ──────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                <!-- Row 1: search + status + branch + doctor -->
                <div class="flex flex-wrap gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input v-model="search" type="text" placeholder="Tên BN, mã KH, bác sĩ, chi nhánh, ghi chú..."
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
                    <select v-model="filterDoctor"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                        <option value="">Tất cả bác sĩ</option>
                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>

                <!-- Row 2: date range + presets + quick filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs text-gray-500 font-medium">Ngày tạo:</span>
                    <input v-model="dateFrom" type="date"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"/>
                    <span class="text-gray-400 text-xs">→</span>
                    <input v-model="dateTo" type="date"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"/>

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
                        <!-- Quick: đang điều trị -->
                        <button @click="filterInProgress = !filterInProgress"
                            :class="['text-xs px-2.5 py-1.5 rounded-lg border font-medium transition-colors',
                                filterInProgress ? 'bg-indigo-600 text-white border-indigo-600' : 'border-indigo-200 text-indigo-600 hover:bg-indigo-50']">
                            🦷 Đang điều trị
                        </button>
                        <!-- Quick: có lịch TT -->
                        <button @click="filterHasSchedule = !filterHasSchedule"
                            :class="['text-xs px-2.5 py-1.5 rounded-lg border font-medium transition-colors',
                                filterHasSchedule ? 'bg-emerald-600 text-white border-emerald-600' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50']">
                            📅 Có lịch TT
                        </button>
                        <!-- Quick: dữ liệu lỗi -->
                        <button @click="filterDataIssue = !filterDataIssue"
                            :class="['text-xs px-2.5 py-1.5 rounded-lg border font-medium transition-colors',
                                filterDataIssue ? 'bg-rose-600 text-white border-rose-600' : 'border-rose-200 text-rose-600 hover:bg-rose-50']">
                            ⚠ Dữ liệu lỗi
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
            </div>

            <!-- ── Controls ─────────────────────────────────────────────── -->
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>Hiển thị <strong class="text-gray-700">{{ paginated.length }}</strong> / {{ filtered.length }}
                    <span v-if="filtered.length < allPlans.length" class="text-gray-400">(tổng {{ allPlans.length }})</span>
                </span>
                <select v-model="perPage"
                    class="border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none">
                    <option :value="10">10/trang</option>
                    <option :value="20">20/trang</option>
                    <option :value="50">50/trang</option>
                    <option :value="100">100/trang</option>
                    <option value="all">Tất cả</option>
                </select>
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

            <!-- ── Empty ─────────────────────────────────────────────────── -->
            <div v-else-if="filtered.length === 0"
                class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400">
                {{ hasFilters ? 'Không tìm thấy kế hoạch phù hợp' : 'Chưa có kế hoạch điều trị nào' }}
            </div>

            <!-- ── LIST VIEW ─────────────────────────────────────────────── -->
            <div v-else-if="!loading && !loadError && viewMode === 'list'" class="bg-white rounded-xl border border-gray-200 overflow-hidden overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <button @click="toggleSort('patient')" class="flex items-center gap-1 hover:text-gray-800">
                                    Khách hàng <SortIcon :field="'patient'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">
                                <button @click="toggleSort('doctor')" class="flex items-center gap-1 hover:text-gray-800">
                                    Bác sĩ / Chi nhánh <SortIcon :field="'doctor'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Trạng thái</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">
                                <button @click="toggleSort('start_date_raw')" class="flex items-center gap-1 hover:text-gray-800">
                                    Ngày điều trị <SortIcon :field="'start_date_raw'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <button @click="toggleSort('net_total')" class="flex items-center gap-1 hover:text-gray-800 ml-auto">
                                    Giá trị <SortIcon :field="'net_total'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide hidden xl:table-cell">
                                <button @click="toggleSort('payment_schedule_total')" class="flex items-center gap-1 hover:text-gray-800 ml-auto">
                                    Lịch TT <SortIcon :field="'payment_schedule_total'" :current="sortBy" :dir="sortDir"/>
                                </button>
                            </th>
                            <th class="px-5 py-3 w-14"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in paginated" :key="p.id"
                            class="hover:bg-indigo-50/30 transition-colors group">
                            <!-- Khách hàng + mã -->
                            <td class="px-5 py-4">
                                <Link :href="route('clinical.treatment-plans.show', p.id)"
                                    class="font-semibold text-gray-900 hover:text-indigo-600 transition-colors text-sm leading-snug block">
                                    {{ p.patient }}
                                </Link>
                                <span class="font-mono text-[11px] text-gray-400 mt-0.5 block">{{ p.code }}</span>
                            </td>
                            <!-- Bác sĩ + chi nhánh -->
                            <td class="px-5 py-4 hidden md:table-cell">
                                <p class="text-sm text-gray-700">{{ p.doctor }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ p.branch }}</p>
                            </td>
                            <!-- Trạng thái -->
                            <td class="px-5 py-4">
                                <StatusBadge :color="p.status_color">{{ p.status_label }}</StatusBadge>
                            </td>
                            <!-- Ngày điều trị -->
                            <td class="px-5 py-4 hidden sm:table-cell">
                                <span :class="p.start_date === '—' ? 'text-gray-300 text-sm' : 'text-sm text-gray-700'">
                                    {{ p.start_date }}
                                </span>
                            </td>
                            <!-- Giá trị -->
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-800 tabular-nums">{{ formatVnd(p.net_total) }}</span>
                            </td>
                            <!-- Lịch TT -->
                            <td class="px-5 py-4 text-right hidden xl:table-cell whitespace-nowrap">
                                <template v-if="p.payment_schedule_count > 0">
                                    <span class="text-sm font-semibold text-emerald-700 tabular-nums">{{ formatVnd(p.payment_schedule_total) }}</span>
                                    <span class="block text-[11px] text-gray-400 mt-0.5">{{ p.payment_schedule_count }} đợt</span>
                                </template>
                                <span v-else class="text-gray-300 text-sm">—</span>
                            </td>
                            <!-- Xem -->
                            <td class="px-5 py-4 text-right">
                                <Link :href="route('clinical.treatment-plans.show', p.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-medium opacity-0 group-hover:opacity-100 transition-all hover:bg-indigo-100 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Xem
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── GRID VIEW ─────────────────────────────────────────────── -->
            <div v-else-if="!loading && !loadError" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <Link v-for="p in paginated" :key="p.id"
                    :href="route('clinical.treatment-plans.show', p.id)"
                    class="bg-white rounded-xl border border-gray-200 hover:border-primary-200 hover:shadow-md transition-all p-4 flex flex-col gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-mono text-xs text-gray-400">{{ p.code }}</span>
                        <StatusBadge :color="p.status_color" class="flex-shrink-0 text-xs">{{ p.status_label }}</StatusBadge>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 truncate">{{ p.patient }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ p.doctor }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span class="truncate">{{ p.branch }}</span>
                    </div>
                    <p v-if="p.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 truncate">
                        📝 {{ p.notes }}
                    </p>
                    <div class="mt-auto pt-2 border-t border-gray-100 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Giá trị KH</span>
                            <span class="text-sm font-semibold text-primary-700 tabular-nums">{{ formatVnd(p.net_total) }}</span>
                        </div>
                        <div v-if="p.payment_schedule_count > 0" class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Lịch TT ({{ p.payment_schedule_count }} đợt)</span>
                            <span class="text-sm font-semibold text-emerald-600 tabular-nums">{{ formatVnd(p.payment_schedule_total) }}</span>
                        </div>
                        <div v-if="p.start_date !== '—'" class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Ngày điều trị</span>
                            <span class="text-xs text-gray-600 font-medium">{{ p.start_date }}</span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- ── Pagination ────────────────────────────────────────────── -->
            <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 py-2">
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
import { usePermission } from '@/composables/usePermission';
import { useCurrency } from '@/composables/useCurrency';

const SortIcon = {
    props: ['field', 'current', 'dir'],
    template: `<span class="inline-block w-3" :class="field === current ? 'text-primary-600' : 'text-gray-300'">
        {{ field === current ? (dir === 'asc' ? '↑' : '↓') : '↕' }}
    </span>`,
};

const { hasPermission: can } = usePermission();
const { formatVnd } = useCurrency();
const props = defineProps({ statuses: Array, branches: Array, doctors: Array });

// ── Data fetch ────────────────────────────────────────────────────────────────
const allPlans  = ref([]);
const loading   = ref(true);
const loadError = ref(false);

async function loadData() {
    loading.value   = true;
    loadError.value = false;
    try {
        const res = await fetch(route('clinical.treatment-plans.data'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        allPlans.value = await res.json();
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(loadData);

// ── State ────────────────────────────────────────────────────────────────────
const viewMode         = ref(localStorage.getItem('tp_view') || 'list');
const perPage          = ref(localStorage.getItem('tp_per') === 'all' ? 'all' : Number(localStorage.getItem('tp_per') || 20));
const page             = ref(1);
const search           = ref('');
const filterStatus     = ref('');
const filterBranch     = ref('');
const filterDoctor     = ref('');
const dateFrom         = ref('');
const dateTo           = ref('');
const filterInProgress  = ref(false);
const filterHasSchedule = ref(false);
const filterDataIssue   = ref(false);
const activeStatusGroup = ref('');
const sortBy           = ref('');
const sortDir          = ref('desc');
const activePreset     = ref('');

watch(viewMode, v => localStorage.setItem('tp_view', v));
watch(perPage,  v => localStorage.setItem('tp_per', String(v)));
watch([search, filterStatus, filterBranch, filterDoctor, dateFrom, dateTo, filterInProgress, filterHasSchedule, filterDataIssue, activeStatusGroup, perPage],
    () => { page.value = 1; });

// ── Date presets ─────────────────────────────────────────────────────────────
const today = new Date().toISOString().split('T')[0];

const datePresets = [
    { label: 'Hôm nay', from: today, to: today },
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
        dateFrom.value    = '';
        dateTo.value      = '';
        activePreset.value = '';
    } else {
        dateFrom.value    = p.from;
        dateTo.value      = p.to;
        activePreset.value = p.label;
    }
}

watch([dateFrom, dateTo], () => {
    const preset = datePresets.find(p => p.from === dateFrom.value && p.to === dateTo.value);
    activePreset.value = preset ? preset.label : '';
});

// ── Filtering ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
    let list = allPlans.value;
    if (filterStatus.value)  list = list.filter(p => p.status === filterStatus.value);
    if (filterBranch.value)  list = list.filter(p => p.branch_id == filterBranch.value);
    if (filterDoctor.value)  list = list.filter(p => p.doctor_id == filterDoctor.value);
    if (dateFrom.value)      list = list.filter(p => p.created_at_raw >= dateFrom.value);
    if (dateTo.value)        list = list.filter(p => p.created_at_raw <= dateTo.value);
    if (activeStatusGroup.value === 'draft')       list = list.filter(p => p.status === 'draft');
    else if (activeStatusGroup.value === 'not_started') list = list.filter(p => p.status === 'approved');
    else if (activeStatusGroup.value === 'in_progress') list = list.filter(p => p.status === 'in_progress');
    else if (activeStatusGroup.value === 'completed')   list = list.filter(p => p.status === 'completed');
    if (filterInProgress.value)  list = list.filter(p => p.status === 'in_progress');
    if (filterHasSchedule.value) list = list.filter(p => p.payment_schedule_count > 0);
    if (filterDataIssue.value)   list = list.filter(p => p.has_data_issue);
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(p =>
            (p.patient      || '').toLowerCase().includes(q) ||
            (p.code         || '').toLowerCase().includes(q) ||
            (p.doctor       || '').toLowerCase().includes(q) ||
            (p.branch       || '').toLowerCase().includes(q) ||
            (p.status_label || '').toLowerCase().includes(q) ||
            (p.notes        || '').toLowerCase().includes(q) ||
            (p.created_at   || '').includes(q)
        );
    }
    return list;
});

// ── Sorting ────────────────────────────────────────────────────────────────────
const sorted = computed(() => {
    if (!sortBy.value) return filtered.value;
    return [...filtered.value].sort((a, b) => {
        let va = a[sortBy.value] ?? '';
        let vb = b[sortBy.value] ?? '';
        if (typeof va === 'string') va = va.toLowerCase();
        if (typeof vb === 'string') vb = vb.toLowerCase();
        if (va < vb) return sortDir.value === 'asc' ? -1 : 1;
        if (va > vb) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

function toggleStatusGroup(group) {
    activeStatusGroup.value = activeStatusGroup.value === group ? '' : group;
    filterStatus.value = '';
}

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

// ── Summary ────────────────────────────────────────────────────────────────────
const summary = computed(() => ({
    draft:      allPlans.value.filter(p => p.status === 'draft').length,
    notStarted: allPlans.value.filter(p => p.status === 'approved').length,
    inProgress: allPlans.value.filter(p => p.status === 'in_progress').length,
    completed:  allPlans.value.filter(p => p.status === 'completed').length,
}));

// ── Helpers ────────────────────────────────────────────────────────────────────
const hasFilters = computed(() =>
    !!(search.value || filterStatus.value || filterBranch.value || filterDoctor.value ||
       dateFrom.value || dateTo.value || filterInProgress.value || filterHasSchedule.value ||
       filterDataIssue.value || activeStatusGroup.value)
);

function clearFilters() {
    search.value          = '';
    filterStatus.value    = '';
    filterBranch.value    = '';
    filterDoctor.value    = '';
    dateFrom.value        = '';
    dateTo.value          = '';
    filterInProgress.value  = false;
    filterHasSchedule.value = false;
    filterDataIssue.value   = false;
    activeStatusGroup.value = '';
    activePreset.value      = '';
}

// ── Export CSV ─────────────────────────────────────────────────────────────────
function exportCsv() {
    const headers = ['Mã KH','Khách hàng','Bác sĩ','Chi nhánh','Giá trị KH','Tổng lịch TT','Số đợt','Trạng thái','Ngày điều trị','Ngày tạo'];
    const rows = sorted.value.map(p => [
        p.code, p.patient, p.doctor, p.branch,
        p.net_total, p.payment_schedule_total, p.payment_schedule_count,
        p.status_label, p.start_date, p.created_at,
    ]);
    const csv = [headers, ...rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a'); a.href = url;
    a.download = `ke-hoach-${today}.csv`; a.click();
    URL.revokeObjectURL(url);
}
</script>
