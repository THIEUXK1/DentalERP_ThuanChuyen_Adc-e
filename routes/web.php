<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Cashier\DebtController;
use App\Http\Controllers\Cashier\ExpenseController;
use App\Http\Controllers\Cashier\PatientInvoiceController;
use App\Http\Controllers\Cashier\PatientPaymentController;
use App\Http\Controllers\Catalog\DentalServiceController;
use App\Http\Controllers\Catalog\PriceListController;
use App\Http\Controllers\Clinical\ClinicalNoteController;
use App\Http\Controllers\Clinical\ToothConditionController;
use App\Http\Controllers\Clinical\TreatmentPlanController;
use App\Http\Controllers\Clinical\TreatmentPlanItemController;
use App\Http\Controllers\Core\BranchController;
use App\Http\Controllers\Core\DentalChairController;
use App\Http\Controllers\Crm\ContactActivityController;
use App\Http\Controllers\Crm\FollowUpTaskController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\PatientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hr\CommissionController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Schedule\AppointmentController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', fn () => redirect()->route('login'));

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Reports
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue')->middleware('can:reports.financial');
    Route::get('appointments', [ReportController::class, 'appointments'])->name('appointments')->middleware('can:reports.view');
    Route::get('debt', [ReportController::class, 'debt'])->name('debt')->middleware('can:reports.financial');
    Route::get('crm', [ReportController::class, 'crm'])->name('crm')->middleware('can:reports.view');
    Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss')->middleware('can:reports.financial');
});

// Profile (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->middleware('can:admin.users');
    Route::resource('roles', RoleController::class)->only(['index', 'show'])->middleware('can:admin.roles');
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index')->middleware('can:admin.audit_log');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index')->middleware('can:settings.view');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update')->middleware('can:settings.manage');
});

// Notifications
Route::middleware(['auth'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// Patients + clinical sub-resources
Route::middleware(['auth'])->group(function () {
    Route::resource('patients', PatientController::class)->middleware('can:patients.view');

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
});

// Cashier
Route::middleware(['auth'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::resource('invoices', PatientInvoiceController::class)->only(['index', 'show'])->middleware('can:cashier.view');
    Route::post('invoices/{invoice}/payments', [PatientPaymentController::class, 'store'])->name('invoices.payments.store')->middleware('can:cashier.manage');
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
    Route::resource('treatment-plans', TreatmentPlanController::class)->middleware('can:treatment_plans.view');
    Route::post('treatment-plans/{treatmentPlan}/items', [TreatmentPlanItemController::class, 'store'])->name('treatment-plans.items.store')->middleware('can:treatment_plans.edit');
    Route::put('treatment-plan-items/{treatmentPlanItem}', [TreatmentPlanItemController::class, 'update'])->name('treatment-plan-items.update')->middleware('can:treatment_plans.edit');
    Route::delete('treatment-plan-items/{treatmentPlanItem}', [TreatmentPlanItemController::class, 'destroy'])->name('treatment-plan-items.destroy')->middleware('can:treatment_plans.edit');
    Route::post('treatment-plan-items/{treatmentPlanItem}/complete', [TreatmentPlanItemController::class, 'complete'])->name('treatment-plan-items.complete')->middleware('can:treatment_plans.edit');
    Route::post('treatment-plans/{treatmentPlan}/transition', [TreatmentPlanController::class, 'transition'])->name('treatment-plans.transition')->middleware('can:treatment_plans.edit');
    Route::post('treatment-plans/{treatmentPlan}/approve', [TreatmentPlanController::class, 'approve'])->name('treatment-plans.approve')->middleware('can:treatment_plans.approve');
    Route::patch('treatment-plans/{treatmentPlan}/payment-schedule', [TreatmentPlanController::class, 'savePaymentSchedule'])->name('treatment-plans.payment-schedule')->middleware('can:treatment_plans.edit');
    Route::get('treatment-plans/{treatmentPlan}/pdf', [TreatmentPlanController::class, 'pdf'])->name('treatment-plans.pdf')->middleware('can:treatment_plans.view');
});

// Schedule
Route::middleware(['auth'])->prefix('schedule')->name('schedule.')->group(function () {
    Route::resource('appointments', AppointmentController::class)->middleware('can:appointments.view');
    Route::post('appointments/{appointment}/transition', [AppointmentController::class, 'transition'])
        ->name('appointments.transition')->middleware('can:appointments.manage');
});

// Core
Route::middleware(['auth'])->group(function () {
    Route::resource('dental-chairs', DentalChairController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:branches.manage');
    Route::resource('branches', BranchController::class)->middleware('can:branches.view');
    Route::resource('employees', EmployeeController::class)->middleware('can:employees.view');

    // Commissions
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index')->middleware('can:commissions.view');
        Route::post('commissions/{transaction}/approve', [CommissionController::class, 'approve'])->name('commissions.approve')->middleware('can:commissions.manage');
        Route::post('commissions/{transaction}/mark-paid', [CommissionController::class, 'markPaid'])->name('commissions.mark-paid')->middleware('can:commissions.manage');
        Route::get('commissions/rules', [CommissionController::class, 'rules'])->name('commissions.rules')->middleware('can:commissions.manage');
        Route::post('commissions/rules', [CommissionController::class, 'storeRule'])->name('commissions.rules.store')->middleware('can:commissions.manage');
        Route::delete('commissions/rules/{rule}', [CommissionController::class, 'destroyRule'])->name('commissions.rules.destroy')->middleware('can:commissions.manage');
    });

    // Catalog
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::resource('services', DentalServiceController::class)->middleware('can:services.view');
        Route::resource('price-lists', PriceListController::class)->middleware('can:services.view');
        Route::post('price-lists/{priceList}/items', [PriceListController::class, 'addItem'])
            ->name('price-lists.items.add')->middleware('can:services.manage');
        Route::delete('price-list-items/{item}', [PriceListController::class, 'removeItem'])
            ->name('price-lists.items.remove')->middleware('can:services.manage');
    });
});

require __DIR__.'/auth.php';
