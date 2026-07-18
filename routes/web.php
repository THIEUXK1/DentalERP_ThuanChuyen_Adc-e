<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ClinicRecordController;
use App\Http\Controllers\Admin\DataRestoreController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Cashier\DebtController;
use App\Http\Controllers\Cashier\ExpenseController;
use App\Http\Controllers\Cashier\PatientInvoiceController;
use App\Http\Controllers\Cashier\PatientPaymentController;
use App\Http\Controllers\Catalog\DentalServiceController;
use App\Http\Controllers\Catalog\PriceListController;
use App\Http\Controllers\Catalog\ServiceCategoryController;
use App\Http\Controllers\Catalog\ServiceGroupController;
use App\Http\Controllers\Clinical\ClinicalNoteController;
use App\Http\Controllers\Clinical\ClinicalTemplateController;
use App\Http\Controllers\Clinical\ToothConditionController;
use App\Http\Controllers\Clinical\TreatmentPlanController;
use App\Http\Controllers\Clinical\TreatmentPlanItemController;
use App\Http\Controllers\Accounting\FundTransferController;
use App\Http\Controllers\Accounting\InventoryController;
use App\Http\Controllers\Accounting\PayrollController;
use App\Http\Controllers\Accounting\PayrollItemController;
use App\Http\Controllers\Accounting\PurchaseInvoiceController;
use App\Http\Controllers\Accounting\SupplierController;
use App\Http\Controllers\Dental\ConditionController;
use App\Http\Controllers\Dental\ExaminationController;
use App\Http\Controllers\Dental\KpiAllocationController;
use App\Http\Controllers\Dental\ServiceStepController;
use App\Http\Controllers\Dental\TreatmentExecutionController;
use App\Http\Controllers\Hkd\HkdCashController;
use App\Http\Controllers\Hkd\HkdExpenseController;
use App\Http\Controllers\Hkd\HkdInventoryController;
use App\Http\Controllers\Hkd\HkdOtherTaxController;
use App\Http\Controllers\Hkd\HkdPeriodCloseController;
use App\Http\Controllers\Hkd\HkdProfileController;
use App\Http\Controllers\Hkd\HkdReportController;
use App\Http\Controllers\Hkd\HkdRevenueController;
use App\Http\Controllers\Core\BranchController;
use App\Http\Controllers\Core\DentalChairController;
use App\Http\Controllers\Core\DepartmentController;
use App\Http\Controllers\Core\FundAccountController;
use App\Http\Controllers\Crm\ConsentFormController;
use App\Http\Controllers\Crm\PatientAttachmentController;
use App\Http\Controllers\Crm\PatientPhoneController;
use App\Http\Controllers\Crm\PatientRelationshipController;
use App\Http\Controllers\Hr\EmployeeContractController;
use App\Http\Controllers\Hr\KpiController;
use App\Http\Controllers\Hr\LeaveController;
use App\Http\Controllers\Hr\PerformanceReviewController;
use App\Http\Controllers\Hr\TimesheetController;
use App\Http\Controllers\Hr\WorkShiftController;
use App\Http\Controllers\Crm\ContactActivityController;
use App\Http\Controllers\Crm\FollowUpTaskController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\PatientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemRecordController;
use App\Http\Controllers\Crm\CareRuleController;
use App\Http\Controllers\Crm\MessageController;
use App\Http\Controllers\Hr\CommissionController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\FixedAssetController;
use App\Http\Controllers\Hr\AttendanceDeviceController;
use App\Http\Controllers\Hr\AttendancePeriodController;
use App\Http\Controllers\Hr\AttendanceRecordController;
use App\Http\Controllers\Hr\SalarySlipController;
use App\Http\Controllers\Lab\LabController;
use App\Http\Controllers\Lab\LabOrderController;
use App\Http\Controllers\Lab\LabWarrantyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendingDeletionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Schedule\AppointmentController;
use App\Http\Controllers\Schedule\RegistrationController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', fn () => redirect()->route('login'));

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::get('/dashboard/export/pdf', [DashboardController::class, 'exportPdf'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.export.pdf');
Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.export.excel');
Route::get('/system-records', [SystemRecordController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:reports.financial'])
    ->name('system-records.index');
Route::get('/system-records/export', [SystemRecordController::class, 'exportExcel'])
    ->middleware(['auth', 'verified', 'can:reports.financial'])
    ->name('system-records.export');

// Reports
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue')->middleware('can:reports.financial');
    Route::get('appointments', [ReportController::class, 'appointments'])->name('appointments')->middleware('can:reports.view');
    Route::get('daily-schedule', [ReportController::class, 'dailySchedule'])->name('daily-schedule')->middleware('can:reports.view');
    Route::get('debt', [ReportController::class, 'debt'])->name('debt')->middleware('can:reports.financial');
    Route::get('crm', [ReportController::class, 'crm'])->name('crm')->middleware('can:reports.view');
    Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss')->middleware('can:reports.financial');
    Route::get('cashflow', [ReportController::class, 'cashflow'])->name('cashflow')->middleware('can:reports.financial');
    Route::get('performance', [ReportController::class, 'performance'])->name('performance')->middleware('can:reports.financial');
    Route::get('vat', [ReportController::class, 'vatReport'])->name('vat')->middleware('can:accounting.view');
    Route::get('general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger')->middleware('can:accounting.view');
    Route::get('reconciliation', [ReportController::class, 'reconciliation'])->name('reconciliation')->middleware('can:reports.financial');
    Route::get('kpi-summary', [ReportController::class, 'kpiSummary'])->name('kpi-summary')->middleware('can:reports.financial');
});

// Inventory (Kho vật tư)
Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index')->middleware('can:inventory.view');
    Route::get('/create', [InventoryController::class, 'create'])->name('create')->middleware('can:inventory.manage');
    Route::post('/', [InventoryController::class, 'store'])->name('store')->middleware('can:inventory.manage');
    Route::get('/{inventory}', [InventoryController::class, 'show'])->name('show')->middleware('can:inventory.view');
    Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update')->middleware('can:inventory.manage');
    Route::post('/{inventory}/add-stock', [InventoryController::class, 'addStock'])->name('add-stock')->middleware('can:inventory.manage');
    Route::post('/{inventory}/adjust', [InventoryController::class, 'adjust'])->name('adjust')->middleware('can:inventory.manage');
    Route::get('/service-templates/list', [InventoryController::class, 'templates'])->name('templates')->middleware('can:inventory.manage');
    Route::post('/service-templates', [InventoryController::class, 'storeTemplate'])->name('templates.store')->middleware('can:inventory.manage');
    Route::delete('/service-templates/{template}', [InventoryController::class, 'destroyTemplate'])->name('templates.destroy')->middleware('can:inventory.manage');
});

// Profile (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::delete('pending-deletions/{pendingDeletion}/undo', [PendingDeletionController::class, 'undo'])
        ->name('pending-deletions.undo');
});

// Admin
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->middleware('can:admin.users');
    Route::resource('roles', RoleController::class)->only(['index', 'show'])->middleware('can:admin.roles');
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index')->middleware('can:admin.audit_log');
    Route::get('data-restore', [DataRestoreController::class, 'index'])->name('data-restore.index')->middleware('can:admin.audit_log');
    Route::post('data-restore/{activity}/restore', [DataRestoreController::class, 'restore'])->name('data-restore.restore')->middleware('can:admin.audit_log');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index')->middleware('can:settings.view');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update')->middleware('can:settings.manage');
    Route::get('clinic-records', [ClinicRecordController::class, 'index'])->name('clinic-records.index')->middleware('can:admin.audit_log');
    Route::get('clinic-records/template', [ClinicRecordController::class, 'downloadTemplate'])->name('clinic-records.template')->middleware('can:admin.audit_log');
    Route::post('clinic-records/preview', [ClinicRecordController::class, 'previewImport'])->name('clinic-records.preview')->middleware('can:admin.audit_log');
    Route::post('clinic-records/import', [ClinicRecordController::class, 'import'])->name('clinic-records.import')->middleware('can:admin.audit_log');
    Route::post('clinic-records/import-chunk', [ClinicRecordController::class, 'importChunk'])->name('clinic-records.import-chunk')->middleware('can:admin.audit_log');
    Route::delete('clinic-records/bulk-delete', [ClinicRecordController::class, 'bulkDelete'])->name('clinic-records.bulk-delete')->middleware('can:admin.audit_log');
});

// Notifications
Route::middleware(['auth'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// Patients + clinical sub-resources
Route::middleware(['auth'])->group(function () {
    Route::post('patients/check-duplicate', [PatientController::class, 'checkDuplicate'])
        ->name('patients.check-duplicate')->middleware('can:patients.create');
    Route::get('patients/data', [PatientController::class, 'data'])
        ->name('patients.data')->middleware('can:patients.view');
    Route::get('patients/lite-list', [PatientController::class, 'liteList'])
        ->name('patients.lite-list')->middleware('can:patients.view');
    Route::get('patients/{patient}/edit-json', [PatientController::class, 'editJson'])
        ->name('patients.edit-json')->middleware('can:patients.edit');
    Route::get('patients/{patient}/data', [PatientController::class, 'showData'])
        ->name('patients.show-data')->middleware('can:patients.view');
    Route::post('patients/{patient}/merge-preview', [PatientController::class, 'mergePreview'])
        ->name('patients.merge-preview')->middleware('can:patients.delete');
    Route::post('patients/{patient}/merge', [PatientController::class, 'merge'])
        ->name('patients.merge')->middleware('can:patients.delete');
    Route::resource('patients', PatientController::class)->middleware('can:patients.view');
    Route::post('patients/{patient}/avatar', [PatientController::class, 'uploadAvatar'])
        ->name('patients.upload-avatar')->middleware('can:patients.edit');

    // Quick appointment registration per patient
    Route::get('patients/{patient}/register-appointment', [PatientController::class, 'registerAppointment'])
        ->name('patients.register-appointment')->middleware('can:appointments.view');
    Route::post('patients/{patient}/quick-register', [PatientController::class, 'quickRegister'])
        ->name('patients.quick-register')->middleware('can:appointments.create');

    // Clinical notes
    Route::post('patients/{patient}/clinical-notes', [ClinicalNoteController::class, 'store'])
        ->name('clinical-notes.store')->middleware('can:clinical_notes.create');
    Route::put('clinical-notes/{note}', [ClinicalNoteController::class, 'update'])
        ->name('clinical-notes.update')->middleware('can:clinical_notes.create');
    Route::delete('clinical-notes/{note}', [ClinicalNoteController::class, 'destroy'])
        ->name('clinical-notes.destroy')->middleware('can:clinical_notes.create');

    // Tooth conditions
    Route::post('patients/{patient}/tooth-conditions', [ToothConditionController::class, 'upsert'])
        ->name('tooth-conditions.upsert')->middleware('can:clinical_notes.create');
    Route::delete('patients/{patient}/tooth-conditions/{condition}', [ToothConditionController::class, 'destroy'])
        ->name('tooth-conditions.destroy')->middleware('can:clinical_notes.create');

    // Patient attachments (Phase M)
    Route::post('patients/{patient}/attachments', [PatientAttachmentController::class, 'store'])
        ->name('patient-attachments.store')->middleware('can:patients.edit');
    Route::delete('patient-attachments/{attachment}', [PatientAttachmentController::class, 'destroy'])
        ->name('patient-attachments.destroy')->middleware('can:patients.edit');

    // Consent forms (Phase M)
    Route::post('patients/{patient}/consent-forms', [ConsentFormController::class, 'store'])
        ->name('consent-forms.store')->middleware('can:patients.edit');
    Route::post('consent-forms/{consentForm}/sign', [ConsentFormController::class, 'sign'])
        ->name('consent-forms.sign')->middleware('can:patients.edit');
    Route::delete('consent-forms/{consentForm}', [ConsentFormController::class, 'destroy'])
        ->name('consent-forms.destroy')->middleware('can:patients.edit');

    // Patient relationships (Phase M)
    Route::post('patients/{patient}/relationships', [PatientRelationshipController::class, 'store'])
        ->name('patient-relationships.store')->middleware('can:patients.edit');
    Route::delete('patient-relationships/{relationship}', [PatientRelationshipController::class, 'destroy'])
        ->name('patient-relationships.destroy')->middleware('can:patients.edit');

    // Secondary phone numbers
    Route::post('patients/{patient}/phones', [PatientPhoneController::class, 'store'])
        ->name('patient-phones.store')->middleware('can:patients.edit');
    Route::delete('patient-phones/{phone}', [PatientPhoneController::class, 'destroy'])
        ->name('patient-phones.destroy')->middleware('can:patients.edit');
});

// CRM
Route::middleware(['auth'])->prefix('crm')->name('crm.')->group(function () {
    Route::resource('leads', LeadController::class)->middleware('can:leads.view');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert')->middleware('can:leads.manage');
    Route::post('leads/{lead}/transition', [LeadController::class, 'transition'])->name('leads.transition')->middleware('can:leads.manage');
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign')->middleware('can:leads.assign');
    Route::post('activities', [ContactActivityController::class, 'store'])->name('activities.store');
    Route::resource('tasks', FollowUpTaskController::class)->only(['index', 'store'])->middleware('can:leads.view');
    Route::post('tasks/{task}/complete', [FollowUpTaskController::class, 'complete'])->name('tasks.complete');

    // Message templates + log
    Route::get('messages/templates', [MessageController::class, 'templates'])->name('messages.templates')->middleware('can:leads.manage');
    Route::post('messages/templates', [MessageController::class, 'storeTemplate'])->name('messages.templates.store')->middleware('can:leads.manage');
    Route::put('messages/templates/{template}', [MessageController::class, 'updateTemplate'])->name('messages.templates.update')->middleware('can:leads.manage');
    Route::delete('messages/templates/{template}', [MessageController::class, 'destroyTemplate'])->name('messages.templates.destroy')->middleware('can:leads.manage');
    Route::get('messages/log', [MessageController::class, 'log'])->name('messages.log')->middleware('can:leads.manage');
    Route::post('messages/send', [MessageController::class, 'send'])->name('messages.send')->middleware('can:leads.manage');

    // Care Rules
    Route::get('care-rules', [CareRuleController::class, 'index'])->name('care-rules.index')->middleware('can:leads.manage');
    Route::post('care-rules', [CareRuleController::class, 'store'])->name('care-rules.store')->middleware('can:leads.manage');
    Route::put('care-rules/{careRule}', [CareRuleController::class, 'update'])->name('care-rules.update')->middleware('can:leads.manage');
    Route::delete('care-rules/{careRule}', [CareRuleController::class, 'destroy'])->name('care-rules.destroy')->middleware('can:leads.manage');
});

// Cashier
Route::middleware(['auth'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('invoices/data', [PatientInvoiceController::class, 'data'])
        ->name('invoices.data')->middleware('can:cashier.view');
    Route::resource('invoices', PatientInvoiceController::class)->only(['index', 'show'])->middleware('can:cashier.view');
    Route::post('invoices/{invoice}/payments', [PatientPaymentController::class, 'store'])->name('invoices.payments.store')->middleware('can:cashier.manage');
    Route::patch('payments/{payment}/method', [PatientPaymentController::class, 'updateMethod'])->name('payments.update-method')->middleware('can:cashier.manage');
    Route::patch('payments/{payment}/date', [PatientPaymentController::class, 'updateDate'])->name('payments.update-date')->middleware('can:cashier.manage');
    Route::post('invoices/{invoice}/discount', [PatientInvoiceController::class, 'discount'])->name('invoices.discount')->middleware('can:cashier.approve_discount');
    Route::post('invoices/{invoice}/cancel', [PatientInvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('can:cashier.manage');
    Route::get('invoices/{invoice}/receipt', [PatientInvoiceController::class, 'pdf'])->name('invoices.receipt')->middleware('can:cashier.view');
    Route::get('debts', [DebtController::class, 'index'])->name('debts.index')->middleware('can:cashier.view');
    // Expenses
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('can:expenses.view');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('can:expenses.manage');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('can:expenses.manage');
});

// Clinical
Route::middleware(['auth'])->prefix('clinical')->name('clinical.')->group(function () {
    // Clinical templates
    Route::get('templates', [ClinicalTemplateController::class, 'index'])->name('templates.index')->middleware('can:clinical_notes.create');
    Route::post('templates', [ClinicalTemplateController::class, 'store'])->name('templates.store')->middleware('can:clinical_notes.create');
    Route::put('templates/{template}', [ClinicalTemplateController::class, 'update'])->name('templates.update')->middleware('can:clinical_notes.create');
    Route::delete('templates/{template}', [ClinicalTemplateController::class, 'destroy'])->name('templates.destroy')->middleware('can:clinical_notes.create');
    Route::get('templates/search', [ClinicalTemplateController::class, 'search'])->name('templates.search')->middleware('can:clinical_notes.create');

    Route::get('treatment-plans/data', [TreatmentPlanController::class, 'data'])->name('treatment-plans.data')->middleware('can:treatment_plans.view');
    Route::resource('treatment-plans', TreatmentPlanController::class)->middleware('can:treatment_plans.view');
    Route::post('treatment-plans/{treatmentPlan}/items', [TreatmentPlanItemController::class, 'store'])->name('treatment-plans.items.store')->middleware('can:treatment_plans.edit');
    Route::put('treatment-plan-items/{treatmentPlanItem}', [TreatmentPlanItemController::class, 'update'])->name('treatment-plan-items.update')->middleware('can:treatment_plans.edit');
    Route::delete('treatment-plan-items/{treatmentPlanItem}', [TreatmentPlanItemController::class, 'destroy'])->name('treatment-plan-items.destroy')->middleware('can:treatment_plans.edit');
    Route::patch('treatment-plan-items/{treatmentPlanItem}/status', [TreatmentPlanItemController::class, 'updateStatus'])->name('treatment-plan-items.update-status')->middleware('can:treatment_plans.edit');
    Route::post('treatment-plan-items/{treatmentPlanItem}/complete', [TreatmentPlanItemController::class, 'complete'])->name('treatment-plan-items.complete')->middleware('can:treatment_plans.edit');
    Route::post('treatment-plans/{treatmentPlan}/transition', [TreatmentPlanController::class, 'transition'])->name('treatment-plans.transition')->middleware('can:treatment_plans.edit');
    Route::post('treatment-plans/{treatmentPlan}/approve', [TreatmentPlanController::class, 'approve'])->name('treatment-plans.approve')->middleware('can:treatment_plans.approve');
    Route::patch('treatment-plans/{treatmentPlan}/payment-schedule', [TreatmentPlanController::class, 'savePaymentSchedule'])->name('treatment-plans.payment-schedule')->middleware('can:treatment_plans.edit');
    Route::patch('treatment-plans/{treatmentPlan}/payment-notes', [TreatmentPlanController::class, 'savePaymentNotes'])->name('treatment-plans.payment-notes')->middleware('can:treatment_plans.edit');
    Route::get('treatment-plans/{treatmentPlan}/pdf', [TreatmentPlanController::class, 'pdf'])->name('treatment-plans.pdf')->middleware('can:treatment_plans.view');
});

// Schedule
Route::middleware(['auth'])->prefix('schedule')->name('schedule.')->group(function () {
    Route::get('appointments/data', [AppointmentController::class, 'data'])->name('appointments.data')->middleware('can:appointments.view');
    Route::resource('appointments', AppointmentController::class)->middleware('can:appointments.view');
    Route::post('appointments/{appointment}/transition', [AppointmentController::class, 'transition'])
        ->name('appointments.transition')->middleware('can:appointments.manage');
    Route::patch('appointments/{appointment}/quick-reschedule', [AppointmentController::class, 'quickReschedule'])
        ->name('appointments.quick-reschedule')->middleware('can:appointments.manage');
    Route::patch('appointments/{appointment}/notes', [AppointmentController::class, 'updateNotes'])
        ->name('appointments.update-notes')->middleware('can:appointments.manage');
    Route::post('appointments/{appointment}/quick-register', [AppointmentController::class, 'quickRegister'])
        ->name('appointments.quick-register')->middleware('can:appointments.manage');

    Route::get('registrations', [RegistrationController::class, 'index'])
        ->name('registrations.index')->middleware('can:appointments.view');
    Route::post('registrations', [RegistrationController::class, 'store'])
        ->name('registrations.store')->middleware('can:appointments.create');
    Route::put('registrations/{registration}', [RegistrationController::class, 'update'])
        ->name('registrations.update')->middleware('can:appointments.create');
    Route::patch('registrations/{registration}', [RegistrationController::class, 'update'])
        ->name('registrations.patch')->middleware('can:appointments.create');
    Route::delete('registrations/{registration}', [RegistrationController::class, 'destroy'])
        ->name('registrations.destroy')->middleware('can:appointments.create');
});

// Core
Route::middleware(['auth'])->group(function () {
    Route::resource('dental-chairs', DentalChairController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:branches.manage');
    Route::resource('branches', BranchController::class)->middleware('can:branches.view');
    Route::resource('employees', EmployeeController::class)->middleware('can:employees.view');
    Route::patch('employees/{employee}/toggle-active', [EmployeeController::class, 'toggleActive'])
        ->name('employees.toggle-active')->middleware('can:employees.manage');

    // Core management
    Route::prefix('core')->name('core.')->group(function () {
        Route::get('fund-accounts', [FundAccountController::class, 'index'])->name('fund-accounts.index')->middleware('can:branches.manage');
        Route::post('fund-accounts', [FundAccountController::class, 'store'])->name('fund-accounts.store')->middleware('can:branches.manage');
        Route::put('fund-accounts/{fundAccount}', [FundAccountController::class, 'update'])->name('fund-accounts.update')->middleware('can:branches.manage');
        Route::delete('fund-accounts/{fundAccount}', [FundAccountController::class, 'destroy'])->name('fund-accounts.destroy')->middleware('can:branches.manage');

        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index')->middleware('can:branches.manage');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store')->middleware('can:branches.manage');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update')->middleware('can:branches.manage');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy')->middleware('can:branches.manage');
    });

    // HR: Commissions + Fixed Assets
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index')->middleware('can:commissions.view');
        Route::post('commissions/{transaction}/approve', [CommissionController::class, 'approve'])->name('commissions.approve')->middleware('can:commissions.manage');
        Route::post('commissions/{transaction}/mark-paid', [CommissionController::class, 'markPaid'])->name('commissions.mark-paid')->middleware('can:commissions.manage');
        Route::get('commissions/rules', [CommissionController::class, 'rules'])->name('commissions.rules')->middleware('can:commissions.manage');
        Route::post('commissions/rules', [CommissionController::class, 'storeRule'])->name('commissions.rules.store')->middleware('can:commissions.manage');
        Route::delete('commissions/rules/{rule}', [CommissionController::class, 'destroyRule'])->name('commissions.rules.destroy')->middleware('can:commissions.manage');

        // Fixed Assets
        Route::resource('fixed-assets', FixedAssetController::class)->middleware('can:fixed_assets.view');
        Route::post('fixed-assets/depreciate', [FixedAssetController::class, 'depreciate'])->name('fixed-assets.depreciate')->middleware('can:fixed_assets.manage');
        Route::post('fixed-assets/{fixedAsset}/dispose', [FixedAssetController::class, 'dispose'])->name('fixed-assets.dispose')->middleware('can:fixed_assets.manage');

        // KPIs
        Route::get('kpis', [KpiController::class, 'index'])->name('kpis.index')->middleware('can:employees.manage');
        Route::post('kpis', [KpiController::class, 'store'])->name('kpis.store')->middleware('can:employees.manage');
        Route::put('kpis/{kpi}', [KpiController::class, 'update'])->name('kpis.update')->middleware('can:employees.manage');
        Route::post('kpis/{kpi}/approve', [KpiController::class, 'approve'])->name('kpis.approve')->middleware('can:employees.manage');
        Route::delete('kpis/{kpi}', [KpiController::class, 'destroy'])->name('kpis.destroy')->middleware('can:employees.manage');

        // Attendance Devices (ZKTeco)
        Route::get('attendance-devices', [AttendanceDeviceController::class, 'index'])->name('attendance-devices.index')->middleware('can:employees.manage');
        Route::post('attendance-devices', [AttendanceDeviceController::class, 'store'])->name('attendance-devices.store')->middleware('can:employees.manage');
        Route::put('attendance-devices/{attendanceDevice}', [AttendanceDeviceController::class, 'update'])->name('attendance-devices.update')->middleware('can:employees.manage');
        Route::delete('attendance-devices/{attendanceDevice}', [AttendanceDeviceController::class, 'destroy'])->name('attendance-devices.destroy')->middleware('can:employees.manage');
        Route::post('attendance-devices/{attendanceDevice}/sync', [AttendanceDeviceController::class, 'sync'])->name('attendance-devices.sync')->middleware('can:employees.manage');

        // Salary Slips
        Route::get('salary-slips', [SalarySlipController::class, 'index'])->name('salary-slips.index')->middleware('can:employees.manage');
        Route::get('salary-slips/preview', [SalarySlipController::class, 'preview'])->name('salary-slips.preview')->middleware('can:employees.manage');
        Route::get('salary-slips/{salarySlip}', [SalarySlipController::class, 'show'])->name('salary-slips.show')->middleware('can:employees.manage');
        Route::post('salary-slips/generate', [SalarySlipController::class, 'generate'])->name('salary-slips.generate')->middleware('can:employees.manage');
        Route::post('salary-slips/{salarySlip}/confirm', [SalarySlipController::class, 'confirm'])->name('salary-slips.confirm')->middleware('can:employees.manage');
        Route::post('salary-slips/{salarySlip}/mark-paid', [SalarySlipController::class, 'markPaid'])->name('salary-slips.mark-paid')->middleware('can:employees.manage');
        Route::delete('salary-slips/{salarySlip}', [SalarySlipController::class, 'destroy'])->name('salary-slips.destroy')->middleware('can:employees.manage');

        // Work Shifts (Phase N)
        Route::get('work-shifts', [WorkShiftController::class, 'index'])->name('work-shifts.index')->middleware('can:employees.manage');
        Route::post('work-shifts', [WorkShiftController::class, 'store'])->name('work-shifts.store')->middleware('can:employees.manage');
        Route::put('work-shifts/{workShift}', [WorkShiftController::class, 'update'])->name('work-shifts.update')->middleware('can:employees.manage');
        Route::delete('work-shifts/{workShift}', [WorkShiftController::class, 'destroy'])->name('work-shifts.destroy')->middleware('can:employees.manage');

        // Attendance Periods (Bảng chấm công tháng)
        Route::get('attendance', [AttendancePeriodController::class, 'index'])->name('attendance.index')->middleware('can:employees.manage');
        Route::post('attendance', [AttendancePeriodController::class, 'store'])->name('attendance.store')->middleware('can:employees.manage');
        Route::get('attendance/{attendance}', [AttendancePeriodController::class, 'show'])->name('attendance.show')->middleware('can:employees.manage');
        Route::post('attendance/{attendance}/lock', [AttendancePeriodController::class, 'lock'])->name('attendance.lock')->middleware('can:employees.manage');
        Route::post('attendance/{attendance}/unlock', [AttendancePeriodController::class, 'unlock'])->name('attendance.unlock')->middleware('can:employees.manage');
        Route::get('attendance/{attendance}/export', [AttendancePeriodController::class, 'export'])->name('attendance.export')->middleware('can:employees.manage');
        Route::put('attendance/{attendance}/records/{record}', [AttendanceRecordController::class, 'update'])->name('attendance.records.update')->middleware('can:employees.manage');

        // Timesheets (Phase N)
        Route::get('timesheets', [TimesheetController::class, 'index'])->name('timesheets.index')->middleware('can:employees.manage');
        Route::post('timesheets', [TimesheetController::class, 'store'])->name('timesheets.store')->middleware('can:employees.manage');
        Route::post('timesheets/{timesheet}/approve', [TimesheetController::class, 'approve'])->name('timesheets.approve')->middleware('can:employees.manage');
        Route::delete('timesheets/{timesheet}', [TimesheetController::class, 'destroy'])->name('timesheets.destroy')->middleware('can:employees.manage');

        // Leave Requests (Phase N)
        Route::get('leaves', [LeaveController::class, 'index'])->name('leaves.index')->middleware('can:employees.manage');
        Route::post('leaves', [LeaveController::class, 'store'])->name('leaves.store')->middleware('can:employees.manage');
        Route::post('leaves/types', [LeaveController::class, 'storeType'])->name('leaves.types.store')->middleware('can:employees.manage');
        Route::post('leaves/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leaves.approve')->middleware('can:employees.manage');
        Route::post('leaves/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leaves.reject')->middleware('can:employees.manage');
        Route::delete('leaves/{leaveRequest}', [LeaveController::class, 'destroy'])->name('leaves.destroy')->middleware('can:employees.manage');

        // Employee Contracts (Phase N)
        Route::get('contracts', [EmployeeContractController::class, 'index'])->name('contracts.index')->middleware('can:employees.manage');
        Route::post('contracts', [EmployeeContractController::class, 'store'])->name('contracts.store')->middleware('can:employees.manage');
        Route::delete('contracts/{employeeContract}', [EmployeeContractController::class, 'destroy'])->name('contracts.destroy')->middleware('can:employees.manage');

        // Performance Reviews (Phase N)
        Route::get('reviews', [PerformanceReviewController::class, 'index'])->name('reviews.index')->middleware('can:employees.manage');
        Route::post('reviews', [PerformanceReviewController::class, 'store'])->name('reviews.store')->middleware('can:employees.manage');
        Route::post('reviews/{performanceReview}/finalize', [PerformanceReviewController::class, 'finalize'])->name('reviews.finalize')->middleware('can:employees.manage');
        Route::delete('reviews/{performanceReview}', [PerformanceReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('can:employees.manage');
    });

    // Labo
    Route::prefix('lab')->name('lab.')->group(function () {
        Route::resource('labs', LabController::class)->middleware('can:labo.view');
        Route::post('labs/{lab}/price-items', [LabController::class, 'storePriceItem'])->name('labs.price-items.store')->middleware('can:labo.manage');
        Route::delete('labs/{lab}/price-items/{priceItem}', [LabController::class, 'destroyPriceItem'])->name('labs.price-items.destroy')->middleware('can:labo.manage');

        Route::resource('orders', LabOrderController::class)->middleware('can:labo.view');
        Route::post('orders/{order}/transition', [LabOrderController::class, 'transition'])->name('orders.transition')->middleware('can:labo.manage');
        Route::post('orders/{order}/record-payment', [LabOrderController::class, 'recordPayment'])->name('orders.record-payment')->middleware('can:labo.manage');

        Route::get('payables', [LabOrderController::class, 'payables'])->name('payables')->middleware('can:labo.view');

        Route::get('warranties', [LabWarrantyController::class, 'index'])->name('warranties.index')->middleware('can:labo.view');
        Route::post('orders/{order}/warranties', [LabWarrantyController::class, 'store'])->name('warranties.store')->middleware('can:labo.manage');
        Route::post('warranties/{warranty}/claim', [LabWarrantyController::class, 'claim'])->name('warranties.claim')->middleware('can:labo.manage');
        Route::delete('warranties/{warranty}', [LabWarrantyController::class, 'destroy'])->name('warranties.destroy')->middleware('can:labo.manage');
    });

    // Accounting (Phase O)
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index')->middleware('can:accounting.view');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store')->middleware('can:accounting.manage');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->middleware('can:accounting.manage');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('can:accounting.manage');

        Route::get('purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index')->middleware('can:accounting.view');
        Route::get('purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create')->middleware('can:accounting.manage');
        Route::post('purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store')->middleware('can:accounting.manage');
        Route::get('purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show')->middleware('can:accounting.view');
        Route::post('purchase-invoices/{purchaseInvoice}/receive', [PurchaseInvoiceController::class, 'receive'])->name('purchase-invoices.receive')->middleware('can:accounting.manage');
        Route::post('purchase-invoices/{purchaseInvoice}/payment', [PurchaseInvoiceController::class, 'addPayment'])->name('purchase-invoices.payment')->middleware('can:accounting.manage');
        Route::post('purchase-invoices/{purchaseInvoice}/cancel', [PurchaseInvoiceController::class, 'cancel'])->name('purchase-invoices.cancel')->middleware('can:accounting.manage');
        Route::delete('purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'destroy'])->name('purchase-invoices.destroy')->middleware('can:accounting.manage');

        Route::get('fund-transfers', [FundTransferController::class, 'index'])->name('fund-transfers.index')->middleware('can:accounting.view');
        Route::post('fund-transfers', [FundTransferController::class, 'store'])->name('fund-transfers.store')->middleware('can:accounting.manage');
        Route::delete('fund-transfers/{fundTransfer}', [FundTransferController::class, 'destroy'])->name('fund-transfers.destroy')->middleware('can:accounting.manage');

        // Payroll — Bảng tính lương
        Route::get('payrolls', [PayrollController::class, 'index'])->name('payrolls.index')->middleware('can:accounting.view');
        Route::post('payrolls', [PayrollController::class, 'store'])->name('payrolls.store')->middleware('can:accounting.manage');
        Route::get('payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show')->middleware('can:accounting.view');
        Route::post('payrolls/{payroll}/confirm', [PayrollController::class, 'confirm'])->name('payrolls.confirm')->middleware('can:accounting.manage');
        Route::post('payrolls/{payroll}/unconfirm', [PayrollController::class, 'unconfirm'])->name('payrolls.unconfirm')->middleware('can:accounting.manage');
        Route::post('payrolls/{payroll}/lock', [PayrollController::class, 'lock'])->name('payrolls.lock')->middleware('can:accounting.manage');
        Route::post('payrolls/{payroll}/unlock', [PayrollController::class, 'unlock'])->name('payrolls.unlock')->middleware('can:accounting.manage');
        Route::get('payrolls/{payroll}/export', [PayrollController::class, 'export'])->name('payrolls.export')->middleware('can:accounting.view');
        Route::put('payrolls/{payroll}/items/{item}', [PayrollItemController::class, 'update'])->name('payrolls.items.update')->middleware('can:accounting.manage');
    });

    // HKD — Hộ kinh doanh (TT152/2025/TT-BTC)
    Route::prefix('hkd')->name('hkd.')->group(function () {
        // Profile + locations + tax rates
        Route::get('profile', [HkdProfileController::class, 'index'])->name('profile.index')->middleware('can:hkd.view');
        Route::post('profiles', [HkdProfileController::class, 'store'])->name('profiles.store')->middleware('can:hkd.manage');
        Route::put('profiles/{hkdProfile}', [HkdProfileController::class, 'update'])->name('profiles.update')->middleware('can:hkd.manage');
        Route::post('profiles/{hkdProfile}/locations', [HkdProfileController::class, 'storeLocation'])->name('profiles.locations.store')->middleware('can:hkd.manage');
        Route::delete('profiles/{hkdProfile}/locations/{location}', [HkdProfileController::class, 'destroyLocation'])->name('profiles.locations.destroy')->middleware('can:hkd.manage');
        Route::post('tax-rates', [HkdProfileController::class, 'storeTaxRate'])->name('tax-rates.store')->middleware('can:hkd.manage');
        Route::delete('tax-rates/{taxRate}', [HkdProfileController::class, 'destroyTaxRate'])->name('tax-rates.destroy')->middleware('can:hkd.manage');

        // Revenue (S1a / S2a / S2b source data)
        Route::get('revenue', [HkdRevenueController::class, 'index'])->name('revenue.index')->middleware('can:hkd.view');
        Route::post('revenue', [HkdRevenueController::class, 'store'])->name('revenue.store')->middleware('can:hkd.manage');
        Route::put('revenue/{hkdRevenue}', [HkdRevenueController::class, 'update'])->name('revenue.update')->middleware('can:hkd.manage');
        Route::delete('revenue/{hkdRevenue}', [HkdRevenueController::class, 'destroy'])->name('revenue.destroy')->middleware('can:hkd.manage');
        Route::post('revenue/import-invoices', [HkdRevenueController::class, 'importFromInvoices'])->name('revenue.import-invoices')->middleware('can:hkd.manage');

        // Expenses (S2c source data)
        Route::get('expenses', [HkdExpenseController::class, 'index'])->name('expenses.index')->middleware('can:hkd.view');
        Route::post('expenses', [HkdExpenseController::class, 'store'])->name('expenses.store')->middleware('can:hkd.manage');
        Route::put('expenses/{hkdExpense}', [HkdExpenseController::class, 'update'])->name('expenses.update')->middleware('can:hkd.manage');
        Route::delete('expenses/{hkdExpense}', [HkdExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('can:hkd.manage');
        Route::post('expenses/import-expenses', [HkdExpenseController::class, 'importFromExpenses'])->name('expenses.import-expenses')->middleware('can:hkd.manage');

        // Inventory (S2d)
        Route::get('inventory', [HkdInventoryController::class, 'index'])->name('inventory.index')->middleware('can:hkd.view');
        Route::post('inventory', [HkdInventoryController::class, 'storeItem'])->name('inventory.store')->middleware('can:hkd.manage');
        Route::put('inventory/{hkdInventoryItem}', [HkdInventoryController::class, 'updateItem'])->name('inventory.update')->middleware('can:hkd.manage');
        Route::post('inventory/{hkdInventoryItem}/transactions', [HkdInventoryController::class, 'storeTransaction'])->name('inventory.transactions.store')->middleware('can:hkd.manage');
        Route::delete('inventory/transactions/{hkdInventoryTransaction}', [HkdInventoryController::class, 'destroyTransaction'])->name('inventory.transactions.destroy')->middleware('can:hkd.manage');

        // Cash (S2e)
        Route::get('cash', [HkdCashController::class, 'index'])->name('cash.index')->middleware('can:hkd.view');
        Route::post('cash/accounts', [HkdCashController::class, 'storeAccount'])->name('cash.accounts.store')->middleware('can:hkd.manage');
        Route::put('cash/accounts/{hkdCashAccount}', [HkdCashController::class, 'updateAccount'])->name('cash.accounts.update')->middleware('can:hkd.manage');
        Route::post('cash/accounts/{hkdCashAccount}/transactions', [HkdCashController::class, 'storeTransaction'])->name('cash.transactions.store')->middleware('can:hkd.manage');
        Route::delete('cash/transactions/{hkdCashTransaction}', [HkdCashController::class, 'destroyTransaction'])->name('cash.transactions.destroy')->middleware('can:hkd.manage');

        // Other taxes (S3a)
        Route::get('other-taxes', [HkdOtherTaxController::class, 'index'])->name('other-taxes.index')->middleware('can:hkd.view');
        Route::post('other-taxes', [HkdOtherTaxController::class, 'store'])->name('other-taxes.store')->middleware('can:hkd.manage');
        Route::put('other-taxes/{hkdOtherTax}', [HkdOtherTaxController::class, 'update'])->name('other-taxes.update')->middleware('can:hkd.manage');
        Route::delete('other-taxes/{hkdOtherTax}', [HkdOtherTaxController::class, 'destroy'])->name('other-taxes.destroy')->middleware('can:hkd.manage');

        // Period close / unlock
        Route::get('periods', [HkdPeriodCloseController::class, 'index'])->name('periods.index')->middleware('can:hkd.view');
        Route::post('periods/{period}/close', [HkdPeriodCloseController::class, 'close'])->name('periods.close')->middleware('can:hkd.manage');
        Route::post('periods/{period}/unlock', [HkdPeriodCloseController::class, 'unlock'])->name('periods.unlock')->middleware('can:hkd.manage');

        // Reports — book preview + PDF download
        Route::get('reports', [HkdReportController::class, 'index'])->name('reports.index')->middleware('can:hkd.view');
        Route::get('reports/{period}/{book}/preview', [HkdReportController::class, 'preview'])->name('reports.preview')->middleware('can:hkd.view');
        Route::get('reports/{period}/{book}/pdf', [HkdReportController::class, 'downloadPdf'])->name('reports.pdf')->middleware('can:hkd.view');
    });

    // ── Dental Specialty Module ────────────────────────────────────────────────
    Route::prefix('dental')->name('dental.')->group(function () {
        // Disease / condition catalog
        Route::get('conditions', [ConditionController::class, 'index'])->name('conditions.index')->middleware('can:dental.manage');
        Route::post('conditions', [ConditionController::class, 'store'])->name('conditions.store')->middleware('can:dental.manage');
        Route::put('conditions/{condition}', [ConditionController::class, 'update'])->name('conditions.update')->middleware('can:dental.manage');
        Route::delete('conditions/{condition}', [ConditionController::class, 'destroy'])->name('conditions.destroy')->middleware('can:dental.manage');

        // Service steps + costs (per service)
        Route::get('services/{service}/steps', [ServiceStepController::class, 'show'])->name('services.steps')->middleware('can:dental.manage');
        Route::post('services/{service}/steps', [ServiceStepController::class, 'storeStep'])->name('services.steps.store')->middleware('can:dental.manage');
        Route::put('service-steps/{step}', [ServiceStepController::class, 'updateStep'])->name('service-steps.update')->middleware('can:dental.manage');
        Route::delete('service-steps/{step}', [ServiceStepController::class, 'destroyStep'])->name('service-steps.destroy')->middleware('can:dental.manage');
        Route::post('services/{service}/costs', [ServiceStepController::class, 'storeCost'])->name('services.costs.store')->middleware('can:dental.manage');
        Route::put('service-costs/{cost}', [ServiceStepController::class, 'updateCost'])->name('service-costs.update')->middleware('can:dental.manage');
        Route::delete('service-costs/{cost}', [ServiceStepController::class, 'destroyCost'])->name('service-costs.destroy')->middleware('can:dental.manage');

        // Dental examinations
        Route::get('examinations', [ExaminationController::class, 'index'])->name('examinations.index')->middleware('can:dental.view');
        Route::get('examinations/create', [ExaminationController::class, 'create'])->name('examinations.create')->middleware('can:dental.view');
        Route::post('examinations', [ExaminationController::class, 'store'])->name('examinations.store')->middleware('can:dental.view');
        Route::get('examinations/{examination}', [ExaminationController::class, 'show'])->name('examinations.show')->middleware('can:dental.view');
        Route::post('examinations/{examination}/complete', [ExaminationController::class, 'complete'])->name('examinations.complete')->middleware('can:dental.view');
        Route::delete('examinations/{examination}', [ExaminationController::class, 'destroy'])->name('examinations.destroy')->middleware('can:dental.manage');

        // Treatment step execution (per plan item)
        Route::get('treatment-items/{item}/execution', [TreatmentExecutionController::class, 'show'])->name('treatment-items.execution')->middleware('can:treatment_plans.edit');
        Route::post('treatment-items/{item}/executions', [TreatmentExecutionController::class, 'storeExecution'])->name('treatment-items.executions.store')->middleware('can:treatment_plans.edit');
        Route::post('step-executions/{execution}/complete', [TreatmentExecutionController::class, 'completeExecution'])->name('step-executions.complete')->middleware('can:treatment_plans.edit');
        Route::post('treatment-items/{item}/status', [TreatmentExecutionController::class, 'updateItemStatus'])->name('treatment-items.status')->middleware('can:treatment_plans.edit');

        // KPI allocations
        Route::get('kpi', [KpiAllocationController::class, 'index'])->name('kpi.index')->middleware('can:dental.kpi.view');
        Route::post('kpi/{allocation}/approve', [KpiAllocationController::class, 'approve'])->name('kpi.approve')->middleware('can:dental.kpi.manage');
        Route::post('kpi/{allocation}/hold', [KpiAllocationController::class, 'hold'])->name('kpi.hold')->middleware('can:dental.kpi.manage');
        Route::post('kpi/{allocation}/release', [KpiAllocationController::class, 'release'])->name('kpi.release')->middleware('can:dental.kpi.manage');
        Route::post('kpi/{allocation}/reverse', [KpiAllocationController::class, 'reverse'])->name('kpi.reverse')->middleware('can:dental.kpi.manage');
        Route::post('kpi/{allocation}/mark-paid', [KpiAllocationController::class, 'markPaid'])->name('kpi.mark-paid')->middleware('can:dental.kpi.manage');
        Route::post('treatment-items/{item}/kpi/submit', [KpiAllocationController::class, 'submitForApproval'])->name('kpi.submit')->middleware('can:dental.kpi.manage');
    });

    // Catalog
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::resource('services', DentalServiceController::class)->middleware('can:services.view');
        Route::resource('price-lists', PriceListController::class)->middleware('can:services.view');
        Route::post('price-lists/{priceList}/items', [PriceListController::class, 'addItem'])
            ->name('price-lists.items.add')->middleware('can:services.manage');
        Route::delete('price-list-items/{item}', [PriceListController::class, 'removeItem'])
            ->name('price-lists.items.remove')->middleware('can:services.manage');
        Route::get('service-categories', [ServiceCategoryController::class, 'index'])->name('service-categories.index')->middleware('can:services.manage');
        Route::post('service-categories', [ServiceCategoryController::class, 'store'])->name('service-categories.store')->middleware('can:services.manage');
        Route::put('service-categories/{serviceCategory}', [ServiceCategoryController::class, 'update'])->name('service-categories.update')->middleware('can:services.manage');
        Route::delete('service-categories/{serviceCategory}', [ServiceCategoryController::class, 'destroy'])->name('service-categories.destroy')->middleware('can:services.manage');
        Route::get('service-groups', [ServiceGroupController::class, 'index'])->name('service-groups.index')->middleware('can:services.manage');
        Route::post('service-groups', [ServiceGroupController::class, 'store'])->name('service-groups.store')->middleware('can:services.manage');
        Route::put('service-groups/{serviceGroup}', [ServiceGroupController::class, 'update'])->name('service-groups.update')->middleware('can:services.manage');
        Route::delete('service-groups/{serviceGroup}', [ServiceGroupController::class, 'destroy'])->name('service-groups.destroy')->middleware('can:services.manage');
    });
});

require __DIR__.'/auth.php';
