<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Project extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location',
        'status',
        'thumbnail_path',
        'created_by',
        'tagline',
        'total_floors',
        'estimated_delivery',
        'whatsapp_number',
        'whatsapp_message',
        'whatsapp_message_en',
        'contact_email',
        'analytics_id',
        'rental_yield_annual',
        'average_occupancy',
        'appreciation_rate_annual',
        'management_fee',
        'property_tax_rate',
        'avg_nightly_rate',
        'description_en',
        'tagline_en',
        'location_en',
        'latitude',
        'longitude',
        'chatbot_enabled',
        'chatbot_welcome_es',
        'chatbot_welcome_en',
        'chatbot_instructions',
        'viewer_requested_at',
        'viewer_requested_by',
    ];

    protected $casts = [
        'viewer_requested_at' => 'datetime',
        'estimated_delivery' => 'date',
        'total_floors' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'chatbot_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
                $original = $project->slug;
                $count = 1;
                // Con los de la papelera: el slug es unico en la tabla y el
                // borrado sigue ahi. Sin esto, el segundo "Residencial Bahia"
                // revienta contra la restriccion.
                while (static::withTrashed()->where('slug', $project->slug)->exists()) {
                    $project->slug = $original.'-'.$count++;
                }
            }
        });

        // Al borrar del todo -- desde la papelera caducada o desde donde sea --
        // se van los ficheros del disco y la cuota de la promotora se
        // recalcula. Aqui y no en cada sitio que borre, para que no se olvide
        // en ninguno. Las promotoras se cogen antes: al irse el proyecto se
        // van sus asignaciones y no habria a quien devolverle el sitio.
        static::forceDeleting(function (Project $project) {
            $project->promotorasAntesDeBorrar = $project->assignedAgencies()->get();
            Storage::deleteDirectory("projects/{$project->id}");
        });

        static::forceDeleted(function (Project $project) {
            foreach ($project->promotorasAntesDeBorrar ?? [] as $promotora) {
                $promotora->companyProfile?->recalculateStorage();
            }
        });
    }

    /** Las promotoras del proyecto, cogidas justo antes de borrarlo del todo. */
    public $promotorasAntesDeBorrar = null;

    /** Quien pidio que le montaran el visor. */
    public function solicitanteDelVisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_requested_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ProjectSetting::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function typologies(): HasMany
    {
        return $this->hasMany(UnitTypology::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function chatbotConversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(ProjectGalleryImage::class)->orderBy('sort_order');
    }

    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class)->orderBy('sort_order');
    }

    public function constructionPhases(): HasMany
    {
        return $this->hasMany(ConstructionPhase::class)->orderBy('sort_order');
    }

    public function constructionUpdates(): HasMany
    {
        return $this->hasMany(ConstructionUpdate::class)->orderByDesc('date');
    }

    public function pointsOfInterest(): HasMany
    {
        return $this->hasMany(PointOfInterest::class)->orderBy('sort_order');
    }

    /** Inmobiliarias assigned to this project */
    public function assignedAgencies(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    public function translatedDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'en' && $this->description_en
                ? $this->description_en
                : $this->description,
        );
    }

    public function translatedTagline(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'en' && $this->tagline_en
                ? $this->tagline_en
                : $this->tagline,
        );
    }

    public function translatedLocation(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'en' && $this->location_en
                ? $this->location_en
                : $this->location,
        );
    }

    public function translatedWhatsappMessage(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'en' && $this->whatsapp_message_en
                ? $this->whatsapp_message_en
                : ($this->whatsapp_message ?? __('landing.whatsapp_default_message', ['project' => $this->name])),
        );
    }

    public function getFileByType(string $type): ?ProjectFile
    {
        return $this->files()->where('file_type', $type)->where('upload_complete', true)->first();
    }

    protected function availableUnitsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->units()->where('status', 'available')->count(),
        );
    }

    public function scopePortalVisible($query)
    {
        return $query->where('status', 'public')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    protected function priceRange(): Attribute
    {
        return Attribute::make(
            get: function () {
                $min = $this->units()->where('status', 'available')->min('price');
                $max = $this->units()->where('status', 'available')->max('price');
                if (! $min) {
                    return null;
                }
                if ($min == $max) {
                    return 'USD '.number_format($min, 0, '.', ',');
                }

                return 'USD '.number_format($min, 0, '.', ',').' - '.number_format($max, 0, '.', ',');
            },
        );
    }
}
