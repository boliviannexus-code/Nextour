<?php

use App\Http\Controllers\Web\ActivityTypeController;
use App\Http\Controllers\Web\AdminDataTableController;
use App\Http\Controllers\Web\AuditController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CompanyController;
use App\Http\Controllers\Web\CreditConsumptionRuleController;
use App\Http\Controllers\Web\CreditPackageController;
use App\Http\Controllers\Web\CreditPurchaseController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DatabaseBackupController;
use App\Http\Controllers\Web\GuideTypeController;
use App\Http\Controllers\Web\LocationSearchController;
use App\Http\Controllers\Web\ManagerDashboardController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\Public\BusinessRegistrationController;
use App\Http\Controllers\Web\Public\PublicTourController;
use App\Http\Controllers\Web\Public\TourBookingController;
use App\Http\Controllers\Web\Public\TouristAuthController;
use App\Http\Controllers\Web\Public\TouristPanelController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\RegistrationRequestController;
use App\Http\Controllers\Web\SubscriptionCreditController;
use App\Http\Controllers\Web\TourAvailabilityController;
use App\Http\Controllers\Web\TourBookingAdminController;
use App\Http\Controllers\Web\TourController;
use App\Http\Controllers\Web\TransportTypeController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WebsiteSettingsController;
use App\Support\LocaleManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/'.LocaleManager::preferredFrom(request()));
});

Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => 'es|en'],
    'middleware' => ['persistLocale'],
], function (): void {
    Route::get('/', [PublicTourController::class, 'home'])->name('public.home');
    Route::get('tours', [PublicTourController::class, 'index'])->name('public.tours.index');
    Route::get('tour/{tour}', [PublicTourController::class, 'show'])->name('public.tours.show');
    Route::get('locations/search', LocationSearchController::class)->name('locations.search');

    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.store');
        Route::get('register', [TouristAuthController::class, 'showRegister'])->name('tourist.register');
        Route::post('register', [TouristAuthController::class, 'register'])->name('tourist.register.store');
        Route::prefix('business-register')->name('business-register.')->group(function (): void {
            Route::get('/', [BusinessRegistrationController::class, 'select'])->name('select');
            Route::get('company', [BusinessRegistrationController::class, 'company'])->name('company');
            Route::post('company', [BusinessRegistrationController::class, 'storeCompany'])->name('company.store');
            Route::get('independent', [BusinessRegistrationController::class, 'independent'])->name('independent');
            Route::post('independent', [BusinessRegistrationController::class, 'storeIndependent'])->name('independent.store');
            Route::get('thanks', [BusinessRegistrationController::class, 'thanks'])->name('thanks');
        });
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('tour/{tour}/book', [TourBookingController::class, 'create'])->name('public.bookings.create');
        Route::post('tour/{tour}/book', [TourBookingController::class, 'store'])->name('public.bookings.store');

        Route::prefix('account')->name('tourist.')->group(function (): void {
            Route::get('bookings', [TouristPanelController::class, 'reservations'])->name('reservations.index');
            Route::get('bookings/{booking}', [TouristPanelController::class, 'show'])->name('reservations.show');
            Route::get('bookings/{booking}/voucher', [TouristPanelController::class, 'voucher'])->name('reservations.voucher');
            Route::get('history', [TouristPanelController::class, 'history'])->name('history');
            Route::get('profile', [TouristPanelController::class, 'profile'])->name('profile');
        });
    });
});

Route::middleware(['auth', 'noAuthCache'])->prefix('admin')->group(function (): void {

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('manager-dashboard', ManagerDashboardController::class)->middleware('companyApproved')->name('manager.dashboard');
    Route::prefix('registration-requests')->name('registration-requests.')->group(function (): void {
        Route::get('/', [RegistrationRequestController::class, 'index'])->name('index');
        Route::get('{registrationRequest}', [RegistrationRequestController::class, 'show'])->name('show');
        Route::post('{registrationRequest}/approve', [RegistrationRequestController::class, 'approve'])->name('approve');
        Route::post('{registrationRequest}/observe', [RegistrationRequestController::class, 'observe'])->name('observe');
        Route::post('{registrationRequest}/reject', [RegistrationRequestController::class, 'reject'])->name('reject');
        Route::post('{registrationRequest}/resubmit', [RegistrationRequestController::class, 'resubmit'])->name('resubmit');
    });
    Route::get('audits', [AuditController::class, 'index'])->middleware(['companyApproved', 'permission:audits.view'])->name('audits.index');
    Route::get('audits/{audit}', [AuditController::class, 'show'])->middleware(['companyApproved', 'permission:audits.view'])->name('audits.show');
    Route::prefix('database-backups')->name('database-backups.')->middleware('permission:database-backups.manage')->group(function (): void {
        Route::get('/', [DatabaseBackupController::class, 'index'])->name('index');
        Route::post('/', [DatabaseBackupController::class, 'store'])->name('store');
        Route::post('restore-upload', [DatabaseBackupController::class, 'restoreUpload'])->name('restore-upload');
        Route::get('{backup}/download', [DatabaseBackupController::class, 'download'])->where('backup', '[A-Za-z0-9_.-]+\.sql')->name('download');
        Route::post('{backup}/restore', [DatabaseBackupController::class, 'restoreStored'])->where('backup', '[A-Za-z0-9_.-]+\.sql')->name('restore');
        Route::delete('{backup}', [DatabaseBackupController::class, 'destroy'])->where('backup', '[A-Za-z0-9_.-]+\.sql')->name('destroy');
    });
    Route::prefix('subscriptions')->name('subscriptions.')->group(function (): void {
        Route::get('/', [SubscriptionCreditController::class, 'index'])->middleware('permission:subscription.view')->name('index');
        Route::put('settings', [SubscriptionCreditController::class, 'updateSettings'])->middleware('permission:subscription.manage')->name('settings.update');
        Route::post('credits', [SubscriptionCreditController::class, 'addCredits'])->middleware('permission:credits.adjust')->name('credits.store');
    });
    Route::prefix('credit-packages')->name('credit-packages.')->middleware('permission:credits.manage')->group(function (): void {
        Route::get('/', [CreditPackageController::class, 'index'])->name('index');
        Route::post('/', [CreditPackageController::class, 'store'])->name('store');
        Route::get('{package}/edit', [CreditPackageController::class, 'edit'])->name('edit');
        Route::put('{package}', [CreditPackageController::class, 'update'])->name('update');
        Route::delete('{package}', [CreditPackageController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('credit-consumption-rules')->name('credit-consumption-rules.')->middleware('permission:credits.manage')->group(function (): void {
        Route::get('/', [CreditConsumptionRuleController::class, 'index'])->name('index');
        Route::post('/', [CreditConsumptionRuleController::class, 'store'])->name('store');
        Route::put('{rule}', [CreditConsumptionRuleController::class, 'update'])->name('update');
        Route::delete('{rule}', [CreditConsumptionRuleController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('credit-purchases')->name('credit-purchases.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [CreditPurchaseController::class, 'index'])->middleware('permission:credits.purchase')->name('index');
        Route::post('/', [CreditPurchaseController::class, 'store'])->middleware('permission:credits.purchase')->name('store');
    });
    Route::prefix('credit-purchases/requests')->name('credit-purchases.requests.')->middleware('permission:credits.manage')->group(function (): void {
        Route::get('/', [CreditPurchaseController::class, 'adminIndex'])->name('index');
        Route::post('{purchaseRequest}/approve', [CreditPurchaseController::class, 'approve'])->name('approve');
        Route::post('{purchaseRequest}/reject', [CreditPurchaseController::class, 'reject'])->name('reject');
    });
    Route::prefix('companies')->name('companies.')->group(function (): void {
        Route::get('/', [CompanyController::class, 'index'])->middleware('permission:companies.view')->name('index');
        Route::get('create', [CompanyController::class, 'create'])->middleware('permission:companies.create')->name('create');
        Route::post('/', [CompanyController::class, 'store'])->middleware('permission:companies.create')->name('store');
        Route::get('{company}', [CompanyController::class, 'show'])->middleware('permission:companies.view')->name('show');
        Route::get('{company}/edit', [CompanyController::class, 'edit'])->middleware('permission:companies.update')->name('edit');
        Route::put('{company}', [CompanyController::class, 'update'])->middleware('permission:companies.update')->name('update');
        Route::delete('{company}', [CompanyController::class, 'destroy'])->middleware('permission:companies.delete')->name('destroy');
    });
    Route::prefix('tours')->name('tours.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [TourController::class, 'index'])->middleware('permission:tours.view')->name('index');
        Route::get('reviews', [TourController::class, 'reviewQueue'])->middleware('permission:tours.review')->name('reviews.index');
        Route::prefix('availability')->name('availability.')->middleware('permission:tours.availability')->group(function (): void {
            Route::get('/', [TourAvailabilityController::class, 'index'])->name('index');
            Route::get('grid', [TourAvailabilityController::class, 'grid'])->name('grid');
            Route::patch('day', [TourAvailabilityController::class, 'updateDay'])->name('day.update');
            Route::post('bulk', [TourAvailabilityController::class, 'bulkUpdate'])->name('bulk');
        });
        Route::get('create', [TourController::class, 'create'])->middleware('permission:tours.create')->name('create');
        Route::post('/', [TourController::class, 'store'])->middleware('permission:tours.create')->name('store');
        Route::post('draft', [TourController::class, 'storeDraft'])->middleware('permission:tours.create')->name('draft.store');
        Route::get('{tour}', [TourController::class, 'show'])->middleware('permission:tours.view')->name('show');
        Route::get('{tour}/edit', [TourController::class, 'edit'])->middleware('permission:tours.edit')->name('edit');
        Route::get('{tour}/wizard', [TourController::class, 'editWizard'])->middleware('permission:tours.edit')->name('wizard.edit');
        Route::patch('{tour}/wizard/step/{step}', [TourController::class, 'updateStep'])->middleware('permission:tours.edit')->name('wizard.step');
        Route::post('{tour}/finalize', [TourController::class, 'finalize'])->middleware('permission:tours.edit')->name('finalize');
        Route::post('{tour}/approve', [TourController::class, 'approve'])->middleware('permission:tours.review')->name('approve');
        Route::post('{tour}/reject', [TourController::class, 'reject'])->middleware('permission:tours.review')->name('reject');
        Route::patch('{tour}/toggle-status', [TourController::class, 'toggleStatus'])->middleware('permission:tours.edit')->name('toggle-status');
        Route::get('{tour}/pricing', [TourController::class, 'pricing'])->middleware('permission:tours.pricing')->name('pricing.edit');
        Route::put('{tour}/pricing', [TourController::class, 'updatePricing'])->middleware('permission:tours.pricing')->name('pricing.update');
        Route::post('{tour}/images', [TourController::class, 'uploadImages'])->middleware('permission:tours.edit')->name('images.store');
        Route::delete('{tour}/images/{image}', [TourController::class, 'deleteImage'])->middleware('permission:tours.edit')->name('images.destroy');
        Route::patch('{tour}/images/{image}/main', [TourController::class, 'setMainImage'])->middleware('permission:tours.edit')->name('images.main');
        Route::put('{tour}', [TourController::class, 'update'])->middleware('permission:tours.edit')->name('update');
        Route::delete('{tour}', [TourController::class, 'destroy'])->middleware('permission:tours.delete')->name('destroy');
    });
    Route::prefix('bookings')->name('bookings.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [TourBookingAdminController::class, 'index'])->middleware('permission:bookings.view')->name('index');
        Route::get('{booking}', [TourBookingAdminController::class, 'show'])->middleware('permission:bookings.view')->name('show');
        Route::patch('{booking}/status', [TourBookingAdminController::class, 'updateStatus'])->middleware('permission:bookings.manage')->name('status.update');
    });
    Route::get('website-settings', [WebsiteSettingsController::class, 'edit'])->middleware(['companyApproved', 'permission:website.manage'])->name('website-settings.edit');
    Route::put('website-settings', [WebsiteSettingsController::class, 'update'])->middleware(['companyApproved', 'permission:website.manage'])->name('website-settings.update');
    Route::prefix('categories')->name('categories.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('index');
        Route::get('create', [CategoryController::class, 'create'])->middleware('permission:categories.create')->name('create');
        Route::post('/', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('store');
        Route::get('{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view')->name('show');
        Route::get('{category}/edit', [CategoryController::class, 'edit'])->middleware('permission:categories.update')->name('edit');
        Route::put('{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('update');
        Route::delete('{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('destroy');
    });
    Route::prefix('guide-types')->name('guide-types.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [GuideTypeController::class, 'index'])->middleware('permission:guide_types.view')->name('index');
        Route::get('create', [GuideTypeController::class, 'create'])->middleware('permission:guide_types.create')->name('create');
        Route::post('/', [GuideTypeController::class, 'store'])->middleware('permission:guide_types.create')->name('store');
        Route::get('{guideType}', [GuideTypeController::class, 'show'])->middleware('permission:guide_types.view')->name('show');
        Route::get('{guideType}/edit', [GuideTypeController::class, 'edit'])->middleware('permission:guide_types.update')->name('edit');
        Route::put('{guideType}', [GuideTypeController::class, 'update'])->middleware('permission:guide_types.update')->name('update');
        Route::delete('{guideType}', [GuideTypeController::class, 'destroy'])->middleware('permission:guide_types.delete')->name('destroy');
    });
    Route::prefix('transport-types')->name('transport-types.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [TransportTypeController::class, 'index'])->middleware('permission:transport_types.view')->name('index');
        Route::get('create', [TransportTypeController::class, 'create'])->middleware('permission:transport_types.create')->name('create');
        Route::post('/', [TransportTypeController::class, 'store'])->middleware('permission:transport_types.create')->name('store');
        Route::get('{transportType}', [TransportTypeController::class, 'show'])->middleware('permission:transport_types.view')->name('show');
        Route::get('{transportType}/edit', [TransportTypeController::class, 'edit'])->middleware('permission:transport_types.update')->name('edit');
        Route::put('{transportType}', [TransportTypeController::class, 'update'])->middleware('permission:transport_types.update')->name('update');
        Route::delete('{transportType}', [TransportTypeController::class, 'destroy'])->middleware('permission:transport_types.delete')->name('destroy');
    });
    Route::prefix('activity-types')->name('activity-types.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [ActivityTypeController::class, 'index'])->middleware('permission:activity_types.view')->name('index');
        Route::get('create', [ActivityTypeController::class, 'create'])->middleware('permission:activity_types.create')->name('create');
        Route::post('/', [ActivityTypeController::class, 'store'])->middleware('permission:activity_types.create')->name('store');
        Route::get('{activityType}', [ActivityTypeController::class, 'show'])->middleware('permission:activity_types.view')->name('show');
        Route::get('{activityType}/edit', [ActivityTypeController::class, 'edit'])->middleware('permission:activity_types.update')->name('edit');
        Route::put('{activityType}', [ActivityTypeController::class, 'update'])->middleware('permission:activity_types.update')->name('update');
        Route::delete('{activityType}', [ActivityTypeController::class, 'destroy'])->middleware('permission:activity_types.delete')->name('destroy');
    });
    Route::prefix('datatables')->name('datatables.')->middleware('companyApproved')->group(function (): void {
        Route::get('audits', [AdminDataTableController::class, 'audits'])->name('audits');
        Route::get('categories', [AdminDataTableController::class, 'categories'])->middleware('permission:categories.view')->name('categories');
        Route::get('guide-types', [AdminDataTableController::class, 'guideTypes'])->middleware('permission:guide_types.view')->name('guide-types');
        Route::get('transport-types', [AdminDataTableController::class, 'transportTypes'])->middleware('permission:transport_types.view')->name('transport-types');
        Route::get('activity-types', [AdminDataTableController::class, 'activityTypes'])->middleware('permission:activity_types.view')->name('activity-types');
    });
    Route::prefix('users')->name('users.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('create', [UserController::class, 'create'])->middleware('permission:users.create')->name('create');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::get('{user}', [UserController::class, 'show'])->middleware('permission:users.view')->withTrashed()->name('show');
        Route::get('{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('update');
        Route::patch('{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:users.edit')->name('toggle-status');
        Route::get('{user}/change-password', [UserController::class, 'changePasswordForm'])->middleware('permission:users.change-password')->name('change-password.form');
        Route::patch('{user}/change-password', [UserController::class, 'changePassword'])->middleware('permission:users.change-password')->name('change-password');
        Route::get('{user}/roles', [UserController::class, 'rolesForm'])->middleware('permission:users.assign-roles')->name('roles.form');
        Route::patch('{user}/assign-roles', [UserController::class, 'assignRoles'])->middleware('permission:users.assign-roles')->name('assign-roles');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
        Route::patch('{user}/restore', [UserController::class, 'restore'])->middleware('permission:users.restore')->name('restore');
    });
    Route::prefix('roles')->name('roles.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('index');
        Route::get('create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('create');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('store');
        Route::get('{role}', [RoleController::class, 'show'])->middleware('permission:roles.view')->name('show');
        Route::get('{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('edit');
        Route::put('{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('update');
        Route::get('{role}/permissions', [RoleController::class, 'permissionsForm'])->middleware('permission:roles.assign-permissions')->name('permissions.form');
        Route::patch('{role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:roles.assign-permissions')->name('permissions');
        Route::delete('{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
    });
    Route::prefix('permissions')->name('permissions.')->middleware('companyApproved')->group(function (): void {
        Route::get('/', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('index');
        Route::get('create', [PermissionController::class, 'create'])->middleware('permission:permissions.create')->name('create');
        Route::post('/', [PermissionController::class, 'store'])->middleware('permission:permissions.create')->name('store');
        Route::get('{permission}', [PermissionController::class, 'show'])->middleware('permission:permissions.view')->name('show');
        Route::get('{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:permissions.edit')->name('edit');
        Route::match(['put', 'patch'], '{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.edit')->name('update');
        Route::delete('{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('destroy');
    });
});
