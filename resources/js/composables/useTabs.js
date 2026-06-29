import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { restorePage } from './usePageCache';

const STORAGE_KEY = 'dental_tabs';
const MAX_TABS    = 10;

// Sorted by descending length at import time so more-specific paths match first
const ROUTE_TITLES = [
    ['/hr/fixed-assets',                      'Tài sản CĐ'],
    ['/hr/salary-slips',                      'Phiếu lương'],
    ['/hr/kpis',                              'KPI nhân viên'],
    ['/hr/attendance-devices',                'Máy chấm công'],
    ['/hr/attendance',                        'Bảng chấm công'],
    ['/hr/work-shifts',                       'Ca làm việc'],
    ['/hr/timesheets',                        'Chấm công ngày'],
    ['/hr/leaves',                            'Nghỉ phép'],
    ['/hr/contracts',                         'HĐ lao động'],
    ['/hr/reviews',                           'Đánh giá NV'],
    ['/dental/kpi',                            'KPI chuyên môn'],
    ['/dental/examinations',                   'Phiếu khám'],
    ['/dental/conditions',                     'Danh mục bệnh'],
    ['/dental/services',                       'Công đoạn DV'],
    ['/dental/treatment-items',                'Thực hiện ĐT'],
    ['/hkd/reports',                           'Xuất sổ HKD'],
    ['/hkd/periods',                          'Chốt kỳ HKD'],
    ['/hkd/other-taxes',                      'Thuế khác HKD'],
    ['/hkd/inventory',                        'Hàng hóa HKD'],
    ['/hkd/expenses',                         'Chi phí HKD'],
    ['/hkd/revenue',                          'Doanh thu HKD'],
    ['/hkd/profile',                          'Hồ sơ HKD'],
    ['/hkd/cash',                             'Tiền HKD'],
    ['/accounting/payrolls',                  'Bảng lương'],
    ['/accounting/purchase-invoices',         'HĐ mua hàng'],
    ['/accounting/fund-transfers',            'Chuyển quỹ'],
    ['/accounting/suppliers',                 'Nhà cung cấp'],
    ['/core/fund-accounts',                   'Nguồn quỹ'],
    ['/core/departments',                     'Bộ phận'],
    ['/reports/general-ledger',               'Sổ cái'],
    ['/reports/cashflow',                     'BC Thu/Chi'],
    ['/reports/performance',                  'BC Hiệu suất'],
    ['/reports/vat',                          'BC Thuế VAT'],
    ['/lab/payables',                         'CN Labo'],
    ['/hr/commissions/rules',                 'Quy tắc HH'],
    ['/hr/commissions',                       'Hoa hồng'],
    ['/lab/orders',                           'Đơn đặt xưởng'],
    ['/lab/warranties',                       'Bảo hành labo'],
    ['/lab/labs',                             'Labo'],
    ['/crm/messages/log',                     'Lịch sử tin nhắn'],
    ['/crm/messages/templates',               'Mẫu tin nhắn'],
    ['/crm/care-rules',                       'Quy tắc CSKH'],
    ['/crm/leads',                            'Lead'],
    ['/crm/tasks',                            'Follow-up'],
    ['/schedule/appointments',                'Lịch hẹn'],
    ['/clinical/templates',                   'Mẫu lâm sàng'],
    ['/clinical/treatment-plans',             'Kế hoạch ĐT'],
    ['/cashier/invoices',                     'Hóa đơn'],
    ['/cashier/debts',                        'Công nợ'],
    ['/cashier/expenses',                     'Phiếu chi'],
    ['/reports/revenue',                      'BC Doanh thu'],
    ['/reports/appointments',                 'BC Lịch hẹn'],
    ['/reports/debt',                         'BC Công nợ'],
    ['/reports/crm',                          'BC CRM'],
    ['/reports/profit-loss',                  'Lãi / Lỗ'],
    ['/catalog/price-lists',                  'Bảng giá'],
    ['/catalog/services',                     'Dịch vụ'],
    ['/admin/activity-log',                   'Audit Log'],
    ['/admin/users',                          'Người dùng'],
    ['/admin/roles',                          'Vai trò'],
    ['/admin/settings',                       'Cấu hình'],
    ['/dental-chairs',                        'Ghế nha'],
    ['/employees',                            'Nhân viên'],
    ['/branches',                             'Chi nhánh'],
    ['/patients',                             'Khách hàng'],
    ['/notifications',                        'Thông báo'],
    ['/profile',                              'Hồ sơ'],
    ['/dashboard',                            'Dashboard'],
].sort((a, b) => b[0].length - a[0].length);

function titleFor(url) {
    const path = new URL(url, 'http://x').pathname;
    for (const [prefix, title] of ROUTE_TITLES) {
        if (path.startsWith(prefix)) {
            // Suffix "/ Chi tiết" for numeric-ID sub-pages (e.g. /patients/5)
            const rest = path.slice(prefix.length).replace(/^\//, '');
            if (rest && /^\d+/.test(rest)) return `${title} · Chi tiết`;
            return title;
        }
    }
    return path.split('/').filter(Boolean).slice(-1)[0] ?? 'Trang';
}

function load() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]'); } catch { return []; }
}

function save(tabs) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(tabs));
}

// ── Singleton state — created once at module level, never duplicated ──────────
const tabs = ref(load());

function openTab(url) {
    // Deduplicate by pathname; update stored URL when only query-string changed
    const pathname = new URL(url, 'http://x').pathname;
    const idx = tabs.value.findIndex(t => new URL(t.url, 'http://x').pathname === pathname);

    if (idx !== -1) {
        tabs.value = tabs.value.map((t, i) => ({
            ...t,
            url: i === idx ? url : t.url,
            active: i === idx,
        }));
    } else {
        tabs.value = [
            ...tabs.value.map(t => ({ ...t, active: false })),
            { url, title: titleFor(url), active: true },
        ].slice(-MAX_TABS);
    }
    save(tabs.value);
}

function closeTab(url) {
    const pathname = new URL(url, 'http://x').pathname;
    const idx = tabs.value.findIndex(t => new URL(t.url, 'http://x').pathname === pathname);
    if (idx === -1) return;
    const wasActive = tabs.value[idx].active;
    tabs.value.splice(idx, 1);
    if (wasActive && tabs.value.length) {
        const next = tabs.value[Math.max(0, idx - 1)];
        next.active = true;
        if (!restorePage(next.url)) router.visit(next.url);
    }
    save(tabs.value);
}

function pinTab(url) {
    const pathname = new URL(url, 'http://x').pathname;
    tabs.value = tabs.value.map(t => ({
        ...t,
        pinned: new URL(t.url, 'http://x').pathname === pathname ? !t.pinned : t.pinned,
    }));
    save(tabs.value);
}

function closeAllTabs() {
    // Giữ lại: tab đang ghim + tab đang active (đang hiển thị)
    tabs.value = tabs.value.filter(t => t.pinned || t.active);
    save(tabs.value);
}

// Single listener — registered once at module scope, never re-registered
router.on('navigate', e => openTab(e.detail.page.url));

export function useTabs() {
    return { tabs, closeTab, pinTab, closeAllTabs };
}
