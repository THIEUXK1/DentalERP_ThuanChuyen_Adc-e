<template>
    <AppLayout title="Bảng kế hoạch báo cáo theo ngày">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Bảng kế hoạch báo cáo theo ngày</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Nhật ký giao dịch hợp nhất — dịch vụ đã thực hiện và thanh toán đã ghi nhận trong hệ thống</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 whitespace-nowrap">{{ filteredRecords.length.toLocaleString('vi-VN') }} bản ghi</span>
                    <button @click="openExport"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-green-300 text-green-700 hover:bg-green-50 transition-colors font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Xuất Excel
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap gap-3">
                    <input v-model="form.search" type="search" list="patient-suggestions"
                        placeholder="Tìm tên KH, mã KH, dịch vụ, SĐT, mã chứng từ..."
                        class="flex-1 min-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    <select v-model="form.record_type"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Tất cả loại</option>
                        <option value="service">Thủ thuật</option>
                        <option value="payment">Thanh toán</option>
                    </select>
                    <select v-if="branches.length > 0" v-model="form.branch_id"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="reloadFromServer">
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <select v-model="form.year"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="reloadFromServer">
                        <option value="">Tất cả năm</option>
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <input v-model="form.date_from" type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="reloadFromServer" />
                    <input v-model="form.date_to" type="date"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @change="reloadFromServer" />
                    <select v-model="form.per_page"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="20">20 / trang</option>
                        <option value="50">50 / trang</option>
                        <option value="100">100 / trang</option>
                        <option value="200">200 / trang</option>
                        <option value="500">500 / trang</option>
                        <option value="1000">1000 / trang</option>
                    </select>
                    <button @click="showAdvanced = !showAdvanced"
                        :class="['inline-flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg border transition-colors font-medium',
                            showAdvanced || hasAdvancedFilters
                                ? 'border-primary-400 text-primary-700 bg-primary-50'
                                : 'border-gray-300 text-gray-600 hover:bg-gray-50']">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Lọc nâng cao
                        <span v-if="hasAdvancedFilters" class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                    </button>
                    <button v-if="hasFilters" @click="clearFilters"
                        class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">Xóa lọc</button>
                </div>

                <!-- Advanced filter panel -->
                <div v-if="showAdvanced" class="mt-3 pt-3 border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Tên khách hàng</label>
                            <input v-model="form.patient_name" type="text" list="patient-suggestions" placeholder="Tên khách hàng..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Bác sĩ</label>
                            <SearchableSelect :model-value="form.doctor_id" @update:model-value="v => setFilter('doctor_id', v)"
                                :options="doctorOptions" placeholder="Tất cả bác sĩ" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Tư vấn</label>
                            <select v-model="form.consultant_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Tất cả tư vấn</option>
                                <option v-for="c in consultants" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Trợ thủ</label>
                            <select v-model="form.assistant_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Tất cả trợ thủ</option>
                                <option v-for="a in assistants" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>
                        <!-- "Nhóm dịch vụ" (service_groups) tạm ẩn — chưa có dữ liệu, sẽ bật lại khi Danh mục có nhóm. -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nhóm thủ thuật</label>
                            <SearchableSelect :model-value="form.category_id" @update:model-value="v => setFilter('category_id', v)"
                                :options="categoryOptions" placeholder="Tất cả nhóm thủ thuật" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Diễn giải (dịch vụ)</label>
                            <SearchableSelect :model-value="form.service_id" @update:model-value="v => setFilter('service_id', v)"
                                :options="serviceOptions" placeholder="Tất cả dịch vụ" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nguồn</label>
                            <select v-model="form.source"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Tất cả nguồn</option>
                                <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Trạng thái</label>
                            <select v-model="form.status"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Tất cả trạng thái</option>
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Mã chứng từ</label>
                            <input v-model="form.reference_code" type="text" list="reference-suggestions" placeholder="Mã KHDT / hóa đơn..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Thành tiền từ</label>
                            <input v-model="form.amount_min" type="number" placeholder="0"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 tabular-nums" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Thành tiền đến</label>
                            <input v-model="form.amount_max" type="number" placeholder="0"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 tabular-nums" />
                        </div>
                        <div v-if="hasAdvancedFilters" class="flex items-end">
                            <button @click="clearAdvancedFilters"
                                class="px-3 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">Xóa lọc nâng cao</button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Kết quả được lọc ngay khi bạn chọn — không cần bấm nút. Có thể kết hợp với khoảng ngày (Từ ngày / Đến ngày) ở trên để thu hẹp kết quả.</p>
                </div>

                <!-- Gợi ý gõ-tìm, lấy từ các bản ghi đang hiển thị -->
                <datalist id="patient-suggestions">
                    <option v-for="name in patientNameSuggestions" :key="name" :value="name" />
                </datalist>
                <datalist id="reference-suggestions">
                    <option v-for="code in referenceCodeSuggestions" :key="code" :value="code" />
                </datalist>
            </div>

            <!-- Data-window cap warning -->
            <div v-if="truncated" class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 text-sm text-amber-700">
                Khoảng ngày đã chọn có quá nhiều bản ghi — chỉ tải {{ records.length.toLocaleString('vi-VN') }} dòng đầu tiên. Thu hẹp khoảng ngày để xem đầy đủ.
            </div>

            <!-- Totals for the currently filtered result set -->
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-1.5 text-sm">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Tổng theo bộ lọc</span>
                <span class="text-gray-600">SL: <strong class="text-gray-800 tabular-nums">{{ fmt(totals.quantity) }}</strong></span>
                <span class="text-gray-600">Đơn giá × SL: <strong class="text-gray-800 tabular-nums">{{ fmt(totals.gross) }}</strong></span>
                <span class="text-gray-600">Khuyến mại: <strong class="text-orange-600 tabular-nums">{{ fmt(totals.discount) }}</strong></span>
                <span class="text-gray-600">Thành tiền dịch vụ: <strong class="tabular-nums" :class="totals.service_amount < 0 ? 'text-red-600' : 'text-gray-800'">{{ fmt(totals.service_amount) }}</strong></span>
                <span class="text-gray-600">Tiền thu: <strong class="text-emerald-700 tabular-nums">{{ fmt(totals.payment_collected) }}</strong></span>
                <span class="text-gray-600">Tiền hoàn: <strong class="text-red-600 tabular-nums">{{ fmt(totals.payment_refunded) }}</strong></span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                                <th class="px-3 py-3 text-left">Ngày</th>
                                <th class="px-3 py-3 text-left">Mã KH</th>
                                <th class="px-3 py-3 text-left">Tên khách hàng</th>
                                <th class="px-3 py-3 text-left">Loại</th>
                                <th class="px-3 py-3 text-left">Diễn giải</th>
                                <th class="px-3 py-3 text-right">Đơn giá</th>
                                <th class="px-3 py-3 text-right">SL</th>
                                <th class="px-3 py-3 text-right">Khuyến mại</th>
                                <th class="px-3 py-3 text-right">Thành tiền</th>
                                <th class="px-3 py-3 text-left hidden lg:table-cell">Chứng từ</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Bác sĩ</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Tư vấn</th>
                                <th class="px-3 py-3 text-left hidden 2xl:table-cell">Trợ thủ</th>
                                <th class="px-3 py-3 text-left hidden xl:table-cell">Chi nhánh</th>
                                <th class="px-3 py-3 text-left">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="pagedRecords.length === 0">
                                <td colspan="15" class="px-4 py-12 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                            <tr v-for="r in pagedRecords" :key="r.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ formatDate(r.record_date) }}</td>
                                <td class="px-3 py-2.5">
                                    <Link v-if="r.patient_id" :href="route('patients.show', r.patient_id)"
                                        class="font-mono text-xs text-primary-700 hover:underline">{{ r.patient_code }}</Link>
                                </td>
                                <td class="px-3 py-2.5 font-medium text-gray-800">{{ r.patient_name }}</td>
                                <td class="px-3 py-2.5">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        r.record_type === 'payment' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700']">
                                        {{ r.record_type_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 max-w-[200px] truncate" :title="r.description">{{ r.description }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ fmt(r.unit_price) }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ r.quantity ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-orange-600">{{ fmt(r.discount) }}</td>
                                <td class="px-3 py-2.5 text-right font-medium"
                                    :class="r.amount < 0 ? 'text-red-600' : 'text-gray-800'">{{ fmt(r.amount) }}</td>
                                <td class="px-3 py-2.5 hidden lg:table-cell">
                                    <Link :href="referenceUrl(r)" class="font-mono text-xs text-indigo-600 hover:underline">{{ r.reference_code }}</Link>
                                </td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-700">{{ r.doctor_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.consultant_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 hidden 2xl:table-cell text-gray-600 text-xs">{{ r.assistant_name ?? '—' }}</td>
                                <td class="px-3 py-2.5 hidden xl:table-cell text-gray-600 text-xs">{{ r.branch_name }}</td>
                                <td class="px-3 py-2.5">
                                    <span class="text-xs text-gray-500">{{ r.status_label }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <span class="text-xs text-gray-500">
                        Trang {{ currentPage }} / {{ totalPages }} — {{ filteredRecords.length.toLocaleString('vi-VN') }} bản ghi
                    </span>
                    <div class="flex gap-1">
                        <button :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)"
                            :class="['px-3 py-1 rounded text-xs border transition-colors',
                                currentPage > 1 ? 'border-gray-300 text-gray-600 hover:bg-gray-100' : 'border-gray-200 text-gray-300 cursor-not-allowed']">‹ Trước</button>
                        <button v-for="p in pageNumbers" :key="p"
                            @click="goToPage(p)"
                            :class="['px-3 py-1 rounded text-xs border transition-colors',
                                p === currentPage ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 text-gray-600 hover:bg-gray-100']">{{ p }}</button>
                        <button :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)"
                            :class="['px-3 py-1 rounded text-xs border transition-colors',
                                currentPage < totalPages ? 'border-gray-300 text-gray-600 hover:bg-gray-100' : 'border-gray-200 text-gray-300 cursor-not-allowed']">Sau ›</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export modal -->
        <Teleport to="body">
            <div v-if="showExport" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeExport" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                    <div>
                        <h3 class="font-bold text-gray-800">Xuất Excel</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Chọn khoảng thời gian cần xuất dữ liệu</p>
                    </div>

                    <div v-if="years.length > 0">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Chọn nhanh theo năm</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="y in years" :key="y" :disabled="exporting" @click="exportYear(y)"
                                class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-600 hover:border-green-400 hover:text-green-700 hover:bg-green-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Cả năm {{ y }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-px bg-gray-200" />
                        <span class="text-xs text-gray-400">hoặc chọn khoảng ngày</span>
                        <div class="flex-1 h-px bg-gray-200" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Từ ngày</label>
                            <input v-model="exportForm.date_from" type="date" :disabled="exporting"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-400" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Đến ngày</label>
                            <input v-model="exportForm.date_to" type="date" :disabled="exporting"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-400" />
                        </div>
                    </div>

                    <!-- Export progress -->
                    <div v-if="exporting" class="space-y-1.5">
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>Đang xuất dữ liệu...</span>
                            <span class="font-semibold text-green-600">{{ exportProgress }}%</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full transition-all duration-300 ease-out"
                                :style="{ width: exportProgress + '%' }" />
                        </div>
                    </div>

                    <p v-if="exportError" class="text-sm text-red-600 flex gap-1.5 items-center">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ exportError }}
                    </p>
                    <p v-if="!exporting" class="text-xs text-gray-400">
                        Các bộ lọc đang áp dụng (loại, chi nhánh, tìm kiếm...) trên trang sẽ được giữ nguyên khi xuất.
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="closeExport" :disabled="exporting"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Hủy
                        </button>
                        <button @click="confirmExport" :disabled="exporting"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg v-if="exporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ exporting ? `${exportProgress}%` : 'Xuất Excel' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import SearchableSelect from '@/Components/Shared/SearchableSelect.vue';
import { matchesQuery } from '@/utils/text';

const props = defineProps({
    records: Array,
    truncated: { type: Boolean, default: false },
    filters: Object,
    branches: Array,
    years: Array,
    doctors: { type: Array, default: () => [] },
    consultants: { type: Array, default: () => [] },
    assistants: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

// Only date_from/date_to/year/branch_id bound what the server sends — they're the only fields
// that need a fresh request. Everything else below is applied 100% client-side against
// `props.records` (search, doctor/category/service/status/... filters, sorting, pagination),
// so picking them never round-trips to the server.
const form = reactive({
    search:          '',
    record_type:     '',
    branch_id:       props.filters?.branch_id       ?? '',
    year:            props.filters?.year            ?? '',
    date_from:       props.filters?.date_from       ?? '',
    date_to:         props.filters?.date_to         ?? '',
    per_page:        '50',
    patient_name:    '',
    doctor_id:       '',
    consultant_id:   '',
    assistant_id:    '',
    reference_code:  '',
    amount_min:      '',
    amount_max:      '',
    category_id:     '',
    service_id:      '',
    source:          '',
    status:          '',
});

// SearchableSelect wants {value, label} — the props come as {id, name}.
const doctorOptions   = computed(() => props.doctors.map(d => ({ value: d.id, label: d.name })));
const categoryOptions = computed(() => props.categories.map(c => ({ value: c.id, label: c.name })));
// Chọn "Nhóm thủ thuật" trước thì "Diễn giải (dịch vụ)" chỉ hiện dịch vụ thuộc nhóm đó.
const serviceOptions  = computed(() => props.services
    .filter(s => !form.category_id || String(s.category_id) === String(form.category_id))
    .map(s => ({ value: s.id, label: s.name })));

function setFilter(key, value) {
    form[key] = value ?? '';
    if (key === 'category_id' && form.service_id) {
        const stillValid = props.services.some(s => String(s.id) === String(form.service_id) && String(s.category_id) === String(value));
        if (!stillValid) form.service_id = '';
    }
}

const showAdvanced = ref(
    !!(form.patient_name || form.doctor_id || form.consultant_id || form.assistant_id || form.reference_code || form.amount_min || form.amount_max
        || form.category_id || form.service_id || form.source || form.status)
);

const hasAdvancedFilters = computed(() =>
    form.patient_name || form.doctor_id || form.consultant_id || form.assistant_id || form.reference_code || form.amount_min || form.amount_max
        || form.category_id || form.service_id || form.source || form.status
);

const hasFilters = computed(() =>
    form.search || form.record_type || form.branch_id || form.year || form.date_from || form.date_to || hasAdvancedFilters.value
);

// Gợi ý gõ-tìm cho ô "Tìm kiếm" / "Tên khách hàng" / "Mã chứng từ" — lấy từ toàn bộ dữ liệu
// đã tải cho khoảng ngày hiện tại, không cần gọi API riêng.
const patientNameSuggestions = computed(() =>
    [...new Set(props.records.map(r => r.patient_name).filter(Boolean))]
);
const referenceCodeSuggestions = computed(() =>
    [...new Set(props.records.map(r => r.reference_code).filter(Boolean))]
);

// 100% client-side filtering — everything except the date/year/branch window (already applied
// server-side) runs here against the already-loaded `props.records`.
function matchesFilters(r) {
    if (form.record_type && r.record_type !== form.record_type) return false;
    if (form.search) {
        const q = form.search;
        const hit = matchesQuery(r.patient_name, q) || matchesQuery(r.patient_code, q) || matchesQuery(r.phone, q)
            || matchesQuery(r.description, q) || matchesQuery(r.reference_code, q);
        if (!hit) return false;
    }
    if (form.patient_name && !matchesQuery(r.patient_name, form.patient_name)) return false;
    if (form.doctor_id && String(r.doctor_id) !== String(form.doctor_id)) return false;
    if (form.consultant_id && String(r.consultant_id) !== String(form.consultant_id)) return false;
    if (form.assistant_id && String(r.assistant_id) !== String(form.assistant_id)) return false;
    if (form.reference_code && !matchesQuery(r.reference_code, form.reference_code)) return false;
    if (form.amount_min !== '' && r.amount < Number(form.amount_min)) return false;
    if (form.amount_max !== '' && r.amount > Number(form.amount_max)) return false;
    if (form.category_id && String(r.category_id) !== String(form.category_id)) return false;
    if (form.service_id && String(r.service_id) !== String(form.service_id)) return false;
    if (form.source && r.source !== form.source) return false;
    if (form.status && r.status !== form.status) return false;
    return true;
}

const filteredRecords = computed(() => props.records.filter(matchesFilters));

const totals = computed(() => {
    const t = { quantity: 0, discount: 0, gross: 0, service_amount: 0, payment_collected: 0, payment_refunded: 0 };
    for (const r of filteredRecords.value) {
        const quantity = Number(r.quantity) || 0;
        const unitPrice = Number(r.unit_price) || 0;
        t.quantity += quantity;
        t.discount += Number(r.discount) || 0;
        t.gross += quantity * unitPrice;
        if (r.record_type === 'service') t.service_amount += r.amount;
        else if (r.amount > 0) t.payment_collected += r.amount;
        else t.payment_refunded += r.amount;
    }
    return t;
});

// ── Client-side pagination ────────────────────────────────
const currentPage = ref(1);
const totalPages = computed(() => Math.max(1, Math.ceil(filteredRecords.value.length / Number(form.per_page))));
const pagedRecords = computed(() => {
    const perPage = Number(form.per_page);
    const start = (currentPage.value - 1) * perPage;
    return filteredRecords.value.slice(start, start + perPage);
});
const pageNumbers = computed(() => {
    const span = 2;
    const start = Math.max(1, currentPage.value - span);
    const end = Math.min(totalPages.value, currentPage.value + span);
    const pages = [];
    for (let p = start; p <= end; p++) pages.push(p);
    return pages;
});

watch(filteredRecords, () => { currentPage.value = 1; });
watch(() => form.per_page, () => { currentPage.value = 1; });

function goToPage(page) {
    currentPage.value = Math.min(Math.max(page, 1), totalPages.value);
}

// Only these bound the server-side data window — changing them needs a fresh page load.
function reloadFromServer() {
    router.get(route('system-records.index'), {
        branch_id: form.branch_id || undefined,
        year:      form.year      || undefined,
        date_from: form.date_from || undefined,
        date_to:   form.date_to   || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    form.search = ''; form.record_type = '';
    clearAdvancedFilters();

    const hadServerFilters = form.branch_id || form.year || form.date_from || form.date_to;
    form.branch_id = ''; form.year = ''; form.date_from = ''; form.date_to = '';
    if (hadServerFilters) reloadFromServer();
}

function clearAdvancedFilters() {
    form.patient_name = ''; form.doctor_id = ''; form.consultant_id = ''; form.assistant_id = '';
    form.reference_code = ''; form.amount_min = ''; form.amount_max = '';
    form.category_id = ''; form.service_id = ''; form.source = ''; form.status = '';
}

function fmt(val) {
    if (!val) return '—';
    return Number(val).toLocaleString('vi-VN');
}

function formatDate(val) {
    if (!val) return '—';
    const [y, m, d] = val.split('-');
    return `${d}/${m}/${y}`;
}

function referenceUrl(r) {
    return r.reference_type === 'invoice'
        ? route('cashier.invoices.show', r.reference_id)
        : route('clinical.treatment-plans.show', r.reference_id);
}

// ── Export modal ──────────────────────────────────────────────
const showExport = ref(false);
const exporting = ref(false);
const exportProgress = ref(0);
const exportError = ref('');
const exportForm = reactive({ date_from: '', date_to: '' });
let exportProgressTimer = null;

function openExport() {
    exportError.value = '';
    exportForm.date_from = form.date_from || '';
    exportForm.date_to = form.date_to || '';
    showExport.value = true;
}

function closeExport() {
    if (exporting.value) return;
    showExport.value = false;
}

function exportYear(y) {
    if (exporting.value) return;
    exportForm.date_from = `${y}-01-01`;
    exportForm.date_to = `${y}-12-31`;
    confirmExport();
}

function exportFilenameBase() {
    const isFullYear = exportForm.date_from.endsWith('-01-01')
        && exportForm.date_to.endsWith('-12-31')
        && exportForm.date_from.slice(0, 4) === exportForm.date_to.slice(0, 4);

    return isFullYear
        ? `du-lieu-he-thong-nam-${exportForm.date_from.slice(0, 4)}`
        : `du-lieu-he-thong_${exportForm.date_from}_${exportForm.date_to}`;
}

async function confirmExport() {
    if (exporting.value) return;

    if (!exportForm.date_from || !exportForm.date_to) {
        exportError.value = 'Vui lòng chọn đầy đủ từ ngày và đến ngày.';
        return;
    }
    if (exportForm.date_from > exportForm.date_to) {
        exportError.value = 'Từ ngày phải trước hoặc bằng đến ngày.';
        return;
    }

    exportError.value = '';
    exporting.value = true;
    exportProgress.value = 0;

    // The server builds the whole file before sending anything back, so there's no real
    // byte-level signal during that phase — ramp toward 90% while waiting, same pattern
    // as the clinic-records import modal, then jump to 100% once the response arrives.
    exportProgressTimer = setInterval(() => {
        if (exportProgress.value < 90) exportProgress.value += Math.random() * 8;
    }, 300);

    try {
        const response = await axios.get(route('system-records.export', {
            search:          form.search          || undefined,
            record_type:     form.record_type     || undefined,
            branch_id:       form.branch_id       || undefined,
            year:            form.year            || undefined,
            date_from:       exportForm.date_from,
            date_to:         exportForm.date_to,
            patient_name:    form.patient_name    || undefined,
            doctor_id:       form.doctor_id       || undefined,
            consultant_id:   form.consultant_id   || undefined,
            assistant_id:    form.assistant_id    || undefined,
            reference_code:  form.reference_code  || undefined,
            amount_min:      form.amount_min      || undefined,
            amount_max:      form.amount_max      || undefined,
            category_id:     form.category_id     || undefined,
            service_id:      form.service_id      || undefined,
            source:          form.source          || undefined,
            status:          form.status          || undefined,
        }), {
            responseType: 'blob',
            onDownloadProgress(e) {
                if (e.total) exportProgress.value = Math.max(exportProgress.value, Math.round((e.loaded / e.total) * 100));
            },
        });

        clearInterval(exportProgressTimer);
        exportProgress.value = 100;

        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${exportFilenameBase()}.xlsx`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        setTimeout(() => {
            exporting.value = false;
            showExport.value = false;
        }, 400);
    } catch (err) {
        clearInterval(exportProgressTimer);
        exporting.value = false;
        exportProgress.value = 0;

        if (err.response?.data instanceof Blob) {
            const text = await err.response.data.text();
            try {
                const parsed = JSON.parse(text);
                exportError.value = parsed.message ?? Object.values(parsed.errors ?? {}).flat()[0] ?? 'Có lỗi xảy ra khi xuất dữ liệu.';
            } catch {
                exportError.value = 'Có lỗi xảy ra khi xuất dữ liệu.';
            }
        } else {
            exportError.value = err.response?.data?.message ?? 'Có lỗi xảy ra khi xuất dữ liệu.';
        }
    }
}
</script>
