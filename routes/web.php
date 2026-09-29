<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\ChatbotAdminController;
use App\Http\Controllers\Admin\CompanyProfileController;
use App\Http\Controllers\Admin\CompradorController;
use App\Http\Controllers\Admin\ConstructionProgressController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\ImportarViviendasController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentPlanController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectSettingsController;
use App\Http\Controllers\Admin\SolicitudDeVisorController;
use App\Http\Controllers\Admin\StrategicAnalysisController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypologyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ViewerEventController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DatosPersonalesController;
use App\Http\Controllers\DeveloperDirectoryController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\McpServerController;
use App\Http\Controllers\MiInversionController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\UnitPdfController;
use App\Http\Controllers\ViewerController;
use App\Support\Version;
use Illuminate\Support\Facades\Route;

// Version desplegada. Hermana de /up (el health check de Laravel): sirve para
// saber desde fuera que hay publicado en cada entorno, sin entrar por SSH.
Route::get('/version', function () {
    return response()->json(Version::all())
        ->header('Cache-Control', 'no-store');
})->name('version');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// LLMs.txt
Route::get('/llms.txt', [LlmsTxtController::class, 'show']);
Route::get('/llms-full.txt', [LlmsTxtController::class, 'full']);

// MCP Server
Route::get('/.well-known/mcp.json', [McpServerController::class, 'discover']);
Route::post('/mcp', [McpServerController::class, 'handle'])->middleware('throttle:mcp');

// Landing page
Route::get('/', [ViewerController::class, 'welcome'])->name('welcome');

// Dashboard route (Breeze redirects here after login)
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasAdminAccess()) {
        // If inmobiliaria without company profile, redirect to onboarding
        if ($user->isInmobiliaria() && ! $user->companyProfile) {
            return redirect()->route('onboarding.company');
        }

        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('viewer.index');
})->middleware('auth')->name('dashboard');

// Admin routes
Route::middleware(['auth', 'admin', 'onboarding'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // La papelera, antes del resource: si no, "papelera" encaja en {project}.
    Route::get('projects/papelera', [ProjectController::class, 'papelera'])->name('projects.papelera');
    Route::post('projects/{project}/restaurar', [ProjectController::class, 'restaurar'])
        ->withTrashed()
        ->name('projects.restaurar');
    Route::resource('projects', ProjectController::class);
    // El material con el que se monta el visor: la promotora lo entrega aqui
    // y el equipo lo recoge de aqui, en vez de por correo.
    Route::get('projects/{project}/material', [MaterialController::class, 'index'])->name('projects.material.index');
    Route::post('projects/{project}/material', [MaterialController::class, 'store'])->name('projects.material.store');
    Route::delete('projects/{project}/material/{material}', [MaterialController::class, 'destroy'])->name('projects.material.destroy');
    Route::get('projects/{project}/material/{material}/descargar', [MaterialController::class, 'descargar'])->name('projects.material.descargar');
    // La costura del reparto: la promotora avisa de que ha terminado lo suyo.
    Route::post('projects/{project}/pedir-visor', [SolicitudDeVisorController::class, 'pedir'])
        ->name('projects.visor.pedir');
    Route::delete('projects/{project}/pedir-visor', [SolicitudDeVisorController::class, 'retirar'])
        ->name('projects.visor.retirar');
    Route::get('visores-pendientes', [SolicitudDeVisorController::class, 'pendientes'])
        ->name('visores.pendientes');
    Route::post('projects/{project}/visor-montado', [SolicitudDeVisorController::class, 'marcarMontado'])
        ->name('projects.visor.montado');

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
    // Importar viviendas desde Excel o CSV. Antes del resource: si no, "import"
    // encaja en {unit} y Laravel busca una vivienda llamada asi.
    Route::get('projects/{project}/units/import', [ImportarViviendasController::class, 'create'])
        ->name('projects.units.import.create');
    Route::post('projects/{project}/units/import/analizar', [ImportarViviendasController::class, 'analizar'])
        ->name('projects.units.import.analizar');
    Route::post('projects/{project}/units/import/confirmar', [ImportarViviendasController::class, 'confirmar'])
        ->name('projects.units.import.confirmar');

    // Comprador de una vivienda y sus pagos. Antes del resource, o
    // "comprador" encajaria en {unit}.
    Route::get('projects/{project}/units/{unit}/comprador', [CompradorController::class, 'show'])
        ->name('projects.units.comprador');
    Route::post('projects/{project}/units/{unit}/comprador', [CompradorController::class, 'asignar'])
        ->name('projects.units.comprador.asignar');
    Route::delete('projects/{project}/units/{unit}/comprador', [CompradorController::class, 'desasignar'])
        ->name('projects.units.comprador.desasignar');
    Route::post('projects/{project}/units/{unit}/comprador/pagos', [CompradorController::class, 'registrarPago'])
        ->name('projects.units.comprador.pago');
    Route::delete('projects/{project}/units/{unit}/comprador/pagos/{payment}', [CompradorController::class, 'borrarPago'])
        ->name('projects.units.comprador.pago.borrar');

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

    // Location & POIs
    Route::get('projects/{project}/location', [LocationController::class, 'index'])->name('projects.location.index');
    Route::put('projects/{project}/location/coords', [LocationController::class, 'updateCoords'])->name('projects.location.updateCoords');
    Route::post('projects/{project}/location/pois', [LocationController::class, 'storePoi'])->name('projects.location.storePoi');
    Route::put('projects/{project}/location/pois/{poi}', [LocationController::class, 'updatePoi'])->name('projects.location.updatePoi');
    Route::delete('projects/{project}/location/pois/{poi}', [LocationController::class, 'destroyPoi'])->name('projects.location.destroyPoi');

    // Users
    Route::resource('users', UserController::class)->except('show')->parameters(['users' => 'editUser']);
    Route::post('users/{user}/assign-projects', [UserController::class, 'assignProjects'])->name('users.assignProjects');

    // Notifications
    Route::get('notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::get('notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index')->middleware('feature:analytics');
    Route::get('analytics/data', [AnalyticsController::class, 'data'])->name('analytics.data')->middleware('feature:analytics');

    // Chatbot Admin
    Route::get('chatbot', [ChatbotAdminController::class, 'index'])->name('chatbot.index')->middleware('feature:chatbot');
    Route::get('chatbot/{conversation}', [ChatbotAdminController::class, 'show'])->name('chatbot.show')->middleware('feature:chatbot');
    Route::delete('chatbot/{conversation}', [ChatbotAdminController::class, 'destroy'])->name('chatbot.destroy')->middleware('feature:chatbot');

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

    // Company Profile (inmobiliaria own profile)
    Route::get('company-profile', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
    Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');

    // Superadmin: manage all companies
    Route::get('companies', [CompanyProfileController::class, 'index'])->name('companies.index');
    Route::get('companies/{company}/edit', [CompanyProfileController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyProfileController::class, 'update'])->name('companies.update');

    // Superadmin: subscriptions overview
    Route::get('subscriptions', [SubscriptionController::class, 'companies'])->name('subscriptions.index');
    Route::patch('companies/{company}/plan', [SubscriptionController::class, 'updatePlan'])->name('companies.update-plan');

    // Subscription / Billing (inmobiliaria own)
    Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::post('subscription/portal', [SubscriptionController::class, 'portal'])->name('subscription.portal');
    Route::post('subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscription.change-plan');

    // Webhooks
    Route::resource('webhooks', WebhookController::class)->except('show')->middleware('feature:api_access');
    Route::get('webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries'])->name('webhooks.deliveries');

    // Blog
    Route::resource('blog/posts', BlogPostController::class)->names('blog.posts');
    Route::resource('blog/categories', BlogCategoryController::class)->except(['create', 'show', 'edit'])->names('blog.categories');
});

// Las paginas legales. Tres rutas con nombre, una por pagina, para que
// route('legal.privacidad') exista y un enlace roto lo diga al pintar.
foreach (LegalController::PAGINAS as $pagina) {
    Route::get("/legal/{$pagina}", LegalController::class)
        ->defaults('pagina', $pagina)
        ->name("legal.{$pagina}");
}

// Stripe Webhook (no CSRF, no auth)
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Registro SaaS
Route::middleware('guest')->group(function () {
    Route::get('register/business', [OnboardingController::class, 'showRegistrationForm'])->name('register.business');
    Route::post('register/business', [OnboardingController::class, 'register'])
        ->middleware('throttle:acceso');
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
    Route::get('/projects/{project:slug}/files/{fileType}', [ProjectApiController::class, 'serveFile']);
    Route::get('/projects/{project:slug}/units', [ProjectApiController::class, 'units']);
    Route::get('/units/{unit}/floor-plan', [ProjectApiController::class, 'serveFloorPlan']);
    Route::get('/projects/{project:slug}/gallery/{image}', [ProjectApiController::class, 'serveGalleryImage']);
    Route::get('/projects/{project:slug}/construction/{image}', [ConstructionProgressController::class, 'serveImage'])->name('api.construction.image');
    Route::post('/viewer-events', [ViewerEventController::class, 'store'])->middleware('throttle:eventos');

    // Chatbot
    Route::post('/projects/{project:slug}/chat', [ChatbotController::class, 'sendMessage'])
        ->middleware('throttle:chatbot');
    Route::post('/projects/{project:slug}/chat/lead', [ChatbotController::class, 'captureLead'])
        ->middleware('throttle:chatbot');
});

// Embeddable widget
Route::get('/embed/{slug}', [EmbedController::class, 'show'])->name('embed.show');

// Public Property Portal
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('/search', [PortalController::class, 'search'])->name('search');
    Route::get('/api/locations', [PortalController::class, 'locations'])->name('api.locations');
    Route::get('/api/map-projects', [PortalController::class, 'mapProjects'])->name('api.map-projects');
});

// Blog (public, outside portal prefix for cleaner URLs)
Route::get('/portal/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/portal/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/portal/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/portal/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Public viewer
Route::get('/projects', [ViewerController::class, 'index'])->name('viewer.index');
Route::get('/projects/{project:slug}/info', [ViewerController::class, 'landing'])->name('viewer.landing');
Route::get('/projects/{project:slug}/units/{unit}/pdf', [UnitPdfController::class, 'generate'])->name('viewer.unit.pdf');
Route::get('/projects/{project:slug}/units/{unit}/payment-schedule', [UnitPdfController::class, 'paymentSchedule'])->name('viewer.payment-schedule.pdf');
Route::get('/projects/{project:slug}/units/{unit}/investment', [UnitPdfController::class, 'investmentReport'])->name('viewer.investment.pdf');
Route::post('/projects/{project:slug}/inquiry', [InquiryController::class, 'store'])
    ->middleware('throttle:consultas')
    ->name('viewer.inquiry');
Route::get('/projects/{project:slug}/units/{unit}', [ViewerController::class, 'unitDetail'])->name('viewer.unit.detail');
Route::get('/projects/{project:slug}', [ViewerController::class, 'show'])->name('viewer.show');

// Portal del comprador: quien ya compro ve como va su obra y sus pagos.
// Solo pide sesion, no rol de administracion.
Route::middleware('auth')->group(function () {
    Route::get('/mi-inversion', [MiInversionController::class, 'index'])->name('mi-inversion.index');
    Route::get('/mi-inversion/{unit}', [MiInversionController::class, 'show'])->name('mi-inversion.show');
});

// Profile (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Lo que la politica de privacidad promete: llevarse los datos desde el panel.
    Route::get('/profile/datos', [DatosPersonalesController::class, 'exportar'])->name('profile.exportar');
});

require __DIR__.'/auth.php';

// Como va la maquina por dentro. Detras de token; sin token, no existe.
Route::get('/salud', SaludController::class)->name('salud');
