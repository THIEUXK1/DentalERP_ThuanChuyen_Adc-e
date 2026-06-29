<template>
    <AppLayout title="Lịch hẹn">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    {{ todayOnly ? `Lịch hẹn ngày ${formattedDate}` : 'Tất cả lịch hẹn' }}
                    <span class="ml-2 text-sm font-normal text-gray-400">({{ filteredAppointments.length }})</span>
                </h2>
                <div class="flex items-center gap-2">
                    <!-- View toggle -->
                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button @click="viewMode = 'list'" :class="['p-2 transition-colors', viewMode === 'list' ? 'bg-primary-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600']" title="Dạng danh sách">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </button>
                        <button @click="viewMode = 'grid'" :class="['p-2 transition-colors', viewMode === 'grid' ? 'bg-primary-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600']" title="Dạng hộp">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                        </button>
                    </div>
                    <button v-if="can('appointments.create')" @click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                        + Đặt lịch hẹn
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center gap-3">

                <!-- Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Tìm tên, SĐT, mã, dịch vụ, bác sĩ, ghi chú..."
                        class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    <button v-if="search" @click="search = ''"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Toggle Hôm nay -->
                <label class="flex items-center gap-2 cursor-pointer select-none flex-shrink-0">
                    <div @click="toggleTodayOnly"
                        :class="['w-10 h-5 rounded-full relative transition-colors flex-shrink-0 cursor-pointer',
                            todayOnly ? 'bg-primary-600' : 'bg-gray-300']">
                        <div :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform',
                            todayOnly ? 'translate-x-5' : 'translate-x-0.5']"></div>
                    </div>
                    <span class="text-sm text-gray-700 font-medium">Hôm nay</span>
                </label>

                <!-- Date navigation (chỉ hiện khi bật Hôm nay) -->
                <template v-if="todayOnly">
                    <div class="w-px h-5 bg-gray-200 flex-shrink-0"></div>
                    <button @click="changeDate(-1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <input type="date" v-model="date"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none flex-shrink-0" />
                    <button @click="changeDate(1)" class="p-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button @click="goToday" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 flex-shrink-0">Hôm nay</button>
                </template>

                <select v-model="branchId"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="doctorId"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả bác sĩ</option>
                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <select v-model="filterStatus"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>

                <!-- Per page -->
                <select v-model="perPage"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option :value="10">10/trang</option>
                    <option :value="20">20/trang</option>
                    <option :value="50">50/trang</option>
                    <option :value="100">100/trang</option>
                    <option value="all">Tất cả</option>
                </select>

                <!-- Clear filters -->
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 flex-shrink-0 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa lọc
                </button>
            </div>

            <!-- Results info -->
            <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                <span>
                    Hiển thị <strong class="text-gray-700">{{ paginatedAppointments.length }}</strong>
                    / {{ filteredAppointments.length }} lịch hẹn
                    <span v-if="filteredAppointments.length < all_appointments.length" class="text-gray-400">
                        (tổng {{ all_appointments.length }})
                    </span>
                </span>
                <span v-if="totalPages > 1">Trang {{ currentPage }}/{{ totalPages }}</span>
            </div>

            <!-- Empty state -->
            <div v-if="filteredAppointments.length === 0"
                class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                {{ hasActiveFilters ? 'Không tìm thấy lịch hẹn phù hợp' : 'Chưa có lịch hẹn nào' }}
            </div>

            <!-- ── LIST VIEW ── -->
            <div v-else-if="viewMode === 'list'" class="space-y-2">
                <div v-for="a in paginatedAppointments" :key="a.id"
                    class="flex items-center gap-4 bg-white rounded-xl border border-gray-200 hover:border-primary-200 hover:shadow-sm transition-all">
                    <Link :href="route('schedule.appointments.show', a.id)" class="flex flex-1 items-center gap-4 p-4 min-w-0">
                        <div class="w-20 text-center flex-shrink-0">
                            <p class="text-xs text-gray-400 font-medium">{{ displayDate(a.scheduled_at) }}</p>
                            <p class="text-sm font-bold text-gray-800">{{ a.scheduled_at.split(' ')[1] }}</p>
                            <p class="text-xs text-gray-400">→ {{ a.ends_at }}</p>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 truncate">{{ a.patient }}</p>
                                <span class="text-xs text-gray-400">{{ a.patient_phone }}</span>
                            </div>
                            <p class="text-sm text-gray-500 truncate">{{ a.service }} · {{ a.doctor }} · {{ a.chair }}</p>
                            <p class="text-xs text-gray-400">{{ a.branch }}</p>
                            <p v-if="a.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 mt-1 inline-block max-w-xs truncate">
                                📝 {{ a.notes }}
                            </p>
                        </div>
                        <StatusBadge :color="a.status_color">{{ a.status_label }}</StatusBadge>
                        <span class="font-mono text-xs text-gray-400 flex-shrink-0 hidden sm:block">{{ a.code }}</span>
                    </Link>
                    <div v-if="can('appointments.manage')" class="pr-3 flex-shrink-0">
                        <button @click="openReschedule(a)" title="Rời lịch nhanh"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 rounded-lg hover:bg-indigo-100 hover:border-indigo-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Rời lịch
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── GRID VIEW ── -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <div v-for="a in paginatedAppointments" :key="a.id"
                    class="bg-white rounded-xl border border-gray-200 hover:border-primary-200 hover:shadow-md transition-all flex flex-col">
                    <Link :href="route('schedule.appointments.show', a.id)" class="flex-1 p-4 flex flex-col gap-3 min-w-0">
                        <!-- Header: date/time + status -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs text-gray-400">{{ displayDate(a.scheduled_at) }}</p>
                                <p class="text-base font-bold text-gray-800 leading-tight">{{ a.scheduled_at.split(' ')[1] }}</p>
                                <p class="text-xs text-gray-400">→ {{ a.ends_at }}</p>
                            </div>
                            <StatusBadge :color="a.status_color" class="flex-shrink-0 mt-0.5">{{ a.status_label }}</StatusBadge>
                        </div>
                        <!-- Patient -->
                        <div>
                            <p class="font-semibold text-gray-900 truncate">{{ a.patient }}</p>
                            <p class="text-xs text-gray-400">{{ a.patient_phone }}</p>
                        </div>
                        <!-- Details -->
                        <div class="text-xs text-gray-500 space-y-1">
                            <p v-if="a.doctor !== '—'" class="truncate flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ a.doctor }}
                            </p>
                            <p v-if="a.service !== '—'" class="truncate flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                {{ a.service }}
                            </p>
                            <p class="truncate flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                {{ a.branch }}
                            </p>
                        </div>
                        <p v-if="a.notes" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 truncate">
                            📝 {{ a.notes }}
                        </p>
                    </Link>
                    <!-- Footer -->
                    <div class="flex items-center justify-between px-4 py-2 border-t border-gray-100">
                        <span class="font-mono text-xs text-gray-400">{{ a.code }}</span>
                        <button v-if="can('appointments.manage')" @click="openReschedule(a)" title="Rời lịch nhanh"
                            class="flex items-center gap-1 px-2 py-1 text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Rời lịch
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-center gap-1 py-2">
                <button @click="currentPage = 1" :disabled="currentPage === 1"
                    class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">«</button>
                <button @click="currentPage--" :disabled="currentPage === 1"
                    class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">‹</button>
                <template v-for="p in pageNumbers" :key="p">
                    <span v-if="p === '...'" class="px-2 py-1.5 text-xs text-gray-400">…</span>
                    <button v-else @click="currentPage = p"
                        :class="['px-3 py-1.5 text-xs border rounded-lg',
                            p === currentPage
                                ? 'bg-primary-600 text-white border-primary-600'
                                : 'border-gray-300 hover:bg-gray-50']">
                        {{ p }}
                    </button>
                </template>
                <button @click="currentPage++" :disabled="currentPage === totalPages"
                    class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">›</button>
                <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                    class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">»</button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             SLIDE-OVER: TẠO LỊCH HẸN MỚI
        ═══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex justify-end">
                <div class="absolute inset-0 bg-black/40" @click="showCreate = false"></div>
                <div class="relative bg-white w-full max-w-lg h-full flex flex-col shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                        <h3 class="text-base font-semibold text-gray-900">Đặt lịch hẹn mới</h3>
                        <button @click="showCreate = false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <div v-if="createForm.errors.conflict" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            ⚠ {{ createForm.errors.conflict }}
                        </div>
                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                    Khách hàng <span class="text-red-500">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="createForm.patient_id"
                                    :options="patientOptions"
                                    placeholder="-- Tìm khách hàng --"
                                    @update:modelValue="onPatientChange"
                                />
                                <p v-if="createForm.errors.patient_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.patient_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                    Chi nhánh <span class="text-red-500">*</span>
                                </label>
                                <select v-model="createForm.branch_id" @change="onBranchChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                    <option value="">-- Chọn chi nhánh --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <p v-if="createForm.errors.branch_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.branch_id }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Bác sĩ</label>
                                    <select v-model="createForm.doctor_id"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                        <option :value="null">-- Chọn bác sĩ --</option>
                                        <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghế nha</label>
                                    <select v-model="createForm.dental_chair_id"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                        <option :value="null">-- Chọn ghế --</option>
                                        <option v-for="c in filteredChairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Dịch vụ</label>
                                <select v-model="createForm.service_id" @change="onServiceChange"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
                                    <option :value="null">-- Chọn dịch vụ --</option>
                                    <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                        Ngày & giờ hẹn <span class="text-red-500">*</span>
                                    </label>
                                    <input type="datetime-local" v-model="createForm.scheduled_at"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                                    <p v-if="createForm.errors.scheduled_at" class="text-red-500 text-xs mt-1">{{ createForm.errors.scheduled_at }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng (phút)</label>
                                    <select v-model="createForm.duration_minutes"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
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
                                <textarea v-model="createForm.notes" rows="3" placeholder="Ghi chú thêm về lịch hẹn..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none resize-none" />
                            </div>
                        </form>
                    </div>
                    <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50">
                        <button @click="showCreate = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100">Hủy</button>
                        <button @click="submitCreate" :disabled="createForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-1.5">
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

        <!-- ═══════════════════════════════════════════════════════════
             MODAL: RỜI LỊCH NHANH
        ═══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="rescheduleTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="rescheduleTarget = null"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm z-10">
                    <div class="px-5 pt-5 pb-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Rời lịch hẹn</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ rescheduleTarget.patient }}
                            <span class="font-mono text-xs text-gray-400 ml-1">{{ rescheduleTarget.code }}</span>
                        </p>
                    </div>
                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ngày & giờ mới</label>
                            <input type="datetime-local" v-model="rsForm.scheduled_at"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            <p v-if="rsErrors.scheduled_at" class="text-red-500 text-xs mt-1">{{ rsErrors.scheduled_at }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Thời lượng (phút)</label>
                            <select v-model="rsForm.duration_minutes"
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option :value="15">15 phút</option>
                                <option :value="20">20 phút</option>
                                <option :value="30">30 phút</option>
                                <option :value="45">45 phút</option>
                                <option :value="60">60 phút</option>
                                <option :value="90">90 phút</option>
                                <option :value="120">2 giờ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Ghi chú</label>
                            <textarea v-model="rsForm.notes" rows="3" placeholder="Để trống nếu không muốn thay đổi..."
                                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none" />
                        </div>
                        <p v-if="rsErrors.conflict" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">
                            {{ rsErrors.conflict }}
                        </p>
                    </div>
                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button @click="rescheduleTarget = null"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button @click="submitReschedule" :disabled="rsSaving"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5">
                            <svg v-if="rsSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Xác nhận rời lịch
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
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

// ── Filter state ──────────────────────────────────────────────
const search       = ref('');
const todayOnly    = ref(localStorage.getItem('apt_today_only') === '1');
const date         = ref(dayjs().format('YYYY-MM-DD'));
const branchId     = ref('');
const doctorId     = ref('');
const filterStatus = ref('');
const perPage      = ref(20);
const currentPage  = ref(1);
const viewMode     = ref(localStorage.getItem('apt_view_mode') || 'list');

watch(viewMode, (val) => localStorage.setItem('apt_view_mode', val));

// ── Computed ──────────────────────────────────────────────────
const formattedDate = computed(() => dayjs(date.value).format('DD/MM/YYYY'));

const filteredAppointments = computed(() => {
    let list = props.all_appointments;

    if (todayOnly.value) {
        list = list.filter(a => a.scheduled_at.startsWith(date.value));
    }
    if (branchId.value) {
        list = list.filter(a => String(a.branch_id) === String(branchId.value));
    }
    if (doctorId.value) {
        list = list.filter(a => String(a.doctor_id) === String(doctorId.value));
    }
    if (filterStatus.value) {
        list = list.filter(a => a.status === filterStatus.value);
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase().trim();
        list = list.filter(a =>
            (a.patient        ?? '').toLowerCase().includes(q) ||
            (a.patient_phone  ?? '').toLowerCase().includes(q) ||
            (a.doctor         ?? '').toLowerCase().includes(q) ||
            (a.service        ?? '').toLowerCase().includes(q) ||
            (a.chair          ?? '').toLowerCase().includes(q) ||
            (a.branch         ?? '').toLowerCase().includes(q) ||
            (a.code           ?? '').toLowerCase().includes(q) ||
            (a.notes          ?? '').toLowerCase().includes(q) ||
            a.scheduled_at.includes(q)
        );
    }
    return list;
});

const totalPages = computed(() => {
    if (perPage.value === 'all') return 1;
    return Math.max(1, Math.ceil(filteredAppointments.value.length / Number(perPage.value)));
});

const paginatedAppointments = computed(() => {
    if (perPage.value === 'all') return filteredAppointments.value;
    const pp = Number(perPage.value);
    const start = (currentPage.value - 1) * pp;
    return filteredAppointments.value.slice(start, start + pp);
});

const pageNumbers = computed(() => {
    const total = totalPages.value;
    const cur = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    if (cur > 3) pages.push('...');
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
    if (cur < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

const hasActiveFilters = computed(() =>
    !!search.value || !!branchId.value || !!doctorId.value || !!filterStatus.value
);

// ── Watchers ──────────────────────────────────────────────────
watch([search, branchId, doctorId, filterStatus, todayOnly, date, perPage], () => {
    currentPage.value = 1;
});

watch(todayOnly, (val) => {
    localStorage.setItem('apt_today_only', val ? '1' : '0');
});

// ── Actions ───────────────────────────────────────────────────
function toggleTodayOnly() { todayOnly.value = !todayOnly.value; }

function changeDate(delta) {
    date.value = dayjs(date.value).add(delta, 'day').format('YYYY-MM-DD');
}

function goToday() {
    date.value = dayjs().format('YYYY-MM-DD');
}

function clearFilters() {
    search.value = '';
    branchId.value = '';
    doctorId.value = '';
    filterStatus.value = '';
    currentPage.value = 1;
}

function displayDate(scheduledAt) {
    return dayjs(scheduledAt.split(' ')[0]).format('DD/MM');
}

// ── Create slide-over ─────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    patient_id:       '',
    branch_id:        '',
    doctor_id:        null,
    dental_chair_id:  null,
    service_id:       null,
    scheduled_at:     dayjs().format('YYYY-MM-DD') + 'T08:00',
    duration_minutes: 30,
    notes:            '',
});

const patientOptions  = computed(() => props.patients.map(p => ({ value: p.id, label: `${p.code} — ${p.full_name}`, sublabel: p.phone })));
const filteredDoctors = computed(() => props.doctors.filter(d => !createForm.branch_id || d.branch_id == createForm.branch_id));
const filteredChairs  = computed(() => props.chairs.filter(c => !createForm.branch_id || c.branch_id == createForm.branch_id));

function openCreate() {
    createForm.reset();
    createForm.scheduled_at = dayjs(date.value).format('YYYY-MM-DD') + 'T08:00';
    showCreate.value = true;
}

function onPatientChange(patientId) {
    const p = props.patients.find(x => x.id === patientId);
    if (p?.branch_id && !createForm.branch_id) {
        createForm.branch_id = p.branch_id;
    }
}

function onBranchChange() {
    createForm.doctor_id = null;
    createForm.dental_chair_id = null;
}

function onServiceChange() {
    const svc = props.services.find(s => s.id === createForm.service_id);
    if (svc) createForm.duration_minutes = svc.duration_minutes;
}

function submitCreate() {
    createForm.post(route('schedule.appointments.store'), {
        onSuccess: () => { showCreate.value = false; },
    });
}

// ── Quick reschedule ──────────────────────────────────────────
const rescheduleTarget = ref(null);
const rsForm   = ref({ scheduled_at: '', duration_minutes: 30, notes: '' });
const rsErrors = ref({});
const rsSaving = ref(false);

function openReschedule(a) {
    rescheduleTarget.value = a;
    rsForm.value.scheduled_at     = a.scheduled_at.replace(' ', 'T');
    rsForm.value.duration_minutes = a.duration_minutes;
    rsForm.value.notes            = a.notes ?? '';
    rsErrors.value = {};
}

function submitReschedule() {
    if (!rescheduleTarget.value) return;
    rsSaving.value = true;
    rsErrors.value = {};
    router.patch(
        route('schedule.appointments.quick-reschedule', rescheduleTarget.value.id),
        {
            scheduled_at:     rsForm.value.scheduled_at.replace('T', ' ') + ':00',
            duration_minutes: rsForm.value.duration_minutes,
            notes:            rsForm.value.notes,
        },
        {
            preserveScroll: true,
            onSuccess: () => { rescheduleTarget.value = null; },
            onError:   (errors) => { rsErrors.value = errors; },
            onFinish:  () => { rsSaving.value = false; },
        }
    );
}
</script>
