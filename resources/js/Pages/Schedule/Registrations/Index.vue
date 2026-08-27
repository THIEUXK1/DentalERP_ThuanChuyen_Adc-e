<template>
    <AppLayout title="Đăng ký khám" full-height>
        <div class="flex flex-col flex-1 min-h-0 gap-3">

            <!-- Header — không lặp lại tên màn hình (đã có ở TopBar + TabBar),
                 thay bằng ngày đang xem để người dùng luôn biết mình đứng ở đâu -->
            <div class="flex items-center justify-between gap-3 flex-wrap flex-shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <!-- Date navigation -->
                    <div class="flex items-center bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <button @click="prevDay" title="Ngày trước"
                            class="px-2 py-2 text-gray-400 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <input type="date" v-model="selectedDate"
                            class="border-0 text-sm text-gray-800 font-semibold px-1 py-2 focus:outline-none focus:ring-0 bg-transparent cursor-pointer" />
                        <button @click="nextDay" title="Ngày sau"
                            class="px-2 py-2 text-gray-400 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <span v-if="isToday"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        {{ weekdayLabel }} · hôm nay
                    </span>
                    <button v-else @click="goToday"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                        {{ weekdayLabel }} · về hôm nay
                    </button>
                </div>

                <button @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 shadow-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Đăng ký mới
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 flex-shrink-0">
                <div v-for="card in statCards" :key="card.label"
                    :class="['bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 border-l-4', card.accent]">
                    <div class="flex items-baseline gap-2">
                        <span :class="['text-2xl font-bold leading-none', card.text]">{{ card.value }}</span>
                        <span v-if="card.percent !== null" class="text-xs text-gray-400">{{ card.percent }}%</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ card.label }}</div>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="bg-white rounded-xl border border-gray-200 px-3 py-2.5 space-y-2.5 shadow-sm flex-shrink-0">
                <!-- Thanh luôn hiển thị: tìm kiếm + nút thu gọn bộ lọc -->
                <div class="flex flex-wrap items-center gap-2">
                    <input v-model="search" type="text" placeholder="Tìm bệnh nhân, SĐT..."
                        class="flex-1 min-w-40 rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                    <button @click="showFilters = !showFilters"
                        :class="['inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors',
                            showFilters ? 'bg-gray-100 border-gray-300 text-gray-700' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50']">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 10h12M10 16h4"/>
                        </svg>
                        Bộ lọc
                        <span v-if="activeFilterCount" class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-indigo-600 text-white text-[10px] font-bold">
                            {{ activeFilterCount }}
                        </span>
                        <svg :class="['w-3 h-3 transition-transform', showFilters ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <button v-if="activeFilterCount" @click="clearFilters"
                        class="px-3 py-1.5 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Xóa lọc
                    </button>
                </div>

                <!-- Phần thu gọn được -->
                <div v-show="showFilters" class="flex flex-wrap items-center gap-2">
                    <select v-model="filterStatus" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none">
                        <option value="">Tất cả trạng thái</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <select v-model="perPage" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none">
                        <option :value="20">20/trang</option>
                        <option :value="50">50/trang</option>
                        <option :value="100">100/trang</option>
                        <option value="all">Tất cả</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col flex-1 min-h-0">
                <div v-if="filtered.length === 0" class="flex-1 min-h-0 flex flex-col justify-center text-center">
                    <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-500 text-sm font-medium">Chưa có đăng ký khám nào</p>
                    <p class="text-gray-400 text-xs mt-1">{{ weekdayLabel }}, {{ formatDateVn(selectedDate) }}</p>
                    <div>
                        <button @click="openCreate"
                            class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tạo đăng ký đầu tiên
                        </button>
                    </div>
                </div>

                <!-- Vùng cuộn duy nhất của trang -->
                <div v-else class="flex-1 min-h-0 overflow-auto">
                <table class="w-full text-sm">
                    <!-- Header dính khi cuộn trong khung bảng -->
                    <thead class="bg-gray-50 sticky top-0 z-10 [&_th]:bg-gray-50 [&_th]:shadow-[inset_0_-1px_0_0_rgb(243,244,246)]">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs">Giờ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs">Bệnh nhân</th>
                            <th class="hidden sm:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">SĐT</th>
                            <th class="hidden md:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">Bác sĩ</th>
                            <th class="hidden lg:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">Ghế</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs">Trạng thái</th>
                            <th class="hidden xl:table-cell px-4 py-3 text-left font-medium text-gray-500 text-xs">Ghi chú</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 text-xs">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="r in paginated" :key="r.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 font-mono text-gray-800 font-medium">{{ r.visit_time ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('patients.show', r.patient_id)"
                                    class="font-medium text-gray-800 hover:text-indigo-600">
                                    {{ r.patient }}
                                </Link>
                                <div class="sm:hidden text-xs text-gray-400">{{ r.patient_phone }}</div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 text-gray-500">{{ r.patient_phone ?? '—' }}</td>
                            <td class="hidden md:table-cell px-4 py-3 text-gray-600">{{ r.doctor }}</td>
                            <td class="hidden lg:table-cell px-4 py-3 text-gray-600">{{ r.chair }}</td>
                            <td class="px-4 py-3">
                                <select
                                    :value="r.status"
                                    @change="changeStatus(r, $event.target.value)"
                                    :class="['text-xs font-medium rounded-full px-2 py-0.5 border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-300', statusClass(r.status_color)]">
                                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                                <div v-if="r.status === 'pending' && r.pending_since && r.registration_date === today"
                                    class="mt-0.5 text-xs font-mono text-yellow-600 flex items-center gap-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ elapsed(r.pending_since) }}
                                </div>
                                <div v-else-if="r.status === 'pending' && r.registration_date < today"
                                    class="mt-0.5 text-xs text-red-500">Chưa chốt</div>
                            </td>
                            <td class="hidden xl:table-cell px-4 py-3 text-gray-500 max-w-xs truncate">{{ r.notes ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEdit(r)"
                                        class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Sửa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="printRow(r)"
                                        class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="In">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </button>
                                    <button @click="openDelete(r)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Xóa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Pagination -->
            <PaginationBar v-if="filtered.length" v-model:page="page"
                :total-pages="totalPages" :pages="pageNumbers"
                :from="pageFrom" :to="pageTo" :total="filtered.length" />
        </div>

        <!-- Create modal -->
        <div v-if="createModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Đăng ký khám mới</h3>
                    <button @click="createModal.open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="p-5 space-y-4">
                    <!-- Patient search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bệnh nhân <span class="text-red-500">*</span></label>
                        <input v-model="patientSearch" type="text" placeholder="Tìm tên hoặc SĐT..."
                            @input="filteredPatients"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        <div v-if="patientSearch && !createForm.patient_id" class="mt-1 max-h-40 overflow-y-auto border border-gray-200 rounded-lg bg-white shadow-sm">
                            <button type="button" v-for="p in patientSuggestions" :key="p.id"
                                @click="selectPatient(p)"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 border-b border-gray-50 last:border-0">
                                <span class="font-medium">{{ p.full_name }}</span>
                                <span class="text-gray-400 ml-2">{{ p.phone }}</span>
                            </button>
                            <div v-if="patientSuggestions.length === 0" class="px-3 py-2 text-sm text-gray-400">Không tìm thấy</div>
                        </div>
                        <div v-if="createForm.patient_id" class="mt-1 flex items-center gap-2 text-sm text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ patientSearch }}
                            <button type="button" @click="clearPatient" class="text-gray-400 hover:text-red-500 ml-auto">✕</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày khám</label>
                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                {{ formatDateVn(today) }} <span class="text-gray-400">(hôm nay)</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giờ vào</label>
                            <input type="time" v-model="createForm.visit_time"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        </div>
                    </div>

                    <p v-if="!isToday" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Bạn đang xem ngày {{ formatDateVn(selectedDate) }}. Đăng ký khám luôn ghi vào ngày hôm nay —
                        muốn đặt trước cho ngày khác thì tạo Lịch hẹn.
                    </p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bác sĩ</label>
                            <select v-model="createForm.doctor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghế</label>
                            <select v-model="createForm.dental_chair_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="c in chairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select v-model="createForm.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea v-model="createForm.notes" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                            placeholder="Ghi chú thêm..." />
                    </div>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="createModal.open = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button type="submit" :disabled="!createForm.patient_id"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
                            Lưu đăng ký
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit modal -->
        <div v-if="editModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Chỉnh sửa đăng ký</h3>
                    <button @click="editModal.open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày khám</label>
                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                {{ formatDateVn(editForm.registration_date) }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giờ vào</label>
                            <input type="time" v-model="editForm.visit_time"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bác sĩ</label>
                            <select v-model="editForm.doctor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghế</label>
                            <select v-model="editForm.dental_chair_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                                <option :value="null">— Chưa chọn —</option>
                                <option v-for="c in chairs" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select v-model="editForm.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea v-model="editForm.notes" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                            placeholder="Ghi chú..." />
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="editModal.open = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete modal -->
        <div v-if="deleteModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="font-semibold text-gray-800 mb-1">Xóa đăng ký khám</h3>
                <p class="text-sm text-gray-500 mb-4">Bệnh nhân: <strong>{{ deleteModal.row?.patient }}</strong> — {{ deleteModal.row?.visit_time ?? '' }}</p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteModal.open = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Hủy</button>
                    <button @click="confirmDelete"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import PaginationBar from '@/Components/Shared/PaginationBar.vue';

const props = defineProps({
    all_registrations: Array,
    statuses:          Array,
    patients:          Array,
    doctors:           Array,
    chairs:            Array,
});

// toISOString() trả về ngày theo giờ UTC, lệch 1 ngày so với server (Asia/Ho_Chi_Minh)
// khi mở trang trước 7h sáng. Luôn lấy ngày theo lịch của chính máy đang dùng.
function toDateKey(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const today = toDateKey(new Date());
const selectedDate = ref(today);
const search = ref('');
const filterStatus = ref('');

const isToday = computed(() => selectedDate.value === today);

function shiftDay(days) {
    const [y, m, d] = selectedDate.value.split('-').map(Number);
    const dt = new Date(y, m - 1, d + days);
    selectedDate.value = toDateKey(dt);
}
function prevDay() { shiftDay(-1); }
function nextDay() { shiftDay(1); }
function goToday() { selectedDate.value = today; }
function clearFilters() { search.value = ''; filterStatus.value = ''; }

const filtered = computed(() => {
    let list = props.all_registrations.filter(r => r.registration_date === selectedDate.value);
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(r =>
            r.patient.toLowerCase().includes(q) ||
            (r.patient_phone && r.patient_phone.includes(q))
        );
    }
    if (filterStatus.value) list = list.filter(r => r.status === filterStatus.value);
    return list;
});

function countByStatus(status) {
    return filtered.value.filter(r => r.status === status).length;
}

const WEEKDAYS = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
const weekdayLabel = computed(() => {
    const [y, m, d] = selectedDate.value.split('-').map(Number);
    return WEEKDAYS[new Date(y, m - 1, d).getDay()];
});

// Tỷ lệ tính trên tổng của đúng ngày đang xem, không tính khi chưa có đăng ký nào
function percentOf(count) {
    const total = filtered.value.length;
    return total ? Math.round((count / total) * 100) : null;
}

const statCards = computed(() => [
    { label: 'Tổng đăng ký', value: filtered.value.length, percent: null,
      accent: 'border-l-gray-300',   text: 'text-gray-800' },
    { label: 'Đang chờ', value: countByStatus('pending'), percent: percentOf(countByStatus('pending')),
      accent: 'border-l-yellow-400', text: 'text-yellow-600' },
    { label: 'Đang làm', value: countByStatus('in_treatment'), percent: percentOf(countByStatus('in_treatment')),
      accent: 'border-l-teal-400',   text: 'text-teal-600' },
    { label: 'Đã xong', value: countByStatus('completed'), percent: percentOf(countByStatus('completed')),
      accent: 'border-l-green-400',  text: 'text-green-600' },
]);

// ── Bộ lọc thu gọn ───────────────────────────────────────────────────────
const showFilters = ref(localStorage.getItem('reg_filters_open') !== '0');
watch(showFilters, v => localStorage.setItem('reg_filters_open', v ? '1' : '0'));

const activeFilterCount = computed(() => [search.value, filterStatus.value].filter(Boolean).length);

// ── Phân trang ───────────────────────────────────────────────────────────
const perPage = ref(localStorage.getItem('reg_per') === 'all' ? 'all' : Number(localStorage.getItem('reg_per') || 50));
const page    = ref(1);

watch(perPage, v => localStorage.setItem('reg_per', String(v)));
watch([search, filterStatus, selectedDate, perPage], () => { page.value = 1; });

const totalPages = computed(() =>
    perPage.value === 'all' ? 1 : Math.max(1, Math.ceil(filtered.value.length / Number(perPage.value)))
);
const paginated = computed(() => {
    if (perPage.value === 'all') return filtered.value;
    const size = Number(perPage.value);
    return filtered.value.slice((page.value - 1) * size, page.value * size);
});
const pageFrom = computed(() => (filtered.value.length === 0 ? 0
    : perPage.value === 'all' ? 1 : (page.value - 1) * Number(perPage.value) + 1));
const pageTo = computed(() => (perPage.value === 'all'
    ? filtered.value.length
    : Math.min(page.value * Number(perPage.value), filtered.value.length)));

// Lọc có thể làm số trang giảm khi đang ở trang cuối
watch(totalPages, t => { if (page.value > t) page.value = t; });

const pageNumbers = computed(() => {
    const total = totalPages.value, cur = page.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    if (cur > 3) pages.push('...');
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
    if (cur < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

function formatDateVn(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

// ── Live timer for pending rows ──────────────────────────────────────────
const nowTs = ref(Date.now());
let timerInterval = null;
onMounted(() => { timerInterval = setInterval(() => { nowTs.value = Date.now(); }, 1000); });
onUnmounted(() => clearInterval(timerInterval));

function elapsed(pendingSince) {
    if (!pendingSince) return null;
    const secs = Math.floor((nowTs.value - new Date(pendingSince).getTime()) / 1000);
    if (secs < 0) return '00:00';
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = secs % 60;
    if (h > 0) return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

const STATUS_BG = {
    gray: 'bg-gray-100 text-gray-700', yellow: 'bg-yellow-100 text-yellow-700',
    teal: 'bg-teal-100 text-teal-700', green: 'bg-green-100 text-green-700',
    red:  'bg-red-100 text-red-700',
};
function statusClass(color) { return STATUS_BG[color] ?? STATUS_BG.gray; }

// ── Status change inline ─────────────────────────────────────────────────
function changeStatus(r, newStatus) {
    router.patch(route('schedule.registrations.patch', r.id), { status: newStatus }, {
        preserveScroll: true,
    });
}

// ── Create modal ─────────────────────────────────────────────────────────
const createModal = ref({ open: false });
const patientSearch = ref('');
// Không có registration_date: server luôn ghi đăng ký vào ngày hôm nay.
const createForm = ref({
    patient_id: null, visit_time: '',
    doctor_id: null, dental_chair_id: null, status: 'pending', notes: '',
});

const patientSuggestions = computed(() => {
    if (!patientSearch.value || createForm.value.patient_id) return [];
    const q = patientSearch.value.toLowerCase();
    return (props.patients ?? [])
        .filter(p => p.full_name.toLowerCase().includes(q) || (p.phone && p.phone.includes(q)))
        .slice(0, 8);
});

function openCreate() {
    createForm.value = {
        patient_id: null, visit_time: '',
        doctor_id: null, dental_chair_id: null,
        status: 'pending', notes: '',
    };
    patientSearch.value = '';
    createModal.value.open = true;
}

function selectPatient(p) {
    createForm.value.patient_id = p.id;
    patientSearch.value = p.full_name + (p.phone ? ` (${p.phone})` : '');
}

function clearPatient() {
    createForm.value.patient_id = null;
    patientSearch.value = '';
}

function submitCreate() {
    router.post(route('schedule.registrations.store'), createForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            createModal.value.open = false;
            // Bản ghi vừa tạo luôn nằm ở hôm nay, kéo màn hình về đó để thấy ngay.
            selectedDate.value = today;
        },
    });
}

// ── Edit modal ───────────────────────────────────────────────────────────
const editModal = ref({ open: false });
const editForm = ref({
    id: null, registration_date: '', visit_time: '',
    doctor_id: null, dental_chair_id: null, status: 'pending', notes: '',
});

function openEdit(r) {
    editForm.value = {
        id:                r.id,
        registration_date: r.registration_date,
        visit_time:        r.visit_time ?? '',
        doctor_id:         r.doctor_id,
        dental_chair_id:   r.dental_chair_id,
        status:            r.status,
        notes:             r.notes ?? '',
    };
    editModal.value.open = true;
}

function submitEdit() {
    // registration_date chỉ để hiển thị, không gửi lên: ngày khám là cố định.
    const { id, registration_date, ...payload } = editForm.value;
    router.put(route('schedule.registrations.update', id), payload, {
        preserveScroll: true,
        onSuccess: () => { editModal.value.open = false; },
    });
}

// ── Print ────────────────────────────────────────────────────────────────
function printRow(r) {
    const w = window.open('', '_blank', 'width=400,height=300');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Đăng ký khám</title>
    <style>body{font-family:Arial,sans-serif;font-size:13px;padding:16px}h3{margin:0 0 8px}table{width:100%}td{padding:3px 0}td:first-child{color:#555;width:110px}.val{font-weight:600}</style>
    </head><body>
    <h3>PHIẾU ĐĂNG KÝ KHÁM</h3>
    <table>
    <tr><td>Bệnh nhân</td><td class="val">${r.patient}</td></tr>
    <tr><td>SĐT</td><td class="val">${r.patient_phone ?? '—'}</td></tr>
    <tr><td>Ngày khám</td><td class="val">${formatDateVn(r.registration_date)}</td></tr>
    <tr><td>Giờ</td><td class="val">${r.visit_time ?? '—'}</td></tr>
    <tr><td>Bác sĩ</td><td class="val">${r.doctor}</td></tr>
    <tr><td>Ghế</td><td class="val">${r.chair}</td></tr>
    <tr><td>Trạng thái</td><td class="val">${r.status_label}</td></tr>
    ${r.notes ? `<tr><td>Ghi chú</td><td class="val">${r.notes}</td></tr>` : ''}
    </table>
    <script>window.onload=()=>{window.print();window.close();}<\/script>
    </body></html>`);
    w.document.close();
}

// ── Delete ───────────────────────────────────────────────────────────────
const deleteModal = ref({ open: false, row: null });

function openDelete(row) { deleteModal.value = { open: true, row }; }

function confirmDelete() {
    router.delete(route('schedule.registrations.destroy', deleteModal.value.row.id), {
        preserveScroll: true,
        onSuccess: () => { deleteModal.value.open = false; },
    });
}
</script>
