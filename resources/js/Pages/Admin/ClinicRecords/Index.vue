<template>
    <AppLayout title="Bảng ghi phòng khám">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-xl font-bold text-gray-800">Bảng ghi phòng khám</h1>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">{{ records.total }} bản ghi</span>
                    <a :href="route('admin.clinic-records.template')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-green-300 text-green-700 hover:bg-green-50 transition-colors font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Tải mẫu Excel
                    </a>
                    <button @click="openImport"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                        </svg>
                        Import Excel
                    </button>
                </div>
            </div>

            <!-- Bulk action bar -->
            <Transition enter-active-class="transition-all duration-150" enter-from-class="opacity-0 -translate-y-2"
                leave-active-class="transition-all duration-150" leave-to-class="opacity-0 -translate-y-2">
                <div v-if="selectedIds.size > 0 || selectAllMode"
                    class="flex flex-col gap-2 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-red-700">
                            <template v-if="selectAllMode">
                                Đã chọn <strong>tất cả {{ records.total }}</strong> bản ghi (toàn bộ {{ records.last_page }} trang)
                            </template>
                            <template v-else>
                                Đã chọn <strong>{{ selectedIds.size }}</strong> bản ghi trên trang này
                            </template>
                        </span>
                        <div class="flex items-center gap-2">
                            <button @click="clearSelection"
                                class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-white transition-colors">
                                Bỏ chọn tất cả
                            </button>
                            <button @click="confirmBulkDelete"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Xóa {{ selectAllMode ? records.total : selectedIds.size }} bản ghi
                            </button>
                        </div>
                    </div>
                    <!-- Cross-page select all hint -->
                    <div v-if="!selectAllMode && records.last_page > 1 && isAllSelected"
                        class="text-xs text-red-600 bg-red-100 rounded-lg px-3 py-1.5 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Đang chọn {{ selectedIds.size }} bản ghi trên trang này.</span>
                        <button @click="selectAllMode = true"
                            class="underline font-semibold hover:text-red-800 whitespace-nowrap">
                            Chọn tất cả {{ records.total }} bản ghi trong kết quả lọc →
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap gap-3">
                    <input v-model="form.search" type="search"
                        placeholder="Tìm tên KH, mã KH, dịch vụ, bác sĩ, SĐT..."
                        class="flex-1 min-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @keyup.enter="applyFilters" />
                    <select v-model="form.record_type"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="">Tất cả loại</option>
                        <option v-for="t in record_types" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <select v-model="form.year"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="">Tất cả năm</option>
                        <option value="unknown">⚠ Ngày không xác định ({{ unknown_year_count }})</option>
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <input v-model="form.date_from" type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters" />
                    <input v-model="form.date_to" type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters" />
                    <select v-model="form.per_page"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="applyFilters">
                        <option value="20">20 / trang</option>
                        <option value="50">50 / trang</option>
                        <option value="100">100 / trang</option>
                        <option value="all">Tất cả</option>
                    </select>
                    <button @click="applyFilters"
                        class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors">Lọc</button>
                    <button v-if="hasFilters" @click="clearFilters"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">Xóa lọc</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                                <!-- Select all checkbox -->
                                <th class="px-3 py-3 w-10">
                                    <input type="checkbox" :checked="isAllSelected" :indeterminate="isIndeterminate"
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer" />
                                </th>
                                <th class="px-3 py-3 text-left w-12">#</th>
                                <th class="px-3 py-3 text-left">Ngày</th>
                                <th class="px-3 py-3 text-left">Mã KH</th>
                                <th class="px-3 py-3 text-left">Tên khách hàng</th>
                                <th class="px-3 py-3 text-left">Loại</th>
                                <th class="px-3 py-3 text-left">Dịch vụ</th>
                                <th class="px-3 py-3 text-right">Đơn giá</th>
                                <th class="px-3 py-3 text-right">SL</th>
                                <th class="px-3 py-3 text-right">Khuyến mại</th>
                                <th class="px-3 py-3 text-right">Thành tiền</th>
                                <th class="px-3 py-3 text-right">Tổng thu</th>
                                <th class="px-3 py-3 text-right">Còn nợ</th>
                                <th class="px-3 py-3 text-right hidden lg:table-cell">Thu kỳ này</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Nguồn quỹ</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Bước tiến trình</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Bác sĩ</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Tư vấn</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Trợ thủ</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">SĐT</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="records.data.length === 0">
                                <td colspan="21" class="px-4 py-12 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                            <tr v-for="r in records.data" :key="r.id"
                                :class="['transition-colors', selectedIds.has(r.id) ? 'bg-primary-50' : 'hover:bg-gray-50']">
                                <td class="px-3 py-2.5">
                                    <input type="checkbox" :checked="selectedIds.has(r.id)"
                                        @change="toggleRow(r.id)"
                                        class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer" />
                                </td>
                                <td class="px-3 py-2.5 text-gray-400 text-xs">{{ r.id }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ r.record_date }}</td>
                                <td class="px-3 py-2.5 font-mono text-xs text-primary-700">{{ r.patient_code }}</td>
                                <td class="px-3 py-2.5 font-medium text-gray-800">{{ r.patient_name }}</td>
                                <td class="px-3 py-2.5">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        r.record_type === 'Thanh toán' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700']">
                                        {{ r.record_type }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 max-w-[180px] truncate" :title="r.service_name">{{ r.service_name }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ fmt(r.unit_price) }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ r.quantity }}</td>
                                <td class="px-3 py-2.5 text-right text-orange-600">{{ fmt(r.discount) }}</td>
                                <td class="px-3 py-2.5 text-right font-medium text-gray-800">{{ fmt(r.amount) }}</td>
                                <td class="px-3 py-2.5 text-right text-green-700">{{ fmt(r.total_collected) }}</td>
                                <td class="px-3 py-2.5 text-right text-red-600">{{ fmt(r.remaining_debt) }}</td>
                                <td class="px-3 py-2.5 text-right hidden lg:table-cell text-green-600">{{ fmt(r.collected_this_period) }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-600 text-xs">{{ r.fund_name }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-600 text-xs max-w-[120px] truncate" :title="r.treatment_step">{{ r.treatment_step }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-700">{{ r.doctor_name }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.consultant_name }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.assistant_name }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.phone }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell">
                                    <span v-if="r.status" class="text-xs text-gray-500">{{ r.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="records.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500">
                        Trang {{ records.current_page }} / {{ records.last_page }} — {{ records.total }} bản ghi
                    </span>
                    <div class="flex gap-1">
                        <button v-for="link in records.links" :key="link.label"
                            :disabled="!link.url || link.active"
                            @click="goToPage(link.url)"
                            v-html="link.label"
                            :class="['px-3 py-1 rounded text-xs border transition-colors',
                                link.active ? 'bg-primary-600 text-white border-primary-600'
                                    : link.url ? 'border-gray-300 text-gray-600 hover:bg-gray-100'
                                    : 'border-gray-200 text-gray-300 cursor-not-allowed']" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Delete Confirm Modal ────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Xác nhận xóa</h3>
                            <p class="text-sm text-gray-500">Thao tác này không thể hoàn tác</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700">
                        Bạn chắc chắn muốn xóa
                        <strong class="text-red-600">
                            {{ selectAllMode ? `tất cả ${records.total}` : selectedIds.size }} bản ghi
                        </strong>
                        <span v-if="selectAllMode"> khớp với bộ lọc hiện tại</span>?
                    </p>
                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="showDeleteConfirm = false"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Hủy
                        </button>
                        <button @click="doBulkDelete" :disabled="deleting"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60">
                            <svg v-if="deleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ deleting ? 'Đang xóa...' : 'Xóa ngay' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Import Modal ─────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showImport" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeImport" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">

                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">Import dữ liệu từ Excel</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span v-if="step === 1">Chọn file Excel để tải lên · Bước 1 / 2</span>
                                <span v-else>Kiểm tra dữ liệu trước khi xác nhận · Bước 2 / 2</span>
                            </p>
                        </div>
                        <button @click="closeImport" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Step indicator -->
                    <div class="flex items-center px-6 py-3 bg-gray-50 border-b border-gray-100">
                        <div :class="['flex items-center gap-2 text-sm font-medium', step >= 1 ? 'text-primary-600' : 'text-gray-400']">
                            <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold', step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500']">1</span>
                            Tải file lên
                        </div>
                        <div class="h-px flex-1 mx-3 bg-gray-300" />
                        <div :class="['flex items-center gap-2 text-sm font-medium', step >= 2 ? 'text-primary-600' : 'text-gray-400']">
                            <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold', step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500']">2</span>
                            Xem trước & Xác nhận
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-y-auto">

                        <!-- Step 1: Upload -->
                        <div v-if="step === 1" class="p-6 space-y-4">
                            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-sm text-blue-700 flex gap-2.5">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Chưa có file mẫu? Hãy
                                    <a :href="route('admin.clinic-records.template')" class="font-semibold underline hover:text-blue-900">tải mẫu Excel</a>,
                                    điền dữ liệu rồi tải lên bên dưới.
                                </span>
                            </div>

                            <!-- Drop zone -->
                            <label class="flex flex-col items-center justify-center gap-3 border-2 border-dashed rounded-xl p-10 cursor-pointer transition-colors"
                                :class="dragOver ? 'border-primary-400 bg-primary-50' : 'border-gray-300 hover:border-primary-300 hover:bg-gray-50'"
                                @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                @drop.prevent="onDrop">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-gray-700">
                                        <span v-if="!selectedFile">Kéo thả file vào đây hoặc <span class="text-primary-600 underline">chọn file</span></span>
                                        <span v-else class="text-primary-700">{{ selectedFile.name }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">Hỗ trợ: .xlsx, .xls · Tối đa 20MB</p>
                                </div>
                                <input ref="fileInput" type="file" accept=".xlsx,.xls" class="hidden" @change="onFileChange" />
                            </label>

                            <!-- Upload progress -->
                            <div v-if="uploading" class="space-y-1.5">
                                <div class="flex justify-between text-xs text-gray-600">
                                    <span>Đang xử lý file...</span>
                                    <span class="font-semibold text-primary-600">{{ uploadProgress }}%</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 rounded-full transition-all duration-300 ease-out"
                                        :style="{ width: uploadProgress + '%' }" />
                                </div>
                            </div>

                            <p v-if="uploadError" class="text-sm text-red-600 flex gap-1.5 items-center">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ uploadError }}
                            </p>
                        </div>

                        <!-- Step 2: Preview -->
                        <div v-else class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ previewData.total }} bản ghi
                                    </span>
                                    <span v-if="previewData.total > 20" class="text-xs text-gray-400">(hiển thị 20 dòng đầu)</span>
                                </div>
                                <span class="text-xs text-gray-400">{{ selectedFile?.name }}</span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-xs whitespace-nowrap">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="px-2 py-2 text-center text-gray-400 w-8">#</th>
                                            <th v-for="(h, i) in previewData.headers" :key="i"
                                                class="px-3 py-2 text-left font-semibold text-gray-600 border-l border-gray-100">
                                                {{ h }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(row, ri) in previewData.preview" :key="ri" class="hover:bg-gray-50">
                                            <td class="px-2 py-1.5 text-center text-gray-300">{{ ri + 1 }}</td>
                                            <td v-for="(cell, ci) in row" :key="ci"
                                                class="px-3 py-1.5 text-gray-700 border-l border-gray-100 max-w-[160px] truncate"
                                                :title="String(cell ?? '')">
                                                {{ cell ?? '' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Import progress bar (hiện khi đang import) -->
                            <div v-if="importing" class="space-y-1.5">
                                <div class="flex justify-between text-xs font-medium">
                                    <span class="text-gray-600">Đang import dữ liệu vào hệ thống...</span>
                                    <span class="text-green-600">{{ importProgress }}% — {{ importInserted.toLocaleString('vi-VN') }} / {{ previewData.total.toLocaleString('vi-VN') }} bản ghi</span>
                                </div>
                                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full transition-all duration-300 ease-out"
                                        :style="{ width: importProgress + '%' }" />
                                </div>
                            </div>

                            <p v-else class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 border border-amber-200">
                                Sau khi xác nhận, toàn bộ <strong>{{ previewData.total }}</strong> bản ghi sẽ được thêm vào hệ thống. Thao tác này không thể hoàn tác.
                            </p>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                        <button v-if="step === 2" @click="step = 1"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Quay lại
                        </button>
                        <div v-else />

                        <div class="flex items-center gap-2">
                            <button @click="closeImport"
                                class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                Hủy
                            </button>

                            <button v-if="step === 1" :disabled="!selectedFile || uploading"
                                @click="uploadPreview"
                                class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="selectedFile && !uploading ? 'bg-primary-600 text-white hover:bg-primary-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                                <svg v-if="uploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ uploading ? `${uploadProgress}%` : 'Xem trước dữ liệu' }}
                            </button>

                            <button v-else :disabled="importing"
                                @click="confirmImport"
                                class="flex items-center gap-1.5 px-5 py-2 text-sm font-semibold rounded-lg transition-colors"
                                :class="!importing ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                                <svg v-if="importing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ importing
                                    ? `Đang import... ${importProgress}%`
                                    : `Xác nhận import ${previewData.total} bản ghi` }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    records: Object,
    filters: Object,
    record_types: Array,
    years: Array,
    unknown_year_count: Number,
    total_all: Number,
});

// ── Filters ───────────────────────────────────────────────────
const form = reactive({
    search:      props.filters?.search      ?? '',
    record_type: props.filters?.record_type ?? '',
    year:        props.filters?.year        ?? '',
    date_from:   props.filters?.date_from   ?? '',
    date_to:     props.filters?.date_to     ?? '',
    per_page:    props.filters?.per_page    ?? '50',
});

const hasFilters = computed(() => form.search || form.record_type || form.year || form.date_from || form.date_to);

function applyFilters() {
    router.get(route('admin.clinic-records.index'), {
        search:      form.search      || undefined,
        record_type: form.record_type || undefined,
        year:        form.year        || undefined,
        date_from:   form.date_from   || undefined,
        date_to:     form.date_to     || undefined,
        per_page:    form.per_page !== '50' ? form.per_page : undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    form.search = ''; form.record_type = ''; form.year = ''; form.date_from = ''; form.date_to = '';
    applyFilters();
}

function goToPage(url) {
    if (url) router.visit(url, { preserveState: true });
}

function fmt(val) {
    if (!val) return '—';
    return Number(val).toLocaleString('vi-VN');
}

// ── Bulk select ───────────────────────────────────────────────
const selectedIds   = ref(new Set());
const selectAllMode = ref(false);  // true = chọn tất cả mọi trang

const pageIds = computed(() => props.records.data.map(r => r.id));
const isAllSelected   = computed(() => pageIds.value.length > 0 && pageIds.value.every(id => selectedIds.value.has(id)));
const isIndeterminate = computed(() => !isAllSelected.value && pageIds.value.some(id => selectedIds.value.has(id)));

function toggleRow(id) {
    selectAllMode.value = false;
    const s = new Set(selectedIds.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selectedIds.value = s;
}

function toggleSelectAll() {
    selectAllMode.value = false;
    const s = new Set(selectedIds.value);
    if (isAllSelected.value) {
        pageIds.value.forEach(id => s.delete(id));
    } else {
        pageIds.value.forEach(id => s.add(id));
    }
    selectedIds.value = s;
}

function clearSelection() {
    selectedIds.value = new Set();
    selectAllMode.value = false;
}

// ── Bulk delete ───────────────────────────────────────────────
const showDeleteConfirm = ref(false);
const deleting = ref(false);

function confirmBulkDelete() {
    showDeleteConfirm.value = true;
}

function doBulkDelete() {
    if (deleting.value) return;
    deleting.value = true;

    const payload = selectAllMode.value
        ? {
            select_all:  true,
            search:      form.search      || undefined,
            record_type: form.record_type || undefined,
            year:        form.year        || undefined,
            date_from:   form.date_from   || undefined,
            date_to:     form.date_to     || undefined,
          }
        : { ids: [...selectedIds.value] };

    router.delete(route('admin.clinic-records.bulk-delete'), {
        data: payload,
        onSuccess: () => {
            selectedIds.value = new Set();
            selectAllMode.value = false;
            showDeleteConfirm.value = false;
        },
        onFinish: () => { deleting.value = false; },
    });
}

// ── Import modal ──────────────────────────────────────────────
const showImport     = ref(false);
const step           = ref(1);
const fileInput      = ref(null);
const selectedFile   = ref(null);
const dragOver       = ref(false);
const uploading      = ref(false);
const uploadProgress = ref(0);
const importing      = ref(false);
const importProgress = ref(0);
const importInserted = ref(0);
const uploadError    = ref('');
const previewData    = ref({ total: 0, headers: [], preview: [], temp_id: '' });

function openImport() {
    step.value = 1;
    selectedFile.value = null;
    uploadError.value = '';
    uploadProgress.value = 0;
    importProgress.value = 0;
    importInserted.value = 0;
    previewData.value = { total: 0, headers: [], preview: [], temp_id: '' };
    showImport.value = true;
}

function closeImport() {
    if (uploading.value || importing.value) return;
    showImport.value = false;
}

function onFileChange(e) {
    const f = e.target.files?.[0];
    if (f) { selectedFile.value = f; uploadError.value = ''; uploadProgress.value = 0; }
}

function onDrop(e) {
    dragOver.value = false;
    const f = e.dataTransfer?.files?.[0];
    if (!f) return;
    if (!f.name.match(/\.(xlsx|xls)$/i)) {
        uploadError.value = 'Chỉ hỗ trợ file .xlsx hoặc .xls';
        return;
    }
    selectedFile.value = f;
    uploadError.value = '';
    uploadProgress.value = 0;
}

async function uploadPreview() {
    if (!selectedFile.value || uploading.value) return;
    uploading.value = true;
    uploadProgress.value = 0;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const { data } = await axios.post(route('admin.clinic-records.preview'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress(e) {
                // Upload phase: 0→80%
                if (e.total) uploadProgress.value = Math.round((e.loaded / e.total) * 80);
            },
        });
        // Server parsing phase: jump to 100%
        uploadProgress.value = 100;
        previewData.value = data;
        step.value = 2;
    } catch (err) {
        uploadError.value = err.response?.data?.error
            ?? err.response?.data?.message
            ?? 'Có lỗi xảy ra khi xử lý file.';
    } finally {
        uploading.value = false;
    }
}

const CHUNK_SIZE = 500;

async function confirmImport() {
    if (importing.value) return;
    importing.value = true;
    importProgress.value = 0;
    importInserted.value = 0;

    const tempId = previewData.value.temp_id;
    let offset   = 0;
    let serverTotal = previewData.value.total;
    let totalInserted = 0;

    try {
        while (true) {
            const { data } = await axios.post(route('admin.clinic-records.import-chunk'), {
                temp_id: tempId,
                offset,
                limit: CHUNK_SIZE,
            });

            serverTotal        = data.total;
            totalInserted     += data.inserted ?? 0;
            importInserted.value = totalInserted;
            importProgress.value  = serverTotal > 0
                ? Math.min(100, Math.round((data.processed / serverTotal) * 100))
                : 100;

            if (data.done) break;
            offset += CHUNK_SIZE;
        }

        router.visit(route('admin.clinic-records.index'), {
            preserveState: false,
            onSuccess: () => { showImport.value = false; importing.value = false; },
        });
    } catch (err) {
        importing.value = false;
        const res = err.response;
        uploadError.value = res?.data?.error
            ?? res?.data?.message
            ?? (res ? `Lỗi server ${res.status}` : 'Không kết nối được server.')
        step.value = 1;
    }
}
</script>
