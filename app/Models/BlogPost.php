<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'title_en',
        'slug',
        'excerpt',
        'excerpt_en',
        'body',
        'body_en',
        'featured_image_path',
        'featured_image_alt',
        'featured_image_alt_en',
        'category_id',
        'author_id',
        'status',
        'published_at',
        'meta_title',
        'meta_title_en',
        'meta_description',
        'meta_description_en',
        'meta_keywords',
        'is_featured',
        'reading_time_minutes',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'reading_time_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::saving(function (self $post) {
            $post->reading_time_minutes = max(1, (int) ceil(str_word_count(strip_tags($post->body ?? '')) / 200));
        });
    }

    // --- Relationships ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    // --- Scopes ---

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    // --- Translated Accessors ---

    public function getTranslatedTitleAttribute(): string
    {
        if (app()->getLocale() === 'en' && $this->title_en) {
            return $this->title_en;
        }
        return $this->title;
    }

    public function getTranslatedExcerptAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->excerpt_en) {
            return $this->excerpt_en;
        }
        return $this->excerpt;
    }

    public function getTranslatedBodyAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->body_en) {
            return $this->body_en;
        }
        return $this->body;
    }

    public function getTranslatedMetaTitleAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->meta_title_en) {
            return $this->meta_title_en;
        }
        return $this->meta_title;
    }

    public function getTranslatedMetaDescriptionAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->meta_description_en) {
            return $this->meta_description_en;
        }
        return $this->meta_description;
    }

    public function getTranslatedFeaturedImageAltAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->featured_image_alt_en) {
            return $this->featured_image_alt_en;
        }
        return $this->featured_image_alt;
    }

    // --- Helpers ---

    public function bodyHtml(): string
    {
        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        return $converter->convert($this->translated_body ?? '')->getContent();
    }

    public function relatedPosts(int $limit = 3)
    {
        return static::published()
            ->where('id', '!=', $this->id)
            ->where(function (Builder $q) {
                if ($this->category_id) {
                    $q->where('category_id', $this->category_id);
                }
                $tagIds = $this->tags()->pluck('blog_tags.id');
                if ($tagIds->isNotEmpty()) {
                    $q->orWhereHas('tags', fn ($tq) => $tq->whereIn('blog_tags.id', $tagIds));
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
