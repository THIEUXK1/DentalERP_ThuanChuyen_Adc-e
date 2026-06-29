<template>
    <AppLayout :title="plan ? 'Sửa kế hoạch' : 'Tạo kế hoạch điều trị'">
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link v-if="patientId" :href="route('patients.show', patientId)" class="hover:text-indigo-600 font-medium">← Quay lại hồ sơ bệnh nhân</Link>
                <Link v-else :href="route('clinical.treatment-plans.index')" class="hover:text-indigo-600 font-medium">← Quay lại danh sách kế hoạch</Link>
            </div>

            <!-- Page Title -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Lập kế hoạch điều trị chuyên sâu</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Xây dựng lộ trình chẩn đoán, phác đồ điều trị và dự kiến chi phí</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Left Side: Clinical Inputs (3 columns) -->
                <div class="lg:col-span-3 space-y-4">
                    <!-- 1. Patient & Branch Card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Thông tin hành chính
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormInput label="Khách hàng điều trị" required :error="form.errors.patient_id">
                                <!-- Đã chọn → hiển thị thông tin + nút đổi -->
                                <div v-if="currentPatient" class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0 bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 flex items-center justify-between gap-2">
                                        <span class="truncate">{{ activePatientName }}</span>
                                        <span class="flex-shrink-0 font-mono text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">{{ activePatientCode }}</span>
                                    </div>
                                    <button type="button" @click="clearPatient"
                                        title="Đổi bệnh nhân"
                                        class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg border border-indigo-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        Đổi
                                    </button>
                                </div>
                                <!-- Chưa chọn → combobox tìm kiếm -->
                                <div v-else class="relative" ref="patientComboRef">
                                    <div class="relative">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        <input
                                            v-model="patientSearch"
                                            type="text"
                                            placeholder="Tìm tên hoặc mã bệnh nhân..."
                                            @focus="patientDropdownOpen = true"
                                            @input="patientDropdownOpen = true"
                                            class="block w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                    </div>
                                    <div v-if="patientDropdownOpen && filteredPatients.length > 0"
                                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                        <button v-for="p in filteredPatients" :key="p.id" type="button"
                                            @mousedown.prevent="selectPatient(p)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 flex items-center justify-between gap-2 transition-colors">
                                            <span class="truncate">{{ p.full_name }}</span>
                                            <span class="flex-shrink-0 text-xs font-mono text-gray-400">{{ p.code }}</span>
                                        </button>
                                    </div>
                                    <div v-if="patientDropdownOpen && patientSearch && filteredPatients.length === 0"
                                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-sm text-gray-400">
                                        Không tìm thấy bệnh nhân
                                    </div>
                                </div>
                            </FormInput>

                            <FormInput label="Chi nhánh áp dụng" :error="form.errors.branch_id" required>
                                <select v-model="form.branch_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- Chọn chi nhánh --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </FormInput>
                        </div>
                    </div>

                    <!-- 2. Diagnostics & Priority -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Chẩn đoán & Lý do điều trị
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <FormInput label="Lý do điều trị (Chief Complaint)" :error="form.errors.chief_complaint">
                                    <input v-model="form.chief_complaint" type="text" placeholder="Ví dụ: Đau răng 36, chảy máu nha chu, thẩm mỹ sứ..."
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                                </FormInput>
                            </div>
                            <div>
                                <FormInput label="Mức độ ưu tiên" required>
                                    <select v-model="form.priority" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                        <option value="normal">Bình thường</option>
                                        <option value="urgent">Cần xử lý sớm</option>
                                        <option value="emergency">Cấp cứu / Khẩn cấp</option>
                                    </select>
                                </FormInput>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormInput label="Chẩn đoán tổng quát" :error="form.errors.diagnosis">
                                <input v-model="form.diagnosis" type="text" placeholder="Ví dụ: Viêm tủy R36, Sâu ngà R46..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </FormInput>
                            <FormInput label="Mục tiêu điều trị" :error="form.errors.treatment_goal">
                                <input v-model="form.treatment_goal" type="text" placeholder="Ví dụ: Loại bỏ ổ viêm tủy, phục hồi chức năng ăn nhai..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </FormInput>
                        </div>
                    </div>

                    <!-- 3. Phác đồ chi tiết (Treatment Items) -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                Phác đồ & Hạng mục điều trị chi tiết
                            </h3>
                            <button type="button" @click="addItemRow"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-semibold shadow-sm transition-colors border border-indigo-200">
                                ➕ Thêm dịch vụ
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-500">
                                        <th class="p-2.5 border-r font-semibold w-16">Răng/Vùng</th>
                                        <th class="p-2.5 border-r font-semibold w-56">Dịch vụ điều trị *</th>
                                        <th class="p-2.5 border-r font-semibold w-40">Chẩn đoán riêng</th>
                                        <th class="p-2.5 border-r font-semibold w-12 text-center">SL</th>
                                        <th class="p-2.5 border-r font-semibold w-24 text-right">Đơn giá (₫)</th>
                                        <th class="p-2.5 border-r font-semibold w-20 text-right">Giảm (₫)</th>
                                        <th class="p-2.5 border-r font-semibold w-28 text-right">Thành tiền (₫)</th>
                                        <th class="p-2.5 border-r font-semibold w-40">Giai đoạn điều trị</th>
                                        <th class="p-2.5 text-center w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-if="form.items.length === 0">
                                        <td colspan="9" class="p-8 text-center text-gray-400">
                                            Chưa có dịch vụ nào trong phác đồ. Hãy chọn "Thêm dịch vụ" hoặc áp dụng "Phác đồ mẫu" ở cột bên phải.
                                        </td>
                                    </tr>
                                    <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-gray-50/40">
                                        <!-- Răng -->
                                        <td class="p-1.5 border-r">
                                            <input v-model="item.tooth_number" placeholder="Ví dụ: R36"
                                                class="w-full text-xs border border-gray-300 rounded px-1.5 py-1 text-center font-mono focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                                        </td>
                                        <!-- Dịch vụ -->
                                        <td class="p-1.5 border-r">
                                            <select v-model="item.service_id" @change="onItemServiceChange(idx)"
                                                class="w-full text-xs border border-gray-300 rounded px-1 py-1 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                                <option value="" disabled>-- Chọn dịch vụ --</option>
                                                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                                            </select>
                                        </td>
                                        <!-- Chẩn đoán riêng -->
                                        <td class="p-1.5 border-r">
                                            <input v-model="item.diagnosis" placeholder="Chẩn đoán răng này..."
                                                class="w-full text-xs border border-gray-300 rounded px-1.5 py-1 focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                                        </td>
                                        <!-- SL -->
                                        <td class="p-1.5 border-r">
                                            <input v-model.number="item.quantity" type="number" min="1" @input="recalculateItemRow(idx)"
                                                class="w-full text-xs border border-gray-300 rounded px-1 py-1 text-center focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                                        </td>
                                        <!-- Đơn giá -->
                                        <td class="p-1.5 border-r">
                                            <input v-model.number="item.unit_price" type="number" min="0" @input="recalculateItemRow(idx)"
                                                class="w-full text-xs border border-gray-300 rounded px-1 py-1 text-right focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                                        </td>
                                        <!-- Giảm giá -->
                                        <td class="p-1.5 border-r">
                                            <input v-model.number="item.discount" type="number" min="0" @input="recalculateItemRow(idx)"
                                                class="w-full text-xs border border-gray-300 rounded px-1 py-1 text-right focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                                        </td>
                                        <!-- Thành tiền -->
                                        <td class="p-1.5 border-r text-right font-semibold text-gray-700 font-mono">
                                            {{ fmt(item.amount) }}
                                        </td>
                                        <!-- Giai đoạn -->
                                        <td class="p-1.5 border-r">
                                            <select v-model="item.stage_name"
                                                class="w-full text-xs border border-gray-300 rounded px-1 py-1 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                                <option value="Giai đoạn 1: Khám, chụp phim, tư vấn">1. Khám & Lập kế hoạch</option>
                                                <option value="Giai đoạn 2: Điều trị chính">2. Điều trị chính</option>
                                                <option value="Giai đoạn 3: Phục hình / thẩm mỹ">3. Phục hình / Thẩm mỹ</option>
                                                <option value="Giai đoạn 4: Tái khám / bảo hành">4. Tái khám & Bảo dưỡng</option>
                                            </select>
                                        </td>
                                        <!-- Remove button -->
                                        <td class="p-1.5 text-center">
                                            <button type="button" @click="removeItemRow(idx)" class="text-gray-400 hover:text-red-600 transition-colors">
                                                <svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. Duration & Appointment Schedules -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex-shrink-0 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Thời gian & Tần suất điều trị dự kiến
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <FormInput label="Ngày bắt đầu dự kiến" :error="form.errors.start_date" required>
                                <input v-model="form.start_date" type="date"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </FormInput>

                            <FormInput label="Dự kiến hoàn thành" :error="form.errors.expected_end_date">
                                <input v-model="form.expected_end_date" type="date"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </FormInput>

                            <FormInput label="Số buổi dự kiến" :error="form.errors.estimated_sessions" required>
                                <input v-model.number="form.estimated_sessions" type="number" min="1"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </FormInput>

                            <FormInput label="Tần suất tái khám" :error="form.errors.frequency">
                                <input v-model="form.frequency" type="text" placeholder="Ví dụ: 3-5 ngày/lần"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                            </FormInput>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Plan Settings, Templates, & Action Bar (1 column) -->
                <div class="space-y-4">
                    <!-- A. Templates Library -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Thư viện phác đồ mẫu
                        </h3>
                        <p class="text-xs text-gray-400">Chọn phác đồ mẫu để tự động điền nhanh các công đoạn chẩn đoán & dịch vụ.</p>
                        <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <button v-for="t in templates" :key="t.name" type="button" @click="applyTemplate(t)"
                                class="w-full text-left p-2 hover:bg-emerald-50 hover:text-emerald-800 border border-gray-100 rounded-lg text-xs transition-colors flex items-center justify-between font-medium text-gray-700">
                                <span>{{ t.name }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- B. Responsibility Settings -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Nhân sự phụ trách
                        </h3>
                        <div class="space-y-3">
                            <FormInput label="Bác sĩ điều trị chính" :error="form.errors.doctor_id">
                                <select v-model="form.doctor_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn bác sĩ --</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </FormInput>

                            <FormInput label="Tư vấn viên (CSKH)" :error="form.errors.consultant_id">
                                <select v-model="form.consultant_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option :value="null">-- Chọn tư vấn viên --</option>
                                    <option v-for="c in consultants" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </FormInput>
                        </div>
                    </div>

                    <!-- C. Costs Summary & Save actions -->
                    <div class="bg-slate-900 rounded-xl p-4 shadow-sm text-white space-y-4">
                        <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Tổng hợp chi phí</h3>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tổng chi phí dự kiến:</span>
                                <span class="font-semibold font-mono">{{ fmt(form.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tổng giảm giá:</span>
                                <span class="font-semibold font-mono text-rose-400">-{{ fmt(form.discount_amount) }}</span>
                            </div>
                            <div class="border-t border-slate-700 my-2 pt-2 flex justify-between text-base">
                                <span class="font-bold text-slate-200">Thực thu (Sau giảm):</span>
                                <span class="font-bold font-mono text-emerald-400">{{ fmt(netTotal) }}</span>
                            </div>
                        </div>

                        <!-- Professional Notes -->
                        <div class="border-t border-slate-700 pt-3">
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase">Ghi chú chuyên môn</label>
                            <textarea v-model="form.notes" rows="2" placeholder="Lưu ý lâm sàng, tiền sử dị ứng..."
                                class="w-full bg-slate-800 border border-slate-700 text-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
                        </div>

                        <!-- Action Submit Buttons -->
                        <div class="space-y-2 pt-2 border-t border-slate-700">
                            <!-- Lưu nháp -->
                            <button type="button" @click="submitPlan('draft', 'show')" :disabled="form.processing"
                                class="w-full py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs font-semibold text-slate-300 transition-colors">
                                Lưu nháp Kế hoạch
                            </button>

                            <!-- Lưu & Tạo lịch hẹn -->
                            <button type="button" @click="submitPlan('draft', 'appointment')" :disabled="form.processing"
                                class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-xs font-semibold text-white transition-colors">
                                Lưu & Đặt lịch hẹn
                            </button>

                            <!-- Lưu & Ký phiếu đồng ý -->
                            <button type="button" @click="submitPlan('draft', 'consent')" :disabled="form.processing"
                                class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-xs font-semibold text-white transition-colors">
                                Lưu & Tạo phiếu đồng ý
                            </button>

                            <!-- Lưu & Thanh toán -->
                            <button type="button" @click="submitPlan('approved', 'payment')" :disabled="form.processing"
                                class="w-full py-2 bg-amber-500 hover:bg-amber-600 rounded-lg text-xs font-semibold text-slate-950 transition-colors">
                                Lưu & Chuyển sang thanh toán
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, watch, ref, onMounted, onUnmounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import FormInput from '@/Components/Shared/FormInput.vue';

const props = defineProps({
    plan: Object,
    patients: Array,
    doctors: Array,
    consultants: Array,
    branches: Array,
    services: Array,
    selected_patient_id: Number,
    selected_branch_id: Number
});

// Resolve current patient — reactive to form.patient_id so dropdown selection updates display
const currentPatient = computed(() => {
    const pId = form.patient_id || props.plan?.patient_id || props.selected_patient_id;
    return pId ? (props.patients.find(p => p.id == pId) || null) : null;
});

const activePatientName = computed(() => currentPatient.value ? currentPatient.value.full_name : 'Bệnh nhân chưa chọn');
const activePatientCode = computed(() => currentPatient.value ? currentPatient.value.code : 'BN-XXXX');
const patientId = computed(() => currentPatient.value ? currentPatient.value.id : null);

// Patient searchable combobox
const patientSearch = ref('');
const patientDropdownOpen = ref(false);
const patientComboRef = ref(null);

const filteredPatients = computed(() => {
    const q = patientSearch.value.toLowerCase().trim();
    if (!q) return props.patients.slice(0, 20);
    return props.patients.filter(p =>
        p.full_name.toLowerCase().includes(q) ||
        p.code.toLowerCase().includes(q) ||
        (p.phone && p.phone.includes(q))
    ).slice(0, 30);
});

function selectPatient(p) {
    form.patient_id = p.id;
    patientSearch.value = '';
    patientDropdownOpen.value = false;
}

function clearPatient() {
    form.patient_id = '';
    patientSearch.value = '';
    patientDropdownOpen.value = false;
}

function handlePatientOutsideClick(e) {
    if (patientComboRef.value && !patientComboRef.value.contains(e.target)) {
        patientDropdownOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handlePatientOutsideClick));
onUnmounted(() => document.removeEventListener('click', handlePatientOutsideClick));

// Initial form state
const form = useForm({
    patient_id:         props.plan?.patient_id ?? props.selected_patient_id ?? '',
    branch_id:          props.plan?.branch_id ?? props.selected_branch_id ?? '',
    doctor_id:          props.plan?.doctor_id ?? null,
    consultant_id:      props.plan?.consultant_id ?? null,
    appointment_id:     props.plan?.appointment_id ?? null,
    notes:              props.plan?.notes ?? '',
    diagnosis:          props.plan?.diagnosis ?? '',
    chief_complaint:    props.plan?.chief_complaint ?? '',
    treatment_goal:     props.plan?.treatment_goal ?? '',
    start_date:         props.plan?.start_date ?? new Date().toISOString().split('T')[0],
    expected_end_date:  props.plan?.expected_end_date ?? '',
    estimated_sessions: props.plan?.estimated_sessions ?? 1,
    frequency:          props.plan?.frequency ?? '3-5 ngày/lần',
    priority:           props.plan?.priority ?? 'normal',
    status:             props.plan?.status ?? 'draft',
    total_amount:       props.plan?.total_amount ?? 0,
    discount_amount:    props.plan?.discount_amount ?? 0,
    action:             'show',
    items:              props.plan?.items ?? []
});

// Auto-fill branch when patient is selected from dropdown
watch(() => form.patient_id, (pId) => {
    if (!pId || form.branch_id) return;
    const p = props.patients.find(x => x.id == pId);
    if (p?.branch_id) form.branch_id = p.branch_id;
});

// Live net total calculation
const netTotal = computed(() => Math.max(0, form.total_amount - form.discount_amount));

// Currency formatter
function fmt(val) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val ?? 0);
}

// Add a blank service row to items table
function addItemRow() {
    form.items.push({
        service_id: props.services[0]?.id || '',
        tooth_number: '',
        diagnosis: '',
        quantity: 1,
        unit_price: props.services[0]?.selling_price || 0,
        discount: 0,
        amount: props.services[0]?.selling_price || 0,
        estimated_sessions: 1,
        stage_name: 'Giai đoạn 2: Điều trị chính',
        notes: ''
    });
    recalculateTotals();
}

// Remove item row
function removeItemRow(idx) {
    form.items.splice(idx, 1);
    recalculateTotals();
}

// Handle service change to pre-fill unit price
function onItemServiceChange(idx) {
    const item = form.items[idx];
    const service = props.services.find(s => s.id === item.service_id);
    if (service) {
        item.unit_price = service.selling_price;
    }
    recalculateItemRow(idx);
}

// Recalculate component row amount
function recalculateItemRow(idx) {
    const item = form.items[idx];
    item.quantity = Math.max(1, item.quantity || 1);
    item.unit_price = Math.max(0, item.unit_price || 0);
    item.discount = Math.max(0, item.discount || 0);
    item.amount = (item.quantity * item.unit_price) - item.discount;
    recalculateTotals();
}

// Recalculate summary totals
function recalculateTotals() {
    let total = 0;
    let discount = 0;
    form.items.forEach(item => {
        total += (item.quantity * item.unit_price);
        discount += item.discount;
    });
    form.total_amount = total;
    form.discount_amount = discount;
}

// Static Template Library
const templates = [
    {
        name: 'Phác đồ điều trị tủy (Endodontics)',
        diagnosis: 'Viêm tủy cấp / mạn tính',
        estimated_sessions: 3,
        frequency: '3-5 ngày/lần',
        items: [
            { service_name: 'Điều trị tủy', tooth_number: '', diagnosis: 'Viêm tủy răng', quantity: 1, unit_price: 2000000, discount: 0, amount: 2000000, estimated_sessions: 2, stage_name: 'Giai đoạn 2: Điều trị chính', notes: 'Gây tê và làm sạch ống tủy' },
            { service_name: 'Trám răng composite', tooth_number: '', diagnosis: 'Sau điều trị tủy', quantity: 1, unit_price: 500000, discount: 0, amount: 500000, estimated_sessions: 1, stage_name: 'Giai đoạn 3: Phục hình / thẩm mỹ', notes: 'Trám phục hồi' }
        ]
    },
    {
        name: 'Phác đồ bọc răng sứ (Porcelain Crown)',
        diagnosis: 'Răng mẻ lớn / Răng sau chữa tủy',
        estimated_sessions: 2,
        frequency: '3-5 ngày/lần',
        items: [
            { service_name: 'Cùi giả / chốt tái tạo', tooth_number: '', diagnosis: 'Răng sau điều trị tủy', quantity: 1, unit_price: 1000000, discount: 0, amount: 1000000, estimated_sessions: 1, stage_name: 'Giai đoạn 2: Điều trị chính', notes: 'Đặt chốt gia cố' },
            { service_name: 'Bọc răng sứ', tooth_number: '', diagnosis: 'Phục hình răng sứ', quantity: 1, unit_price: 4000000, discount: 0, amount: 4000000, estimated_sessions: 1, stage_name: 'Giai đoạn 3: Phục hình / thẩm mỹ', notes: 'Mài cùi, lấy dấu và lắp sứ' }
        ]
    },
    {
        name: 'Phác đồ cấy Implant (Implant)',
        diagnosis: 'Mất răng đơn lẻ',
        estimated_sessions: 5,
        frequency: '1-3 tháng/lần',
        items: [
            { service_name: 'Cấy trụ Implant', tooth_number: '', diagnosis: 'Mất răng', quantity: 1, unit_price: 15000000, discount: 1000000, amount: 14000000, estimated_sessions: 1, stage_name: 'Giai đoạn 2: Điều trị chính', notes: 'Phẫu thuật cấy trụ' },
            { service_name: 'Răng sứ trên Implant', tooth_number: '', diagnosis: 'Lắp răng sứ phục hình', quantity: 1, unit_price: 5000000, discount: 0, amount: 5000000, estimated_sessions: 2, stage_name: 'Giai đoạn 3: Phục hình / thẩm mỹ', notes: 'Lấy dấu và gắn răng sứ hoàn thiện' }
        ]
    },
    {
        name: 'Phác đồ niềng răng (Orthodontics)',
        diagnosis: 'Răng khấp khểnh, hô, móm',
        estimated_sessions: 24,
        frequency: '3-4 tuần/lần',
        items: [
            { service_name: 'Chụp phim & lên kế hoạch niềng', tooth_number: '', diagnosis: 'Khảo sát khớp cắn', quantity: 1, unit_price: 1500000, discount: 500000, amount: 1000000, estimated_sessions: 1, stage_name: 'Giai đoạn 1: Khám, chụp phim, tư vấn', notes: 'Quét dấu răng và chụp CT Conebeam' },
            { service_name: 'Gắn mắc cài kim loại', tooth_number: '', diagnosis: 'Chỉnh nha mắc cài', quantity: 1, unit_price: 30000000, discount: 3000000, amount: 27000000, estimated_sessions: 1, stage_name: 'Giai đoạn 2: Điều trị chính', notes: 'Lắp mắc cài hai hàm' }
        ]
    },
    {
        name: 'Phác đồ nhổ răng khôn (Wisdom Tooth)',
        diagnosis: 'Răng khôn mọc lệch, ngầm',
        estimated_sessions: 1,
        frequency: '7 ngày/lần tái khám',
        items: [
            { service_name: 'Nhổ răng khôn', tooth_number: '', diagnosis: 'Răng khôn mọc lệch biến chứng', quantity: 1, unit_price: 2500000, discount: 200000, amount: 2300000, estimated_sessions: 1, stage_name: 'Giai đoạn 2: Điều trị chính', notes: 'Tiểu phẫu nhổ răng khôn' }
        ]
    }
];

// Apply template configuration to form
function applyTemplate(tpl) {
    form.diagnosis = tpl.diagnosis;
    form.estimated_sessions = tpl.estimated_sessions;
    form.frequency = tpl.frequency;
    
    // Convert template items to matches based on props.services names
    form.items = tpl.items.map(item => {
        const matched = props.services.find(s => 
            s.name.toLowerCase().includes(item.service_name.toLowerCase()) || 
            item.service_name.toLowerCase().includes(s.name.toLowerCase())
        );
        const unit_price = matched ? matched.selling_price : item.unit_price;
        const discount = item.discount;
        return {
            service_id: matched ? matched.id : (props.services[0]?.id || ''),
            tooth_number: item.tooth_number,
            diagnosis: item.diagnosis,
            quantity: item.quantity,
            unit_price: unit_price,
            discount: discount,
            amount: (unit_price * item.quantity) - discount,
            estimated_sessions: item.estimated_sessions,
            stage_name: item.stage_name,
            notes: item.notes
        };
    });
    
    recalculateTotals();
}

// Submit with dynamic action redirects
function submitPlan(statusVal, actionVal) {
    form.status = statusVal;
    form.action = actionVal;
    
    if (props.plan) {
        form.put(route('clinical.treatment-plans.update', props.plan.id));
    } else {
        form.post(route('clinical.treatment-plans.store'));
    }
}
</script>
