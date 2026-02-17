<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectSettingsController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentPlanController;
use App\Http\Controllers\Admin\ConstructionProgressController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\StrategicAnalysisController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypologyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ViewerController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ViewerEventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UnitPdfController;
use Illuminate\Support\Facades\Route;

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Landing page
Route::get('/', [ViewerController::class, 'welcome'])->name('welcome');

// Dashboard route (Breeze redirects here after login)
Route::get('/dashboard', function () {
    return auth()->user()->hasAdminAccess()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('viewer.index');
})->middleware('auth')->name('dashboard');

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('projects', ProjectController::class);
    Route::put('projects/{project}/settings', [ProjectSettingsController::class, 'update'])
        ->name('projects.settings.update');

    // File uploads (chunked)
    Route::post('projects/{project}/upload/init', [FileUploadController::class, 'initUpload'])
        ->name('projects.upload.init');
    Route::post('projects/{project}/upload/chunk', [FileUploadController::class, 'uploadChunk'])
        ->name('projects.upload.chunk');
    Route::post('projects/{project}/upload/complete', [FileUploadController::class, 'completeUpload'])
        ->name('projects.upload.complete');

    // Typologies
    Route::resource('projects.typologies', UnitTypologyController::class)->except('show');

    // Units
    Route::patch('projects/{project}/units/{unit}/status', [UnitController::class, 'updateStatus'])
        ->name('projects.units.updateStatus');
    Route::put('projects/{project}/units/{unit}/bbox', [UnitController::class, 'updateBbox'])
        ->name('projects.units.updateBbox');
    Route::delete('projects/{project}/units/{unit}/bbox', [UnitController::class, 'clearBbox'])
        ->name('projects.units.clearBbox');
    Route::get('projects/{project}/unit-mapping', [UnitController::class, 'mapping'])
        ->name('projects.unit-mapping');
    Route::resource('projects.units', UnitController::class);

    // Inquiries
    Route::get('inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
    Route::get('inquiries/{inquiry}', [AdminInquiryController::class, 'show'])->name('inquiries.show');
    Route::patch('inquiries/{inquiry}/read', [AdminInquiryController::class, 'markRead'])->name('inquiries.markRead');
    Route::delete('inquiries/{inquiry}', [AdminInquiryController::class, 'destroy'])->name('inquiries.destroy');

    // Gallery
    Route::post('projects/{project}/gallery', [GalleryController::class, 'store'])->name('projects.gallery.store');
    Route::delete('projects/{project}/gallery/{image}', [GalleryController::class, 'destroy'])->name('projects.gallery.destroy');

    // Payment Plans
    Route::get('projects/{project}/payment-plans', [PaymentPlanController::class, 'index'])->name('projects.payment-plans.index');
    Route::post('projects/{project}/payment-plans', [PaymentPlanController::class, 'store'])->name('projects.payment-plans.store');
    Route::put('projects/{project}/payment-plans/{paymentPlan}', [PaymentPlanController::class, 'update'])->name('projects.payment-plans.update');
    Route::delete('projects/{project}/payment-plans/{paymentPlan}', [PaymentPlanController::class, 'destroy'])->name('projects.payment-plans.destroy');

    // Construction Progress
    Route::get('projects/{project}/construction', [ConstructionProgressController::class, 'index'])->name('projects.construction.index');
    Route::post('projects/{project}/construction/phases', [ConstructionProgressController::class, 'storePhase'])->name('projects.construction.phases.store');
    Route::put('projects/{project}/construction/phases/{phase}', [ConstructionProgressController::class, 'updatePhase'])->name('projects.construction.phases.update');
    Route::delete('projects/{project}/construction/phases/{phase}', [ConstructionProgressController::class, 'destroyPhase'])->name('projects.construction.phases.destroy');
    Route::post('projects/{project}/construction/updates', [ConstructionProgressController::class, 'storeUpdate'])->name('projects.construction.updates.store');
    Route::delete('projects/{project}/construction/updates/{update}', [ConstructionProgressController::class, 'destroyUpdate'])->name('projects.construction.updates.destroy');
    Route::get('projects/{project}/construction/images/{image}', [ConstructionProgressController::class, 'serveImage'])->name('projects.construction.image');

    // Users
    Route::resource('users', UserController::class)->except('show')->parameters(['users' => 'editUser']);
    Route::post('users/{user}/assign-projects', [UserController::class, 'assignProjects'])->name('users.assignProjects');

    // Notifications
    Route::get('notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::get('notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/data', [AnalyticsController::class, 'data'])->name('analytics.data');

    // Currencies
    Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::put('currencies', [CurrencyController::class, 'update'])->name('currencies.update');

    // Strategic Analysis
    Route::get('strategic-analysis', [StrategicAnalysisController::class, 'index'])->name('strategic-analysis.index');
    Route::get('strategic-analysis/pdf', [StrategicAnalysisController::class, 'downloadPdf'])->name('strategic-analysis.pdf');
});

// API routes (access control handled in controller)
Route::prefix('api')->group(function () {
    Route::get('/projects/{project:slug}', [ProjectApiController::class, 'show']);
    Route::get('/projects/{project}/files/{fileType}', [ProjectApiController::class, 'serveFile']);
    Route::get('/projects/{project:slug}/units', [ProjectApiController::class, 'units']);
    Route::get('/units/{unit}/floor-plan', [ProjectApiController::class, 'serveFloorPlan']);
    Route::get('/projects/{project}/gallery/{image}', [ProjectApiController::class, 'serveGalleryImage']);
    Route::get('/projects/{project}/construction/{image}', [ConstructionProgressController::class, 'serveImage'])->name('api.construction.image');
    Route::post('/viewer-events', [ViewerEventController::class, 'store']);
});

// Public viewer
Route::get('/projects', [ViewerController::class, 'index'])->name('viewer.index');
Route::get('/projects/{project:slug}/info', [ViewerController::class, 'landing'])->name('viewer.landing');
Route::get('/projects/{project:slug}/units/{unit}/pdf', [UnitPdfController::class, 'generate'])->name('viewer.unit.pdf');
Route::post('/projects/{project:slug}/inquiry', [InquiryController::class, 'store'])->name('viewer.inquiry');
Route::get('/projects/{project:slug}', [ViewerController::class, 'show'])->name('viewer.show');

// Profile (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
