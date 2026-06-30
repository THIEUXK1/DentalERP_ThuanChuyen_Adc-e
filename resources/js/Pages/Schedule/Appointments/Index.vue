<template>
    <AppLayout title="Lịch hẹn">
        <div class="space-y-3">

            <!-- ── Header ─────────────────────────────────────────────────── -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-800">
                    {{ pageTitle }}
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ calendarTotal }})</span>
                </h2>
                <div class="flex items-center gap-2">
                    <!-- View mode toggle -->
                    <div class="flex items-center bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <button v-for="v in VIEW_MODES" :key="v.key" @click="setViewMode(v.key)"
                            :title="v.title"
                            :class="['px-3 py-2 flex items-center gap-1.5 text-xs font-medium transition-colors border-r last:border-r-0 border-gray-200',
                                viewMode === v.key ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-50']">
                            <span v-html="v.icon" class="w-4 h-4 flex-shrink-0"></span>
                            <span class="hidden sm:inline">{{ v.label }}</span>
                        </button>
                    </div>
                    <!-- Báo cáo dropdown -->
                    <div class="relative" ref="reportDropdownRef">
                        <button @click="reportOpen = !reportOpen"
                            :class="['inline-flex items-center gap-1.5 px-3 py-2 border text-sm rounded-xl shadow-sm font-medium transition-colors',
                                reportOpen ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50']">
                            <svg class="w-4 h-4" :class="reportOpen ? 'text-white' : 'text-emerald-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="hidden sm:inline">Báo cáo</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="reportOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown panel -->
                        <Teleport to="body">
                            <div v-if="reportOpen"
                                class="fixed z-50 bg-white rounded-2xl border border-gray-200 shadow-xl p-4 w-80"
                                :style="reportPanelStyle">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Chọn khoảng thời gian</p>

                                <!-- Quick presets -->
                                <div class="grid grid-cols-2 gap-1.5 mb-4">
                                    <button v-for="p in reportPresets" :key="p.label"
                                        @click="openReport(p.from, p.to)"
                                        :class="['px-3 py-2 text-sm rounded-xl border text-left font-medium transition-colors',
                                            p.highlight
                                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'
                                                : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100']">
                                        {{ p.label }}
                                    </button>
                                </div>

                                <div class="border-t border-gray-100 pt-3">
                                    <p class="text-xs font-medium text-gray-500 mb-2">Tùy chọn khoảng ngày</p>
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-400 mb-1">Từ ngày</label>
                                            <input type="date" v-model="reportFrom"
                                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                        </div>
                                        <span class="text-gray-400 mt-4">→</span>
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-400 mb-1">Đến ngày</label>
                                            <input type="date" v-model="reportTo" :min="reportFrom"
                                                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                        </div>
                                    </div>
                                    <button @click="openReport(reportFrom, reportTo)"
                                        class="w-full py-2 bg-emerald-600 text-white text-sm rounded-xl hover:bg-emerald-700 font-medium">
                                        Xem báo cáo →
                                    </button>
                                </div>
                            </div>
                        </Teleport>
                    </div>
                    <button v-if="can('appointments.create')" @click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 shadow-sm font-medium">
                        + Đặt lịch
                    </button>
                </div>
            </div>

            <!-- ── Filter bar ──────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-3 flex flex-wrap items-center gap-2.5">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Tìm tên, SĐT, mã, dịch vụ..."
                        class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    <button v-if="search" @click="search = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <select v-model="branchId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="doctorId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Tất cả bác sĩ</option>
                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <select v-model="filterStatus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <!-- Per page (chỉ list/grid) -->
                <select v-if="viewMode === 'list' || viewMode === 'grid'" v-model="perPage"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option :value="20">20/trang</option>
                    <option :value="50">50/trang</option>
                    <option :value="100">100/trang</option>
                    <option value="all">Tất cả</option>
                </select>
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa lọc
                </button>
            </div>

            <!-- ── Date navigation (day / week / month) ────────────────────── -->
            <div v-if="isCalendarView" class="bg-white rounded-xl border border-gray-200 px-4 py-2.5 flex items-center justify-between gap-3">
                <button @click="navPrev" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="flex items-center gap-3">
                    <span class="text-base font-bold text-gray-800">{{ navLabel }}</span>
                    <button @click="goToday" class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-full hover:bg-indigo-50 transition-colors">Hôm nay</button>
                </div>
                <button @click="navNext" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- ══════════════════════════════════════════════════════════════
                 CHẾ ĐỘ: NGÀY (Day Timeline)
            ══════════════════════════════════════════════════════════════ -->
            <div v-if="viewMode === 'day'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Stats bar -->
                <div class="flex items-center gap-4 px-4 py-2.5 bg-gray-50 border-b border-gray-100 text-xs text-gray-500">
                    <span>{{ dayLayoutItems.length }} lịch hẹn</span>
                    <span v-for="s in daySummary" :key="s.label"
                        :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium', s.badge]">
                        {{ s.count }} {{ s.label }}
                    </span>
                </div>
                <!-- Timeline -->
                <div class="overflow-y-auto" style="max-height: 72vh">
                    <div class="relative" :style="{ height: DAY_TOTAL_H + 'px', minWidth: '500px' }">
                        <!-- Hour grid lines -->
                        <div v-for="hour in dayHours" :key="hour.val"
                            class="absolute left-0 right-0 flex"
                            :style="{ top: hour.top + 'px' }">
                            <div class="w-16 flex-shrink-0 pr-2 text-right">
                                <span class="text-xs text-gray-400 font-mono -mt-2 block">{{ hour.label }}</span>
                            </div>
                            <div class="flex-1 border-t" :class="hour.val % 2 === 0 ? 'border-gray-200' : 'border-gray-100 border-dashed'"></div>
                        </div>
                        <!-- Current time indicator -->
                        <div v-if="todayIsSelected" class="absolute left-16 right-0 z-20 flex items-center pointer-events-none"
                            :style="{ top: nowTop + 'px' }">
                            <div class="w-2 h-2 rounded-full bg-red-500 -ml-1 flex-shrink-0"></div>
                            <div class="flex-1 border-t-2 border-red-400"></div>
                            <span class="text-xs text-red-500 font-bold px-1">{{ nowLabel }}</span>
                        </div>
                        <!-- Appointment blocks -->
                        <div class="absolute left-16 right-2 top-0">
                            <div v-for="apt in dayLayoutItems" :key="apt.id"
                                class="absolute rounded-lg border-l-4 shadow-sm cursor-pointer hover:shadow-md hover:z-10 transition-all overflow-hidden group"
                                :class="statusCard(apt.status)"
                                :style="{ top: apt.top + 'px', height: apt.height + 'px', left: apt.left, width: apt.width, minHeight: '32px' }"
                                @click="router.visit(route('schedule.appointments.show', apt.id))">
                                <div class="px-2 py-1 h-full flex flex-col justify-start overflow-hidden">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <span class="text-xs font-bold leading-tight truncate">{{ apt.patient }}</span>
                                        <span class="text-xs opacity-70 flex-shrink-0">{{ timeOf(apt.scheduled_at) }}</span>
                                    </div>
                                    <span v-if="apt.height > 40" class="text-xs opacity-80 truncate leading-tight">{{ apt.service !== '—' ? apt.service : '' }}</span>
                                    <span v-if="apt.height > 56" class="text-xs opacity-60 truncate leading-tight">{{ apt.doctor !== '—' ? apt.doctor : '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════
                 CHẾ ĐỘ: TUẦN (Week View)
            ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="viewMode === 'week'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Day headers -->
                <div class="grid border-b border-gray-200" style="grid-template-columns: 52px repeat(7, 1fr)">
                    <div class="border-r border-gray-100 bg-gray-50"></div>
                    <div v-for="day in weekDays" :key="day.date"
                        :class="['p-2 text-center border-r border-gray-100 last:border-r-0',
                            day.isToday ? 'bg-indigo-50' : 'bg-gray-50']">
                        <div class="text-xs text-gray-400 font-medium">{{ day.dayName }}</div>
                        <div :class="['text-xl font-bold mt-0.5 w-8 h-8 mx-auto flex items-center justify-center rounded-full',
                            day.isToday ? 'bg-indigo-600 text-white' : 'text-gray-800']">
                            {{ day.dayNum }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ day.monthLabel }}</div>
                    </div>
                </div>
                <!-- Timeline + appointments -->
                <div class="overflow-y-auto" style="max-height: 68vh">
                    <div class="relative" :style="{ height: DAY_TOTAL_H + 'px' }">
                        <!-- Hour rows -->
                        <div v-for="hour in dayHours" :key="hour.val"
                            class="absolute left-0 right-0 flex"
                            :style="{ top: hour.top + 'px' }">
                            <div class="w-13 flex-shrink-0 pr-2 text-right" style="width:52px">
                                <span class="text-xs text-gray-400 font-mono -mt-2 block">{{ hour.label }}</span>
                            </div>
                            <div class="flex-1 border-t" :class="hour.val % 2 === 0 ? 'border-gray-200' : 'border-gray-100 border-dashed'"></div>
                        </div>
                        <!-- Current time line -->
                        <div v-if="todayIsInWeek" class="absolute z-20 flex items-center pointer-events-none"
                            :style="{ top: nowTop + 'px', left: '52px', right: 0 }">
                            <div class="w-2 h-2 rounded-full bg-red-500 -ml-1 flex-shrink-0"></div>
                            <div class="flex-1 border-t-2 border-red-400 opacity-60"></div>
                        </div>
                        <!-- Columns per day -->
                        <div class="absolute top-0 bottom-0 grid" style="left:52px; right:0; grid-template-columns: repeat(7, 1fr)">
                            <div v-for="day in weekDays" :key="day.date"
                                :class="['relative border-r border-gray-100 last:border-r-0', day.isToday ? 'bg-indigo-50/20' : '']">
                                <div v-for="apt in day.layout" :key="apt.id"
                                    class="absolute rounded border-l-4 shadow-sm cursor-pointer hover:shadow-md hover:z-10 transition-all overflow-hidden"
                                    :class="statusCard(apt.status)"
                                    :style="{ top: apt.top + 'px', height: apt.height + 'px', left: (apt.col / apt.totalCols * 100) + '%', width: (1 / apt.totalCols * 100) + '%' }"
                                    @click="router.visit(route('schedule.appointments.show', apt.id))">
                                    <div class="px-1 py-0.5 overflow-hidden h-full flex flex-col gap-0">
                                        <p class="text-[10px] font-bold leading-tight truncate opacity-80 flex-shrink-0">{{ timeOf(apt.scheduled_at) }}</p>
                                        <p class="text-[10px] font-semibold leading-tight truncate">{{ apt.patient }}</p>
                                        <p v-if="apt.height > 52" class="text-[10px] leading-tight truncate opacity-70">{{ apt.service !== '—' ? apt.service : '' }}</p>
                                    </div>
                                </div>
                                <!-- Empty day indicator -->
                                <div v-if="day.appointments.length === 0" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="text-xs text-gray-200">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Week total footer -->
                <div class="grid border-t border-gray-100 bg-gray-50/60" style="grid-template-columns: 52px repeat(7, 1fr)">
                    <div></div>
                    <div v-for="day in weekDays" :key="day.date" class="py-1.5 text-center border-r border-gray-100 last:border-r-0">
                        <span v-if="day.appointments.length > 0"
                            :class="['text-xs font-bold px-2 py-0.5 rounded-full', day.isToday ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500']">
                            {{ day.appointments.length }}
                        </span>
                        <span v-else class="text-xs text-gray-300">0</span>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════
                 CHẾ ĐỘ: THÁNG (Month Calendar)
            ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="viewMode === 'month'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Day of week headers -->
                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
                    <div v-for="d in ['T.Hai','T.Ba','T.Tư','T.Năm','T.Sáu','T.Bảy','CN']" :key="d"
                        class="py-2 text-center text-xs font-semibold text-gray-500 border-r border-gray-100 last:border-r-0">
                        {{ d }}
                    </div>
                </div>
                <!-- Calendar grid -->
                <div class="grid grid-cols-7">
                    <div v-for="cell in monthCalendar" :key="cell.date"
                        :class="['border-r border-b border-gray-100 last:border-r-0 p-1.5 min-h-24 flex flex-col',
                            !cell.currentMonth ? 'bg-gray-50/70' : '',
                            cell.isToday ? 'bg-blue-50/60' : '']">
                        <!-- Day number -->
                        <div class="flex items-center justify-between mb-1">
                            <span :class="['text-sm font-bold w-7 h-7 flex items-center justify-center rounded-full flex-shrink-0',
                                cell.isToday ? 'bg-indigo-600 text-white shadow-sm'
                                : cell.currentMonth ? 'text-gray-700' : 'text-gray-300']">
                                {{ cell.dayNum }}
                            </span>
                            <span v-if="cell.appointments.length > 0"
                                class="text-xs text-gray-400 font-medium pr-0.5">
                                {{ cell.appointments.length }}
                            </span>
                        </div>
                        <!-- Appointment pills -->
                        <div class="flex-1 space-y-0.5 overflow-hidden">
                            <div v-for="apt in cell.appointments.slice(0, 4)" :key="apt.id"
                                :class="['text-xs rounded px-1.5 py-0.5 truncate cursor-pointer border-l-2 leading-tight hover:opacity-80 transition-opacity', statusCard(apt.status)]"
                                @click="router.visit(route('schedule.appointments.show', apt.id))">
                                <span class="font-mono font-semibold">{{ timeOf(apt.scheduled_at) }}</span>
                                <span class="ml-1 hidden sm:inline">{{ apt.patient }}</span>
                            </div>
                            <div v-if="cell.appointments.length > 4"
                                class="text-xs text-indigo-500 font-medium pl-1 cursor-pointer hover:text-indigo-700"
                                @click="jumpToDay(cell.date)">
                                +{{ cell.appointments.length - 4 }} khác →
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════
                 CHẾ ĐỘ: DANH SÁCH
            ══════════════════════════════════════════════════════════════ -->
            <template v-else-if="viewMode === 'list'">
                <!-- Today toggle (chỉ list) -->
                <div class="flex items-center gap-3 flex-wrap text-sm">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <div @click="toggleTodayOnly"
                            :class="['w-10 h-5 rounded-full relative transition-colors cursor-pointer',
                                todayOnly ? 'bg-indigo-600' : 'bg-gray-300']">
                            <div :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform',
                                todayOnly ? 'translate-x-5' : 'translate-x-0.5']"></div>
                        </div>
                        <span class="text-gray-700 font-medium">Hôm nay</span>
                    </label>
                    <template v-if="todayOnly">
                        <div class="w-px h-4 bg-gray-200"></div>
                        <button @click="changeDate(-1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <input type="date" v-model="date"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                        <button @click="changeDate(1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </template>
                    <span class="text-xs text-gray-400 ml-auto">
                        {{ paginatedAppointments.length }} / {{ filteredAppointments.length }} lịch hẹn
                    </span>
                </div>

                <div v-if="filteredAppointments.length === 0"
                    class="bg-white rounded-xl border border-gray-200 p-10 text-center text-gray-400">
                    {{ hasActiveFilters || todayOnly ? 'Không tìm thấy lịch hẹn phù hợp' : 'Chưa có lịch hẹn nào' }}
                </div>
                <div v-else class="space-y-1.5">
                    <div v-for="a in paginatedAppointments" :key="a.id"
                        :class="['flex items-stretch bg-white rounded-xl border hover:shadow-sm transition-all overflow-hidden', statusBorder(a.status)]">
                        <div :class="['w-1.5 flex-shrink-0', statusStripe(a.status)]"></div>
                        <Link :href="route('schedule.appointments.show', a.id)" class="flex flex-1 items-center gap-4 px-4 py-3 min-w-0">
                            <div class="w-16 text-center flex-shrink-0">
                                <p class="text-xs font-semibold text-gray-500">{{ displayDate(a.scheduled_at) }}</p>
                                <p class="text-lg font-bold text-gray-900 leading-tight">{{ timeOf(a.scheduled_at) }}</p>
                                <p class="text-xs text-gray-400">→ {{ a.ends_at }}</p>
                            </div>
                            <div class="w-px self-stretch bg-gray-100 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-base font-bold text-gray-900 truncate">{{ a.patient }}</p>
                                    <span v-if="a.patient_phone" class="text-xs text-gray-400 font-mono">{{ a.patient_phone }}</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate mt-0.5">
                                    <span v-if="a.service !== '—'">{{ a.service }}</span>
                                    <span v-if="a.service !== '—' && a.doctor !== '—'" class="text-gray-300 mx-1">·</span>
                                    <span v-if="a.doctor !== '—'">{{ a.doctor }}</span>
                                    <span v-if="a.chair !== '—'" class="text-gray-300 mx-1">·</span>
                                    <span v-if="a.chair !== '—'" class="text-gray-400">{{ a.chair }}</span>
                                </p>
                                <p v-if="a.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 mt-1 inline-block max-w-xs truncate">📝 {{ a.notes }}</p>
                            </div>
                            <span :class="['flex-shrink-0 inline-flex items-center px-3 py-1 rounded-full text-xs font-bold', statusBadge(a.status)]">{{ a.status_label }}</span>
                            <div class="hidden sm:flex flex-col items-end gap-0.5 flex-shrink-0">
                                <span class="font-mono text-xs text-gray-400">{{ a.code }}</span>
                                <span class="text-xs text-gray-400">{{ a.branch }}</span>
                            </div>
                        </Link>
                        <div v-if="can('appointments.manage')" class="flex items-center pr-3 flex-shrink-0">
                            <button @click.prevent="openReschedule(a)"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Rời lịch
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 py-2">
                    <button @click="currentPage = 1" :disabled="currentPage === 1" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">«</button>
                    <button @click="currentPage--" :disabled="currentPage === 1" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">‹</button>
                    <template v-for="p in pageNumbers" :key="p">
                        <span v-if="p === '...'" class="px-2 py-1.5 text-xs text-gray-400">…</span>
                        <button v-else @click="currentPage = p"
                            :class="['px-3 py-1.5 text-xs border rounded-lg',
                                p === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50']">{{ p }}</button>
                    </template>
                    <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">›</button>
                    <button @click="currentPage = totalPages" :disabled="currentPage === totalPages" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">»</button>
                </div>
            </template>

            <!-- ══════════════════════════════════════════════════════════════
                 CHẾ ĐỘ: LƯỚI
            ══════════════════════════════════════════════════════════════ -->
            <template v-else-if="viewMode === 'grid'">
                <div class="flex justify-end text-xs text-gray-400 px-1">
                    {{ paginatedAppointments.length }} / {{ filteredAppointments.length }} lịch hẹn
                </div>
                <div v-if="filteredAppointments.length === 0"
                    class="bg-white rounded-xl border border-gray-200 p-10 text-center text-gray-400">
                    Không có lịch hẹn nào
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div v-for="a in paginatedAppointments" :key="a.id"
                        :class="['rounded-xl border hover:shadow-md transition-all flex flex-col overflow-hidden', statusBorder(a.status)]">
                        <div :class="['h-1.5 flex-shrink-0', statusStripe(a.status)]"></div>
                        <Link :href="route('schedule.appointments.show', a.id)" class="flex-1 p-4 flex flex-col gap-3 min-w-0 bg-white">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">{{ displayDate(a.scheduled_at) }}</p>
                                    <p class="text-2xl font-bold text-gray-900 leading-tight">{{ timeOf(a.scheduled_at) }}</p>
                                    <p class="text-xs text-gray-400">→ {{ a.ends_at }} · {{ a.duration_minutes }}ph</p>
                                </div>
                                <span :class="['flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold mt-0.5', statusBadge(a.status)]">{{ a.status_label }}</span>
                            </div>
                            <div>
                                <p class="text-base font-bold text-gray-900 truncate">{{ a.patient }}</p>
                                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ a.patient_phone }}</p>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p v-if="a.doctor !== '—'" class="truncate flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ a.doctor }}
                                </p>
                                <p v-if="a.service !== '—'" class="truncate flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    {{ a.service }}
                                </p>
                                <p class="truncate flex items-center gap-1.5 text-gray-400 text-xs">
                                    <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    {{ a.branch }}
                                </p>
                            </div>
                            <p v-if="a.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 truncate">📝 {{ a.notes }}</p>
                        </Link>
                        <div class="flex items-center justify-between px-4 py-2 border-t border-gray-100 bg-white">
                            <span class="font-mono text-xs text-gray-400">{{ a.code }}</span>
                            <button v-if="can('appointments.manage')" @click="openReschedule(a)"
                                class="flex items-center gap-1 px-2 py-1 text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Rời lịch
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 py-2">
                    <button @click="currentPage = 1" :disabled="currentPage === 1" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">«</button>
                    <button @click="currentPage--" :disabled="currentPage === 1" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">‹</button>
                    <template v-for="p in pageNumbers" :key="p">
                        <span v-if="p === '...'" class="px-2 py-1.5 text-xs text-gray-400">…</span>
                        <button v-else @click="currentPage = p"
                            :class="['px-3 py-1.5 text-xs border rounded-lg',
                                p === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50']">{{ p }}</button>
                    </template>
                    <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">›</button>
                    <button @click="currentPage = totalPages" :disabled="currentPage === totalPages" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">»</button>
                </div>
            </template>

        </div>

        <!-- ═══ SLIDE-OVER: TẠO LỊCH HẸN ═══ -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex justify-end">
                <div class="absolute inset-0 bg-black/40" @click="showCreate = false"></div>
                <div class="relative bg-white w-full max-w-lg h-full flex flex-col shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                        <h3 class="text-base font-semibold text-gray-900">Đặt lịch hẹn mới</h3>
                        <button @click="showCreate = false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <div v-if="createForm.errors.conflict" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            ⚠ {{ createForm.errors.conflict }}
                        </div>
                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Khách hàng <span class="text-red-500">*</span></label>
                                <SearchableSelect v-model="createForm.patient_id" :options="patientOptions" placeholder="-- Tìm khách hàng --" @update:modelValue="onPatientChange" />
                                <p v-if="createForm.errors.patient_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.patient_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Chi nhánh <span class="text-red-500">*</span></label>
                                <select v-model="createForm.branch_id" @change="onBranchChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- Chọn chi nhánh --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <p v-if="createForm.errors.branch_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.branch_id }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Bác sĩ</label>
                                    <select v-model="createForm.doctor_id"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        <option :value="null">-- Chọn bác sĩ --</option>
                                        <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghế nha</label>
                                    <select v-model="createForm.dental_chair_id"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        <option :value="null">-- Chọn ghế --</option>
                                        <option v-for="c in filteredChairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Dịch vụ</label>
                                <select v-model="createForm.service_id" @change="onServiceChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn dịch vụ --</option>
                                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Ngày & giờ hẹn <span class="text-red-500">*</span></label>
                                    <input type="datetime-local" v-model="createForm.scheduled_at"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                    <p v-if="createForm.errors.scheduled_at" class="text-red-500 text-xs mt-1">{{ createForm.errors.scheduled_at }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng</label>
                                    <select v-model="createForm.duration_minutes"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        <option :value="15">15 phút</option>
                                        <option :value="20">20 phút</option>
                                        <option :value="30">30 phút</option>
                                        <option :value="45">45 phút</option>
                                        <option :value="60">60 phút</option>
                                        <option :value="90">90 phút</option>
                                        <option :value="120">2 giờ</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghi chú</label>
                                <textarea v-model="createForm.notes" rows="3" placeholder="Ghi chú thêm..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none" />
                            </div>
                        </form>
                    </div>
                    <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50">
                        <button @click="showCreate = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100">Hủy</button>
                        <button @click="submitCreate" :disabled="createForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5">
                            <svg v-if="createForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Đặt lịch
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ═══ MODAL: RỜI LỊCH NHANH ═══ -->
        <Teleport to="body">
            <div v-if="rescheduleTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="rescheduleTarget = null"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm z-10">
                    <div class="px-5 pt-5 pb-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Rời lịch hẹn</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ rescheduleTarget.patient }} <span class="font-mono text-xs text-gray-400 ml-1">{{ rescheduleTarget.code }}</span></p>
                    </div>
                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ngày & giờ mới</label>
                            <input type="datetime-local" v-model="rsForm.scheduled_at"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            <p v-if="rsErrors.scheduled_at" class="text-red-500 text-xs mt-1">{{ rsErrors.scheduled_at }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng</label>
                            <select v-model="rsForm.duration_minutes"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="15">15 phút</option><option :value="20">20 phút</option>
                                <option :value="30">30 phút</option><option :value="45">45 phút</option>
                                <option :value="60">60 phút</option><option :value="90">90 phút</option>
                                <option :value="120">2 giờ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghi chú</label>
                            <textarea v-model="rsForm.notes" rows="2" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none" />
                        </div>
                        <p v-if="rsErrors.conflict" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ rsErrors.conflict }}</p>
                    </div>
                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button @click="rescheduleTarget = null" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button @click="submitReschedule" :disabled="rsSaving"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5">
                            <svg v-if="rsSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Xác nhận
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({
    all_appointments: Array,
    branches: Array,
    doctors: Array,
    chairs: Array,
    services: Array,
    patients: Array,
    statuses: Array,
});

// ── Timeline constants ──────────────────────────────────────────
const DAY_START    = 7      // 07:00
const DAY_END      = 20     // 20:00
const HOUR_H       = 72     // px per hour
const DAY_TOTAL_H  = (DAY_END - DAY_START) * HOUR_H  // 936px

// ── View modes ─────────────────────────────────────────────────
const VIEW_MODES = [
    { key: 'day',   label: 'Ngày',    title: 'Xem theo ngày',   icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><path stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/></svg>' },
    { key: 'week',  label: 'Tuần',    title: 'Xem theo tuần',   icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-width="2" d="M3 6h18M3 10h18M3 14h18M3 18h18"/></svg>' },
    { key: 'month', label: 'Tháng',   title: 'Xem theo tháng',  icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><path stroke-width="2" d="M3 10h18M8 2v4M16 2v4M8 14h2v2H8zM13 14h2v2h-2z"/></svg>' },
    { key: 'list',  label: 'Danh sách', title: 'Danh sách',     icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>' },
    { key: 'grid',  label: 'Lưới',    title: 'Xem dạng lưới',   icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>' },
];

// ── State ──────────────────────────────────────────────────────
const viewMode     = ref(localStorage.getItem('apt_view_mode') || 'list');
const date         = ref(dayjs().format('YYYY-MM-DD'));
const search       = ref('');
const todayOnly    = ref(false);
const branchId     = ref('');
const doctorId     = ref('');
const filterStatus = ref('');
const perPage      = ref(20);
const currentPage  = ref(1);

const isCalendarView = computed(() => ['day', 'week', 'month'].includes(viewMode.value));

function setViewMode(v) {
    viewMode.value = v;
    localStorage.setItem('apt_view_mode', v);
}

// ── Base filtered list (branch/doctor/status/search) ───────────
const filteredAppointments = computed(() => {
    let list = [...props.all_appointments];
    if (branchId.value)     list = list.filter(a => String(a.branch_id) === String(branchId.value));
    if (doctorId.value)     list = list.filter(a => String(a.doctor_id) === String(doctorId.value));
    if (filterStatus.value) list = list.filter(a => a.status === filterStatus.value);
    if (todayOnly.value && viewMode.value === 'list')
        list = list.filter(a => a.scheduled_at.startsWith(date.value));
    if (search.value.trim()) {
        const q = search.value.toLowerCase().trim();
        list = list.filter(a =>
            (a.patient ?? '').toLowerCase().includes(q) ||
            (a.patient_phone ?? '').toLowerCase().includes(q) ||
            (a.doctor ?? '').toLowerCase().includes(q) ||
            (a.service ?? '').toLowerCase().includes(q) ||
            (a.code ?? '').toLowerCase().includes(q) ||
            (a.notes ?? '').toLowerCase().includes(q) ||
            a.scheduled_at.includes(q)
        );
    }
    return list;
});

// Calendar views only apply to visible date range
const calendarFiltered = computed(() => {
    if (viewMode.value === 'day') {
        return filteredAppointments.value.filter(a => a.scheduled_at.startsWith(date.value));
    }
    if (viewMode.value === 'week') {
        const mon = getMonday(date.value);
        const sun = mon.add(6, 'day');
        return filteredAppointments.value.filter(a => {
            const d = a.scheduled_at.split(' ')[0];
            return d >= mon.format('YYYY-MM-DD') && d <= sun.format('YYYY-MM-DD');
        });
    }
    if (viewMode.value === 'month') {
        const ym = date.value.substring(0, 7);
        return filteredAppointments.value.filter(a => a.scheduled_at.startsWith(ym));
    }
    return filteredAppointments.value;
});

const calendarTotal = computed(() => {
    if (isCalendarView.value) return calendarFiltered.value.length;
    return filteredAppointments.value.length;
});

// ── Pagination (list/grid) ──────────────────────────────────────
const totalPages = computed(() => {
    if (perPage.value === 'all') return 1;
    return Math.max(1, Math.ceil(filteredAppointments.value.length / Number(perPage.value)));
});
const paginatedAppointments = computed(() => {
    if (perPage.value === 'all') return filteredAppointments.value;
    const pp = Number(perPage.value);
    return filteredAppointments.value.slice((currentPage.value - 1) * pp, currentPage.value * pp);
});
const pageNumbers = computed(() => {
    const total = totalPages.value, cur = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    if (cur > 3) pages.push('...');
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
    if (cur < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});
const hasActiveFilters = computed(() => !!search.value || !!branchId.value || !!doctorId.value || !!filterStatus.value);

watch([search, branchId, doctorId, filterStatus, perPage], () => { currentPage.value = 1; });

// ── Vietnamese locale arrays ────────────────────────────────────
const VI_DAYS   = ['Chủ nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
const VI_MONTHS = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                   'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];

// ── Navigation label & page title ──────────────────────────────
const navLabel = computed(() => {
    if (viewMode.value === 'day') {
        const d = dayjs(date.value);
        return `${VI_DAYS[d.day()]}, ${d.format('DD/MM/YYYY')}`;
    }
    if (viewMode.value === 'week') {
        const mon = getMonday(date.value);
        const sun = mon.add(6, 'day');
        return `${mon.format('DD/MM')} – ${sun.format('DD/MM/YYYY')}`;
    }
    if (viewMode.value === 'month') {
        const d = dayjs(date.value);
        return `${VI_MONTHS[d.month()]} ${d.year()}`;
    }
    return '';
});
const pageTitle = computed(() => {
    if (isCalendarView.value) return navLabel.value;
    return 'Tất cả lịch hẹn';
});

// ── Navigation ─────────────────────────────────────────────────
function navPrev() {
    if (viewMode.value === 'day')   date.value = dayjs(date.value).subtract(1, 'day').format('YYYY-MM-DD');
    if (viewMode.value === 'week')  date.value = dayjs(date.value).subtract(7, 'day').format('YYYY-MM-DD');
    if (viewMode.value === 'month') date.value = dayjs(date.value).subtract(1, 'month').format('YYYY-MM-DD');
}
function navNext() {
    if (viewMode.value === 'day')   date.value = dayjs(date.value).add(1, 'day').format('YYYY-MM-DD');
    if (viewMode.value === 'week')  date.value = dayjs(date.value).add(7, 'day').format('YYYY-MM-DD');
    if (viewMode.value === 'month') date.value = dayjs(date.value).add(1, 'month').format('YYYY-MM-DD');
}
function goToday() { date.value = dayjs().format('YYYY-MM-DD'); }
function jumpToDay(d) { date.value = d; viewMode.value = 'day'; localStorage.setItem('apt_view_mode', 'day'); }

// ── TODAY helpers ───────────────────────────────────────────────
const TODAY = dayjs().format('YYYY-MM-DD');
const todayIsSelected = computed(() => date.value === TODAY);
const todayIsInWeek = computed(() => {
    const mon = getMonday(date.value);
    const sun = mon.add(6, 'day');
    return TODAY >= mon.format('YYYY-MM-DD') && TODAY <= sun.format('YYYY-MM-DD');
});

// ── Now indicator ──────────────────────────────────────────────
const nowLabel = computed(() => dayjs().format('HH:mm'));
const nowTop = computed(() => {
    const h = dayjs().hour(), m = dayjs().minute();
    return Math.max(0, ((h - DAY_START) + m / 60) * HOUR_H);
});

// ── Hour grid ──────────────────────────────────────────────────
const dayHours = computed(() => {
    const result = [];
    for (let h = DAY_START; h <= DAY_END; h++) {
        result.push({
            val: h,
            label: `${String(h).padStart(2, '0')}:00`,
            top: (h - DAY_START) * HOUR_H,
        });
        if (h < DAY_END) {
            result.push({ val: h + 0.5, label: '', top: (h - DAY_START) * HOUR_H + HOUR_H / 2 });
        }
    }
    return result;
});

// ── DAY VIEW layout ────────────────────────────────────────────
function aptMinutes(scheduledAt) {
    const t = scheduledAt.split(' ')[1] || '00:00';
    const [h, m] = t.split(':').map(Number);
    return (h - DAY_START) * 60 + m;
}

function layoutAppointments(apts) {
    const sorted = [...apts].sort((a, b) => a.scheduled_at.localeCompare(b.scheduled_at));
    const columns = [];
    const result = sorted.map(apt => {
        const start = aptMinutes(apt.scheduled_at);
        const end = start + apt.duration_minutes;
        let col = 0;
        while (col < columns.length && columns[col] > start) col++;
        columns[col] = end;
        return { ...apt, col, endMin: end };
    });
    const totalCols = columns.length || 1;
    return result.map(item => ({
        ...item,
        top:    Math.max(0, aptMinutes(item.scheduled_at) / 60 * HOUR_H),
        height: Math.max(32, item.duration_minutes / 60 * HOUR_H),
        left:   (item.col / totalCols * 100) + '%',
        width:  (1 / totalCols * 100) + '%',
        totalCols,
    }));
}

const dayLayoutItems = computed(() => {
    const dayApts = filteredAppointments.value.filter(a => a.scheduled_at.startsWith(date.value));
    return layoutAppointments(dayApts);
});

const daySummary = computed(() => {
    const counts = {};
    dayLayoutItems.value.forEach(a => { counts[a.status] = (counts[a.status] || 0) + 1; });
    return Object.entries(counts).map(([status, count]) => ({
        label: props.statuses?.find(s => s.value === status)?.label ?? status,
        count,
        badge: statusBadge(status),
    }));
});

// ── WEEK VIEW ──────────────────────────────────────────────────
const DAY_NAMES = ['T.Hai', 'T.Ba', 'T.Tư', 'T.Năm', 'T.Sáu', 'T.Bảy', 'CN'];

function getMonday(d) {
    const dj = dayjs(d);
    const dow = dj.day(); // 0=Sun
    const diff = dow === 0 ? -6 : 1 - dow;
    return dj.add(diff, 'day');
}

const weekDays = computed(() => {
    const mon = getMonday(date.value);
    return Array.from({ length: 7 }, (_, i) => {
        const day = mon.add(i, 'day');
        const dateStr = day.format('YYYY-MM-DD');
        const apts = filteredAppointments.value
            .filter(a => a.scheduled_at.startsWith(dateStr))
            .sort((a, b) => a.scheduled_at.localeCompare(b.scheduled_at));
        return {
            date: dateStr,
            dayName: DAY_NAMES[i],
            dayNum: day.format('D'),
            monthLabel: day.format('MM/YYYY'),
            isToday: dateStr === TODAY,
            appointments: apts,
            layout: layoutAppointments(apts),
        };
    });
});

// ── MONTH VIEW ─────────────────────────────────────────────────
const monthCalendar = computed(() => {
    const firstDay = dayjs(date.value).startOf('month');
    const lastDay  = dayjs(date.value).endOf('month');
    const curMonth = dayjs(date.value).format('YYYY-MM');

    // Start from Monday of the week containing the 1st
    const startDow = firstDay.day(); // 0=Sun
    const startDate = firstDay.subtract(startDow === 0 ? 6 : startDow - 1, 'day');

    // End on Sunday of the week containing the last day
    const endDow = lastDay.day(); // 0=Sun
    const endDate = lastDay.add(endDow === 0 ? 0 : 7 - endDow, 'day');

    const cells = [];
    let cur = startDate;
    while (!cur.isAfter(endDate)) {
        const dateStr = cur.format('YYYY-MM-DD');
        cells.push({
            date: dateStr,
            dayNum: cur.format('D'),
            currentMonth: cur.format('YYYY-MM') === curMonth,
            isToday: dateStr === TODAY,
            appointments: filteredAppointments.value
                .filter(a => a.scheduled_at.startsWith(dateStr))
                .sort((a, b) => a.scheduled_at.localeCompare(b.scheduled_at)),
        });
        cur = cur.add(1, 'day');
    }
    return cells;
});

// ── Status styles ──────────────────────────────────────────────
const STATUS_STYLES = {
    pending:       { stripe: 'bg-gray-400',    border: 'border-gray-200',   badge: 'bg-gray-500 text-white',    card: 'border-l-gray-400 bg-gray-50 text-gray-700' },
    booked:        { stripe: 'bg-blue-500',    border: 'border-blue-200',   badge: 'bg-blue-500 text-white',    card: 'border-l-blue-500 bg-blue-50 text-blue-800' },
    confirmed:     { stripe: 'bg-indigo-600',  border: 'border-indigo-200', badge: 'bg-indigo-600 text-white',  card: 'border-l-indigo-600 bg-indigo-50 text-indigo-800' },
    rescheduled:   { stripe: 'bg-yellow-400',  border: 'border-yellow-200', badge: 'bg-yellow-500 text-white',  card: 'border-l-yellow-400 bg-yellow-50 text-yellow-800' },
    arrived_early: { stripe: 'bg-teal-500',    border: 'border-teal-200',   badge: 'bg-teal-500 text-white',    card: 'border-l-teal-500 bg-teal-50 text-teal-800' },
    checked_in:    { stripe: 'bg-teal-600',    border: 'border-teal-300',   badge: 'bg-teal-600 text-white',    card: 'border-l-teal-600 bg-teal-50 text-teal-800' },
    arrived_late:  { stripe: 'bg-orange-500',  border: 'border-orange-200', badge: 'bg-orange-500 text-white',  card: 'border-l-orange-500 bg-orange-50 text-orange-800' },
    no_show:       { stripe: 'bg-red-400',     border: 'border-red-200',    badge: 'bg-red-400 text-white',     card: 'border-l-red-400 bg-red-50 text-red-700' },
    cancelled:     { stripe: 'bg-gray-300',    border: 'border-gray-200',   badge: 'bg-gray-400 text-white',    card: 'border-l-gray-300 bg-gray-50 text-gray-400' },
    in_treatment:  { stripe: 'bg-purple-500',  border: 'border-purple-200', badge: 'bg-purple-500 text-white',  card: 'border-l-purple-500 bg-purple-50 text-purple-800' },
    completed:     { stripe: 'bg-emerald-500', border: 'border-emerald-200',badge: 'bg-emerald-500 text-white', card: 'border-l-emerald-500 bg-emerald-50 text-emerald-800' },
};
const DS = { stripe: 'bg-gray-300', border: 'border-gray-200', badge: 'bg-gray-400 text-white', card: 'border-l-gray-300 bg-gray-50 text-gray-600' };
function statusStripe(s) { return (STATUS_STYLES[s] ?? DS).stripe; }
function statusBorder(s) { return (STATUS_STYLES[s] ?? DS).border; }
function statusBadge(s)  { return (STATUS_STYLES[s] ?? DS).badge; }
function statusCard(s)   { return (STATUS_STYLES[s] ?? DS).card; }

// ── Helpers ────────────────────────────────────────────────────
function timeOf(scheduledAt) { return (scheduledAt.split(' ')[1] ?? '').substring(0, 5); }
function displayDate(scheduledAt) { return dayjs(scheduledAt.split(' ')[0]).format('DD/MM'); }
function toggleTodayOnly() { todayOnly.value = !todayOnly.value; }
function changeDate(d) { date.value = dayjs(date.value).add(d, 'day').format('YYYY-MM-DD'); }
function clearFilters() { search.value = ''; branchId.value = ''; doctorId.value = ''; filterStatus.value = ''; }

// ── Create form ────────────────────────────────────────────────
const showCreate = ref(false);
const createForm = useForm({
    patient_id: '', branch_id: '', doctor_id: null, dental_chair_id: null,
    service_id: null, scheduled_at: dayjs().format('YYYY-MM-DD') + 'T08:00',
    duration_minutes: 30, notes: '',
});
const patientOptions  = computed(() => props.patients.map(p => ({ value: p.id, label: `${p.code} — ${p.full_name}`, sublabel: p.phone })));
const filteredDoctors = computed(() => props.doctors.filter(d => !createForm.branch_id || d.branch_id == createForm.branch_id));
const filteredChairs  = computed(() => props.chairs.filter(c => !createForm.branch_id || c.branch_id == createForm.branch_id));

function openCreate() {
    createForm.reset();
    createForm.scheduled_at = date.value + 'T08:00';
    showCreate.value = true;
}
function onPatientChange(pid) {
    const p = props.patients.find(x => x.id === pid);
    if (p?.branch_id && !createForm.branch_id) createForm.branch_id = p.branch_id;
}
function onBranchChange() { createForm.doctor_id = null; createForm.dental_chair_id = null; }
function onServiceChange() {
    const svc = props.services.find(s => s.id === createForm.service_id);
    if (svc) createForm.duration_minutes = svc.duration_minutes;
}
function submitCreate() {
    createForm.post(route('schedule.appointments.store'), {
        onSuccess: () => { showCreate.value = false; },
    });
}

// ── Report dropdown ─────────────────────────────────────────────
const reportOpen    = ref(false);
const reportDropdownRef = ref(null);
const reportFrom    = ref(dayjs().format('YYYY-MM-DD'));
const reportTo      = ref(dayjs().format('YYYY-MM-DD'));
const reportPanelStyle = ref({});

const reportPresets = computed(() => {
    const today = dayjs();
    const monThisWeek = today.day() === 0 ? today.subtract(6,'day') : today.startOf('week').add(1,'day');
    return [
        { label: 'Hôm nay',       highlight: true,  from: today.format('YYYY-MM-DD'), to: today.format('YYYY-MM-DD') },
        { label: 'Hôm qua',       highlight: false, from: today.subtract(1,'day').format('YYYY-MM-DD'), to: today.subtract(1,'day').format('YYYY-MM-DD') },
        { label: 'Tuần này',      highlight: true,  from: monThisWeek.format('YYYY-MM-DD'), to: monThisWeek.add(6,'day').format('YYYY-MM-DD') },
        { label: 'Tuần trước',    highlight: false, from: monThisWeek.subtract(7,'day').format('YYYY-MM-DD'), to: monThisWeek.subtract(1,'day').format('YYYY-MM-DD') },
        { label: 'Tháng này',     highlight: true,  from: today.startOf('month').format('YYYY-MM-DD'), to: today.endOf('month').format('YYYY-MM-DD') },
        { label: 'Tháng trước',   highlight: false, from: today.subtract(1,'month').startOf('month').format('YYYY-MM-DD'), to: today.subtract(1,'month').endOf('month').format('YYYY-MM-DD') },
    ];
});

function openReport(from, to) {
    reportOpen.value = false;
    router.visit(route('reports.daily-schedule', { from, to }));
}

watch(reportOpen, (val) => {
    if (!val) return;
    // Position panel below the button
    const btn = reportDropdownRef.value?.querySelector('button');
    if (!btn) return;
    const rect = btn.getBoundingClientRect();
    const panelW = 320;
    let left = rect.right - panelW;
    if (left < 8) left = 8;
    reportPanelStyle.value = {
        top:  rect.bottom + 8 + 'px',
        left: left + 'px',
    };
});

function onClickOutsideReport(e) {
    if (!reportDropdownRef.value?.contains(e.target)) reportOpen.value = false;
}
onMounted(()      => document.addEventListener('mousedown', onClickOutsideReport));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutsideReport));

// ── Reschedule ─────────────────────────────────────────────────
const rescheduleTarget = ref(null);
const rsForm   = ref({ scheduled_at: '', duration_minutes: 30, notes: '' });
const rsErrors = ref({});
const rsSaving = ref(false);

function openReschedule(a) {
    rescheduleTarget.value = a;
    rsForm.value = { scheduled_at: a.scheduled_at.replace(' ', 'T'), duration_minutes: a.duration_minutes, notes: a.notes ?? '' };
    rsErrors.value = {};
}
function submitReschedule() {
    if (!rescheduleTarget.value) return;
    rsSaving.value = true; rsErrors.value = {};
    router.patch(
        route('schedule.appointments.quick-reschedule', rescheduleTarget.value.id),
        { scheduled_at: rsForm.value.scheduled_at.replace('T', ' ') + ':00', duration_minutes: rsForm.value.duration_minutes, notes: rsForm.value.notes },
        { preserveScroll: true, onSuccess: () => { rescheduleTarget.value = null; }, onError: e => { rsErrors.value = e; }, onFinish: () => { rsSaving.value = false; } }
    );
}
</script>
