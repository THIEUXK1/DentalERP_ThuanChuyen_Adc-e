export const menuConfig = [
    {
        label: 'TỔNG QUAN',
        items: [
            { label: 'Tổng quan', route: 'dashboard', icon: 'home' }
        ]
    },
    {
        label: 'CRM & KHÁCH HÀNG',
        items: [
            { label: 'Khách hàng', route: 'patients.index', icon: 'users', permission: 'patients.view' },
            { label: 'Khách tiềm năng', route: 'crm.leads.index', icon: 'lead', permission: 'leads.view' },
            { label: 'Công việc Follow-up', route: 'crm.tasks.index', icon: 'follow-up', permission: 'leads.view' },
            { label: 'Mẫu tin nhắn', route: 'crm.messages.templates', icon: 'message-template', permission: 'leads.manage' },
            { label: 'Lịch sử tin nhắn', route: 'crm.messages.log', icon: 'message-log', permission: 'leads.manage' },
            { label: 'Quy tắc CSKH', route: 'crm.care-rules.index', icon: 'care-rules', permission: 'leads.manage' }
        ]
    },
    {
        label: 'LỊCH HẸN',
        items: [
            { label: 'Đăng ký khám', route: 'schedule.registrations.index', icon: 'appointment', permission: 'appointments.view' },
            { label: 'Lịch hẹn', route: 'schedule.appointments.index', icon: 'appointment', permission: 'appointments.view' }
        ]
    },
    {
        label: 'ĐIỀU TRỊ',
        items: [
            { label: 'Kế hoạch điều trị', route: 'clinical.treatment-plans.index', icon: 'treatment-plan', permission: 'treatment_plans.view' },
            { label: 'Mẫu lâm sàng', route: 'clinical.templates.index', icon: 'clinical-template', permission: 'clinical_notes.create' }
        ]
    },
    {
        label: 'NHA KHOA CHUYÊN SÂU',
        items: [
            { label: 'Phiếu khám', route: 'dental.examinations.index', icon: 'examination', permission: 'dental.view' },
            { label: 'Thực hiện công đoạn', route: 'dental.examinations.index', icon: 'workflow-step', permission: 'treatment_plans.edit' },
            { label: 'KPI chuyên môn', route: 'dental.kpi.index', icon: 'kpi', permission: 'dental.kpi.view' },
            { label: 'Danh mục bệnh', route: 'dental.conditions.index', icon: 'conditions', permission: 'dental.manage' }
        ]
    },
    {
        label: 'THU NGÂN',
        items: [
            { label: 'Hóa đơn', route: 'cashier.invoices.index', icon: 'invoice', permission: 'cashier.view' },
            { label: 'Công nợ', route: 'cashier.debts.index', icon: 'debt', permission: 'cashier.view' },
            { label: 'Phiếu chi', route: 'cashier.expenses.index', icon: 'expense', permission: 'expenses.view' }
        ]
    },
    {
        label: 'BÁO CÁO',
        items: [
            { label: 'Doanh thu', route: 'reports.revenue', icon: 'report-revenue', permission: 'reports.financial' },
            { label: 'Lịch hẹn', route: 'reports.appointments', icon: 'report-appointment', permission: 'reports.view' },
            { label: 'Công nợ', route: 'reports.debt', icon: 'report-debt', permission: 'reports.financial' },
            { label: 'CRM & Lead', route: 'reports.crm', icon: 'report-crm', permission: 'reports.view' },
            { label: 'Lãi / Lỗ', route: 'reports.profit-loss', icon: 'report-profit-loss', permission: 'reports.financial' },
            { label: 'Thu / Chi', route: 'reports.cashflow', icon: 'report-cashflow', permission: 'reports.financial' },
            { label: 'Hiệu suất NV', route: 'reports.performance', icon: 'report-performance', permission: 'reports.financial' }
        ]
    },
    {
        label: 'NHÂN SỰ',
        items: [
            { label: 'Nhân viên', route: 'employees.index', icon: 'employee', permission: 'employees.view' },
            { label: 'Hợp đồng LĐ', route: 'hr.contracts.index', icon: 'contract', permission: 'employees.manage' },
            { label: 'Máy chấm công', route: 'hr.attendance-devices.index', icon: 'attendance-device', permission: 'employees.manage' },
            { label: 'Ca làm việc', route: 'hr.work-shifts.index', icon: 'work-shift', permission: 'employees.manage' },
            { label: 'Bảng chấm công', route: 'hr.attendance.index', icon: 'attendance-table', permission: 'employees.manage' },
            { label: 'Chấm công ngày', route: 'hr.timesheets.index', icon: 'timesheet', permission: 'employees.manage' },
            { label: 'Nghỉ phép', route: 'hr.leaves.index', icon: 'leave', permission: 'employees.manage' },
            { label: 'Phiếu lương', route: 'hr.salary-slips.index', icon: 'salary-slip', permission: 'employees.manage' },
            { label: 'Hoa hồng', route: 'hr.commissions.index', icon: 'commission', permission: 'commissions.view' },
            { label: 'Đánh giá NV', route: 'hr.reviews.index', icon: 'review', permission: 'employees.manage' },
            { label: 'KPI nhân viên', route: 'hr.kpis.index', icon: 'kpi-employee', permission: 'employees.manage' }
        ]
    },
    {
        label: 'KẾ TOÁN',
        items: [
            {
                label: 'Quỹ',
                icon: 'expense',
                children: [
                    { label: 'Quản lý quỹ', route: 'core.fund-accounts.index', permission: 'branches.manage' },
                    { label: 'Phiếu thu / chi', route: 'accounting.transactions.index', permission: 'accounting.view' },
                    { label: 'Luân chuyển quỹ', route: 'accounting.fund-transfers.index', permission: 'accounting.view' },
                    { label: 'Tài khoản ngân hàng', route: 'accounting.bank-accounts.index', permission: 'accounting.view' },
                    { label: 'Điều khoản TT', route: 'accounting.payment-terms.index', permission: 'accounting.view' },
                    { label: 'TK nội bộ', route: 'accounting.internal-accounts.index', permission: 'accounting.view' },
                    { label: 'CK nội bộ', route: 'accounting.internal-transfers.index', permission: 'accounting.view' }
                ]
            },
            {
                label: 'Công nợ phải thu (AR)',
                icon: 'debt',
                children: [
                    { label: 'Thu nợ KH (TK 131)', route: 'cashier.debts.collect', permission: 'cashier.manage' },
                    { label: 'Công nợ đầu kỳ', route: 'cashier.debts.opening', permission: 'cashier.view' },
                    { label: 'Công nợ phải thu (AR)', route: 'cashier.debts.index', permission: 'cashier.view' },
                    { label: 'Sổ chi tiết CN phải thu', route: 'reports.debt', permission: 'reports.financial' }
                ]
            },
            {
                label: 'Công nợ phải trả (AP)',
                icon: 'users',
                children: [
                    { label: 'Trả NCC (TK 331)', route: 'accounting.suppliers.index', permission: 'accounting.view' },
                    { label: 'Công nợ đầu kỳ', route: 'accounting.suppliers.opening', permission: 'accounting.view' },
                    { label: 'Công nợ phải trả (AP)', route: 'accounting.purchase-invoices.index', permission: 'accounting.view' },
                    { label: 'Sổ chi tiết CN phải trả', route: 'accounting.suppliers.ledger', permission: 'accounting.view' }
                ]
            },
            {
                label: 'Tiền lương',
                icon: 'invoice',
                children: [
                    { label: 'Bảng lương', route: 'accounting.payrolls.index', permission: 'accounting.view' }
                ]
            },
            {
                label: 'Thuế',
                icon: 'salary-slip',
                children: [
                    { label: 'Kê khai thuế', route: 'reports.tax-filing', permission: 'accounting.view' },
                    { label: 'Báo cáo VAT', route: 'reports.vat', permission: 'accounting.view' }
                ]
            },
            {
                label: 'Chi phí & Giá vốn',
                icon: 'hkd-expense',
                children: [
                    { label: 'Chi phí trả trước', route: 'cashier.expenses.prepaid', permission: 'expenses.view' },
                    { label: 'Chi tiết chi phí', route: 'cashier.expenses.index', permission: 'expenses.view' }
                ]
            },
            {
                label: 'Tài sản cố định',
                icon: 'fixed-asset',
                children: [
                    { label: 'Tài sản cố định', route: 'hr.fixed-assets.index', permission: 'fixed_assets.view' },
                    { label: 'Tính khấu hao', route: 'hr.fixed-assets.depreciate-view', permission: 'fixed_assets.manage' },
                    { label: 'Sổ TSCĐ', route: 'hr.fixed-assets.ledger', permission: 'fixed_assets.view' },
                    { label: 'Báo cáo TSCĐ', route: 'hr.fixed-assets.report', permission: 'fixed_assets.view' }
                ]
            },
            {
                label: 'Kế toán tổng hợp',
                icon: 'general-ledger',
                children: [
                    { label: 'Số dư đầu kỳ (TK)', route: 'accounting.opening-balances', permission: 'accounting.view' },
                    { label: 'Phiếu kế toán', route: 'accounting.journal-vouchers.index', permission: 'accounting.view' },
                    { label: 'Hệ thống tài khoản', route: 'accounting.chart-of-accounts.index', permission: 'accounting.view' },
                    { label: 'Kỳ kế toán', route: 'accounting.periods.index', permission: 'accounting.view' },
                    { label: 'Kết chuyển cuối kỳ', route: 'accounting.period-closing.index', permission: 'accounting.view' },
                    { label: 'Sổ nhật ký chung', route: 'reports.general-journal', permission: 'accounting.view' },
                    { label: 'Sổ chi tiết TK', route: 'reports.general-ledger', permission: 'accounting.view' },
                    { label: 'Bảng kê chứng từ', route: 'reports.vouchers-list', permission: 'accounting.view' },
                    { label: 'Tất cả chứng từ', route: 'reports.all-documents', permission: 'accounting.view' }
                ]
            },
            {
                label: 'Báo cáo',
                icon: 'report-debt',
                children: [
                    { label: 'Lưu chuyển tiền tệ', route: 'reports.cashflow', permission: 'reports.financial' },
                    { label: 'Kết quả HĐKD', route: 'reports.profit-loss', permission: 'reports.financial' },
                    { label: 'Cân đối kế toán', route: 'reports.balance-sheet', permission: 'reports.financial' },
                    { label: 'Cân đối phát sinh', route: 'reports.trial-balance', permission: 'reports.financial' }
                ]
            }
        ]
    },
    {
        label: 'KẾ TOÁN HKD (TT152)',
        condition: 'isHkd',
        items: [
            { label: 'Hồ sơ HKD', route: 'hkd.profile.index', icon: 'hkd-profile', permission: 'hkd.view' },
            { label: 'Doanh thu', route: 'hkd.revenue.index', icon: 'hkd-revenue', permission: 'hkd.view' },
            { label: 'Chi phí', route: 'hkd.expenses.index', icon: 'hkd-expense', permission: 'hkd.view' },
            { label: 'Hàng hóa (S2d)', route: 'hkd.inventory.index', icon: 'hkd-inventory', permission: 'hkd.view' },
            { label: 'Tiền mặt/NH (S2e)', route: 'hkd.cash.index', icon: 'hkd-cash', permission: 'hkd.view' },
            { label: 'Thuế khác (S3a)', route: 'hkd.other-taxes.index', icon: 'hkd-tax', permission: 'hkd.view' },
            { label: 'Chốt kỳ', route: 'hkd.periods.index', icon: 'hkd-period', permission: 'hkd.manage' },
            { label: 'Xuất sổ', route: 'hkd.reports.index', icon: 'hkd-report', permission: 'hkd.view' }
        ]
    },
    {
        label: 'QUẢN LÝ',
        items: [
            { label: 'Chi nhánh', route: 'branches.index', icon: 'branch', permission: 'branches.view' },
            { label: 'Bộ phận', route: 'core.departments.index', icon: 'department', permission: 'branches.manage' },
            { label: 'Nguồn quỹ', route: 'core.fund-accounts.index', icon: 'fund', permission: 'branches.manage' },
            { label: 'Dịch vụ', route: 'catalog.services.index', icon: 'service', permission: 'services.view' },
            { label: 'Bảng giá', route: 'catalog.price-lists.index', icon: 'price-list', permission: 'services.view' },
            { label: 'Ghế nha', route: 'dental-chairs.index', icon: 'dental-chair', roles: ['owner', 'admin'] }
        ]
    },
    {
        label: 'LABO',
        items: [
            { label: 'Danh sách Labo', route: 'lab.labs.index', icon: 'lab', permission: 'labo.view' },
            { label: 'Đơn đặt xưởng', route: 'lab.orders.index', icon: 'lab-order', permission: 'labo.view' },
            { label: 'Bảo hành', route: 'lab.warranties.index', icon: 'warranty', permission: 'labo.view' },
            { label: 'Công nợ Labo', route: 'lab.payables', icon: 'lab-payable', permission: 'labo.view' }
        ]
    },
    {
        label: 'ADMIN',
        items: [
            { label: 'Người dùng', route: 'admin.users.index', icon: 'users', permission: 'admin.users' },
            { label: 'Vai trò', route: 'admin.roles.index', icon: 'role', permission: 'admin.roles' },
            { label: 'Cấu hình', route: 'admin.settings.index', icon: 'settings', permission: 'settings.view' },
            { label: 'Audit Log', route: 'admin.activity-log.index', icon: 'audit-log', permission: 'admin.audit_log' },
            { label: 'Bảng ghi phòng khám', route: 'admin.clinic-records.index', icon: 'audit-log', permission: 'admin.audit_log' }
        ]
    }
];
