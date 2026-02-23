<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'description',
        'description_en',
        'color',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    public function getTranslatedNameAttribute(): string
    {
        if (app()->getLocale() === 'en' && $this->name_en) {
            return $this->name_en;
        }
        return $this->name;
    }

    public function getTranslatedDescriptionAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->description_en) {
            return $this->description_en;
        }
        return $this->description;
    }

    public function getPublishedPostsCountAttribute(): int
    {
        return $this->posts()->where('status', 'published')->count();
    }
}
