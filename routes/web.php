<?php

use App\Http\Controllers\Web\AdminBrandingController;
use App\Http\Controllers\Web\AdminContactController;
use App\Http\Controllers\Web\AdminContactGroupController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDonationController;
use App\Http\Controllers\Web\AdminEventController;
use App\Http\Controllers\Web\AdminSmsController;
use App\Http\Controllers\Web\AdminTeamController;
use App\Http\Controllers\Web\ImpersonationController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\SuperPlansController;
use App\Http\Controllers\Web\SuperRolesController;
use App\Http\Controllers\Web\SuperTenantController;
use App\Http\Controllers\Web\SuperWebhookController;
use App\Support\Permissions as P;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Stop-impersonation must be accessible while impersonating (target user may not have super rights).
Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->middleware('auth')->name('impersonate.stop');

// Every route below is gated by an explicit `can:...` permission at the route level,
// so `auth` alone is sufficient — role-based access falls out of the permission map.
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->middleware('can:' . P::DASHBOARD_VIEW)->name('dashboard');

    Route::get('/donations', [AdminDonationController::class, 'index'])->middleware('can:' . P::DONATIONS_VIEW)->name('donations.index');
    Route::get('/donations/export.csv', [AdminDonationController::class, 'exportCsv'])->middleware('can:' . P::DONATIONS_VIEW)->name('donations.export.csv');
    Route::get('/donations/export.pdf', [AdminDonationController::class, 'exportPdf'])->middleware('can:' . P::DONATIONS_VIEW)->name('donations.export.pdf');
    Route::post('/donations', [AdminDonationController::class, 'store'])->middleware(['can:' . P::DONATIONS_CREATE, 'writable'])->name('donations.store');
    Route::post('/donations/verify-pending', [AdminDonationController::class, 'autoVerify'])->middleware('can:' . P::DONATIONS_VERIFY)->name('donations.auto-verify');
    Route::post('/donations/{donation}/verify', [AdminDonationController::class, 'verify'])->middleware('can:' . P::DONATIONS_VERIFY)->name('donations.verify');

    Route::get('/sms', fn () => redirect()->route('admin.sms.notifications'));
    Route::get('/sms/notifications', fn () => app(AdminSmsController::class)->show('notifications'))->middleware('can:' . P::SMS_NOTIFICATIONS_VIEW)->name('sms.notifications');
    Route::get('/sms/invitations', fn () => app(AdminSmsController::class)->show('invitations'))->middleware('can:' . P::SMS_INVITATIONS_VIEW)->name('sms.invitations');
    Route::get('/sms/post', fn () => app(AdminSmsController::class)->show('post'))->middleware('can:' . P::SMS_POST_VIEW)->name('sms.post');
    Route::get('/sms/logs', [AdminSmsController::class, 'logs'])->middleware('can:' . P::SMS_LOGS_VIEW)->name('sms.logs');

    Route::get('/contacts', [AdminContactController::class, 'index'])->middleware('can:' . P::CONTACTS_VIEW)->name('contacts.index');
    Route::post('/contacts', [AdminContactController::class, 'store'])->middleware(['can:' . P::CONTACTS_MANAGE, 'writable'])->name('contacts.store');
    Route::post('/contacts/import', [AdminContactController::class, 'import'])->middleware(['can:' . P::CONTACTS_IMPORT, 'writable'])->name('contacts.import');
    Route::post('/contacts/{contact}', [AdminContactController::class, 'update'])->middleware(['can:' . P::CONTACTS_MANAGE, 'writable'])->name('contacts.update');
    Route::post('/contacts/{contact}/delete', [AdminContactController::class, 'destroy'])->middleware(['can:' . P::CONTACTS_MANAGE, 'writable'])->name('contacts.destroy');

    Route::get('/contact-groups', [AdminContactGroupController::class, 'index'])->middleware('can:' . P::CONTACTS_VIEW)->name('contact-groups.index');
    Route::post('/contact-groups', [AdminContactGroupController::class, 'store'])->middleware(['can:' . P::CONTACTS_MANAGE, 'writable'])->name('contact-groups.store');
    Route::post('/contact-groups/{group}', [AdminContactGroupController::class, 'update'])->middleware(['can:' . P::CONTACTS_MANAGE, 'writable'])->name('contact-groups.update');
    Route::post('/contact-groups/{group}/delete', [AdminContactGroupController::class, 'destroy'])->middleware(['can:' . P::CONTACTS_MANAGE, 'writable'])->name('contact-groups.destroy');
    Route::post('/sms', [AdminSmsController::class, 'send'])->middleware('writable')->name('sms.send'); // permission checked in controller by scope

    Route::get('/team', [AdminTeamController::class, 'index'])->middleware('can:' . P::TEAM_VIEW)->name('team.index');
    Route::post('/team', [AdminTeamController::class, 'store'])->middleware('can:' . P::TEAM_CREATE)->name('team.store');
    Route::post('/team/{user_id}/delete', [AdminTeamController::class, 'destroy'])->middleware('can:' . P::TEAM_DELETE)->name('team.destroy');

    Route::get('/events', [AdminEventController::class, 'index'])->middleware('can:' . P::EVENTS_VIEW)->name('events.index');
    Route::post('/events/funeral-info', [AdminEventController::class, 'updateFuneralInfo'])->middleware('can:' . P::EVENTS_MANAGE)->name('events.funeral-info');
    Route::post('/events', [AdminEventController::class, 'store'])->middleware('can:' . P::EVENTS_MANAGE)->name('events.store');
    Route::post('/events/{event}', [AdminEventController::class, 'update'])->middleware('can:' . P::EVENTS_MANAGE)->name('events.update');
    Route::post('/events/{event}/delete', [AdminEventController::class, 'destroy'])->middleware('can:' . P::EVENTS_MANAGE)->name('events.destroy');
});

Route::middleware(['auth', 'super'])->prefix('super')->name('super.')->group(function () {
    Route::get('/', [SuperTenantController::class, 'overview'])->middleware('can:' . P::PLATFORM_OVERVIEW)->name('overview');

    Route::get('/tenants', [SuperTenantController::class, 'index'])->middleware('can:' . P::TENANTS_VIEW)->name('tenants.index');
    Route::get('/tenants/create', [SuperTenantController::class, 'create'])->middleware('can:' . P::TENANTS_CREATE)->name('tenants.create');
    Route::post('/tenants', [SuperTenantController::class, 'store'])->middleware('can:' . P::TENANTS_CREATE)->name('tenants.store');
    Route::post('/tenants/clear-switch', [SuperTenantController::class, 'clearSwitch'])->middleware('can:' . P::TENANTS_SWITCH)->name('tenants.clear-switch');
    Route::get('/tenants/{tenant}/edit', [SuperTenantController::class, 'edit'])->middleware('can:' . P::TENANTS_EDIT)->name('tenants.edit');
    Route::post('/tenants/{tenant}', [SuperTenantController::class, 'update'])->middleware('can:' . P::TENANTS_EDIT)->name('tenants.update');
    Route::post('/tenants/{tenant}/delete', [SuperTenantController::class, 'destroy'])->middleware('can:' . P::TENANTS_DELETE)->name('tenants.destroy');
    Route::post('/tenants/{tenant}/switch', [SuperTenantController::class, 'switchTenant'])->middleware('can:' . P::TENANTS_SWITCH)->name('tenants.switch');
    Route::post('/tenants/{tenant}/archive', [SuperTenantController::class, 'archiveNow'])->middleware('can:' . P::TENANTS_EDIT)->name('tenants.archive');
    Route::post('/tenants/{tenant}/unarchive', [SuperTenantController::class, 'unarchive'])->middleware('can:' . P::TENANTS_EDIT)->name('tenants.unarchive');

    Route::get('/users', [SuperTenantController::class, 'users'])->middleware('can:' . P::USERS_VIEW)->name('users');
    Route::post('/users', [SuperTenantController::class, 'storeUser'])->middleware('can:' . P::USERS_CREATE)->name('users.store');
    Route::post('/users/{user}/delete', [SuperTenantController::class, 'deleteUser'])->middleware('can:' . P::USERS_DELETE)->name('users.delete');
    Route::post('/users/{user_id}/impersonate', [ImpersonationController::class, 'start'])->middleware('can:' . P::USERS_IMPERSONATE)->name('users.impersonate');

    Route::get('/branding', [AdminBrandingController::class, 'edit'])->middleware('can:' . P::BRANDING_VIEW)->name('branding.edit');
    Route::post('/branding', [AdminBrandingController::class, 'update'])->middleware('can:' . P::BRANDING_EDIT)->name('branding.update');

    Route::get('/roles', [SuperRolesController::class, 'index'])->middleware('can:' . P::ROLES_VIEW)->name('roles.index');
    Route::post('/roles/{role}', [SuperRolesController::class, 'update'])->middleware('can:' . P::ROLES_EDIT)->name('roles.update');
    Route::post('/roles/{role}/reset', [SuperRolesController::class, 'reset'])->middleware('can:' . P::ROLES_EDIT)->name('roles.reset');

    Route::get('/plans', [SuperPlansController::class, 'index'])->middleware('can:' . P::PLANS_MANAGE)->name('plans.index');
    Route::post('/plans', [SuperPlansController::class, 'store'])->middleware('can:' . P::PLANS_MANAGE)->name('plans.store');
    Route::post('/plans/{plan}', [SuperPlansController::class, 'update'])->middleware('can:' . P::PLANS_MANAGE)->name('plans.update');
    Route::post('/plans/{plan}/delete', [SuperPlansController::class, 'destroy'])->middleware('can:' . P::PLANS_MANAGE)->name('plans.destroy');

    Route::get('/webhooks', [SuperWebhookController::class, 'index'])->middleware('can:' . P::WEBHOOKS_VIEW)->name('webhooks.index');
});
