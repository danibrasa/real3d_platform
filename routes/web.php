<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CompanyProfileController;
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
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\StrategicAnalysisController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypologyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\DeveloperDirectoryController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\ViewerController;
use App\Http\Controllers\Api\ChatbotController;
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
    $user = auth()->user();
    if ($user->hasAdminAccess()) {
        // If inmobiliaria without company profile, redirect to onboarding
        if ($user->isInmobiliaria() && !$user->companyProfile) {
            return redirect()->route('onboarding.company');
        }
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('viewer.index');
})->middleware('auth')->name('dashboard');

// Admin routes
Route::middleware(['auth', 'admin', 'onboarding'])->prefix('admin')->name('admin.')->group(function () {
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
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index')->middleware('feature:analytics');
    Route::get('analytics/data', [AnalyticsController::class, 'data'])->name('analytics.data')->middleware('feature:analytics');

    // Currencies
    Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::put('currencies', [CurrencyController::class, 'update'])->name('currencies.update');

    // API Tokens
    Route::resource('api-tokens', ApiTokenController::class)->except('show')->middleware('feature:api_access');

    // Strategic Analysis
    Route::get('strategic-analysis', [StrategicAnalysisController::class, 'index'])->name('strategic-analysis.index');
    Route::get('strategic-analysis/pdf', [StrategicAnalysisController::class, 'downloadPdf'])->name('strategic-analysis.pdf');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Company Profile
    Route::get('company-profile', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
    Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');

    // Subscription / Billing
    Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::post('subscription/portal', [SubscriptionController::class, 'portal'])->name('subscription.portal');
    Route::post('subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscription.change-plan');

    // Webhooks
    Route::resource('webhooks', WebhookController::class)->except('show')->middleware('feature:api_access');
    Route::get('webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries'])->name('webhooks.deliveries');
});

// Stripe Webhook (no CSRF, no auth)
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Registro SaaS
Route::middleware('guest')->group(function () {
    Route::get('register/business', [OnboardingController::class, 'showRegistrationForm'])->name('register.business');
    Route::post('register/business', [OnboardingController::class, 'register']);
});

// Onboarding (auth, NO admin middleware)
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('company', [OnboardingController::class, 'showCompanyForm'])->name('company');
    Route::post('company', [OnboardingController::class, 'storeCompany'])->name('company.store');
    Route::get('plan', [OnboardingController::class, 'showPlanSelection'])->name('plan');
    Route::post('plan', [OnboardingController::class, 'selectPlan'])->name('plan.select');
    Route::get('complete', [OnboardingController::class, 'complete'])->name('complete');
});

// Developer Directory
Route::get('/developers', [DeveloperDirectoryController::class, 'index'])->name('directory.index');
Route::get('/developers/{developer:slug}', [DeveloperDirectoryController::class, 'show'])->name('directory.show');

// API routes (access control handled in controller)
Route::prefix('api')->group(function () {
    Route::get('/projects/{project:slug}', [ProjectApiController::class, 'show']);
    Route::get('/projects/{project}/files/{fileType}', [ProjectApiController::class, 'serveFile']);
    Route::get('/projects/{project:slug}/units', [ProjectApiController::class, 'units']);
    Route::get('/units/{unit}/floor-plan', [ProjectApiController::class, 'serveFloorPlan']);
    Route::get('/projects/{project}/gallery/{image}', [ProjectApiController::class, 'serveGalleryImage']);
    Route::get('/projects/{project}/construction/{image}', [ConstructionProgressController::class, 'serveImage'])->name('api.construction.image');
    Route::post('/viewer-events', [ViewerEventController::class, 'store']);

    // Chatbot
    Route::post('/projects/{project:slug}/chat', [ChatbotController::class, 'sendMessage'])
        ->middleware('throttle:chatbot');
    Route::post('/projects/{project:slug}/chat/lead', [ChatbotController::class, 'captureLead']);
});

// Embeddable widget
Route::get('/embed/{slug}', [EmbedController::class, 'show'])->name('embed.show');

// Public viewer
Route::get('/projects', [ViewerController::class, 'index'])->name('viewer.index');
Route::get('/projects/{project:slug}/info', [ViewerController::class, 'landing'])->name('viewer.landing');
Route::get('/projects/{project:slug}/units/{unit}/pdf', [UnitPdfController::class, 'generate'])->name('viewer.unit.pdf');
Route::get('/projects/{project:slug}/units/{unit}/payment-schedule', [UnitPdfController::class, 'paymentSchedule'])->name('viewer.payment-schedule.pdf');
Route::post('/projects/{project:slug}/inquiry', [InquiryController::class, 'store'])->name('viewer.inquiry');
Route::get('/projects/{project:slug}', [ViewerController::class, 'show'])->name('viewer.show');

// Profile (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
