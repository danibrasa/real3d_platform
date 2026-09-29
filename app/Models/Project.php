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
        'visor_estado',
        'visor_estado_en',
        'visor_asignado_a',
        'visor_objetivo',
        'visor_horas',
        'visor_aprobado_en',
        'visor_aprobado_por',
        'visor_comentario',
    ];

    protected $casts = [
        'viewer_requested_at' => 'datetime',
        'visor_estado_en' => 'datetime',
        'visor_objetivo' => 'date',
        // Sin el cast, en MySQL llega como texto y `=== $u->id` nunca casa: el
        // select de la cola no marcaba al asignado y cada "Guardar" lo borraba.
        'visor_asignado_a' => 'integer',
        'visor_aprobado_en' => 'datetime',
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

    /** Lo que la promotora entrega para que se monte el visor. */
    public function material(): HasMany
    {
        return $this->hasMany(MaterialDelProyecto::class);
    }

    /**
     * Por donde pasa un visor en la cola del equipo. "montado" solo se llega
     * dandolo por montado, que comprueba que hay algo montado.
     */
    public const VISOR_PEDIDO = 'pedido';

    public const VISOR_MONTADO = 'montado';

    public const ESTADOS_VISOR = ['pedido', 'en_preparacion', 'para_revisar', 'montado'];

    /** Quien de la promotora dio el visto bueno al visor. */
    public function aprobadorDelVisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visor_aprobado_por');
    }

    /** Quien del equipo lo esta montando. */
    public function montador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visor_asignado_a');
    }

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

    /**
     * La direccion publica de un fichero del visor, con su version.
     *
     * Estaba escrita a mano en cinco sitios, sin version: cada visita
     * volvia a pedir el modelo a la hora. Con ?v= la respuesta puede ser
     * inmutable un ano, y al reemplazar el fichero cambia la direccion.
     */
    public function urlDeFichero(string $tipo, ?string $tam = null): ?string
    {
        $fichero = $this->getFileByType($tipo);
        if (! $fichero) {
            return null;
        }

        // El modelo comprimido con Draco, cuando lo hay, es el que se sirve:
        // todos los que cargan GLB llevan DRACOLoader. Pedir 'original' lo
        // salta.
        if ($tipo === 'model_3d' && $tam === null && $fichero->tieneVariante('draco')) {
            $tam = 'draco';
        }
        if ($tam === 'original') {
            $tam = null;
        }

        $parametros = ['v' => $fichero->version()];
        if ($tam) {
            $parametros['tam'] = $tam;
        }
        if ($tipo === 'model_3d') {
            // El cargador escoge GLB o FBX por la extension del nombre.
            $parametros['f'] = $fichero->original_name;
        }

        return "/api/projects/{$this->slug}/files/{$tipo}?".http_build_query($parametros);
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
