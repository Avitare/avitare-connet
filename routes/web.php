<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityProgressReportController;
use App\Http\Controllers\ActivityWeekController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanGroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequerimientoController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/mis-sitios', function () {
    return Inertia::render('Sites/Index');
})->middleware(['auth', 'verified'])->name('sites.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans/create-mine', [PlanController::class, 'createMine'])->name('plans.create-mine');
    Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
    Route::post('/plans/{plan}/approve', [PlanController::class, 'approve'])->name('plans.approve');
    Route::post('/plans/{plan}/close', [PlanController::class, 'close'])->name('plans.close');
    Route::post('/plans/{plan}/clone', [PlanController::class, 'clone'])->name('plans.clone');

    Route::post('/plans/{plan}/groups', [PlanGroupController::class, 'store'])->name('plan-groups.store');
    Route::delete('/plan-groups/{planGroup}', [PlanGroupController::class, 'destroy'])->name('plan-groups.destroy');

    Route::post('/plan-groups/{planGroup}/activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
    Route::post('/activities/{activity}/close', [ActivityController::class, 'close'])->name('activities.close');
    Route::post('/activities/{activity}/reopen', [ActivityController::class, 'reopen'])->name('activities.reopen');

    Route::patch('/activity-weeks/{activityWeek}/toggle', [ActivityWeekController::class, 'toggle'])->name('activity-weeks.toggle');

    Route::post('/activities/{activity}/progress-reports', [ActivityProgressReportController::class, 'store'])->name('activity-progress-reports.store');
    Route::get('/progress-report-attachments/{attachment}/download', [ActivityProgressReportController::class, 'downloadAttachment'])->name('progress-report-attachments.download');

    Route::post('/activities/{activity}/deliverable', [ActivityController::class, 'updateDeliverable'])->name('activities.deliverable.update');
    Route::delete('/activities/{activity}/deliverable', [ActivityController::class, 'destroyDeliverable'])->name('activities.deliverable.destroy');
    Route::get('/activities/{activity}/deliverable/download', [ActivityController::class, 'downloadDeliverable'])->name('activities.deliverable.download');

    Route::get('/requerimientos', [RequerimientoController::class, 'index'])->name('requerimientos.index');
    Route::post('/requerimientos', [RequerimientoController::class, 'store'])->name('requerimientos.store');
    Route::get('/requerimientos/bandeja', [RequerimientoController::class, 'inbox'])->name('requerimientos.inbox');
    Route::post('/requerimientos/{requerimiento}/corregir', [RequerimientoController::class, 'correct'])->name('requerimientos.correct');
    Route::post('/requerimientos/{requerimiento}/anular', [RequerimientoController::class, 'cancel'])->name('requerimientos.cancel');
    Route::post('/requerimientos/{requerimiento}/aprobar', [RequerimientoController::class, 'approve'])->name('requerimientos.approve');
    Route::post('/requerimientos/{requerimiento}/observar', [RequerimientoController::class, 'observe'])->name('requerimientos.observe');
    Route::post('/requerimientos/{requerimiento}/rechazar', [RequerimientoController::class, 'reject'])->name('requerimientos.reject');
    Route::post('/requerimientos/{requerimiento}/atender', [RequerimientoController::class, 'attend'])->name('requerimientos.attend');
    Route::get('/requerimiento-materials/{material}/image', [RequerimientoController::class, 'downloadMaterialImage'])->name('requerimiento-materials.image');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/crear', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/bandeja', [TicketController::class, 'inbox'])->name('tickets.inbox');
    Route::get('/tickets/adjuntos/{attachment}/descargar', [TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/comentarios', [TicketController::class, 'comment'])->name('tickets.comment');
    Route::post('/tickets/{ticket}/adjuntos', [TicketController::class, 'attachment'])->name('tickets.attachment');
    Route::post('/tickets/{ticket}/confirmar', [TicketController::class, 'confirm'])->name('tickets.confirm');
    Route::post('/tickets/{ticket}/calificar', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::post('/tickets/{ticket}/estado', [TicketController::class, 'status'])->name('tickets.status');
    Route::post('/tickets/{ticket}/resolver', [TicketController::class, 'resolve'])->name('tickets.resolve');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('areas', AreaController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');
});

require __DIR__.'/auth.php';
