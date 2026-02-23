<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('excerpt_en')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_en')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('featured_image_alt_en')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_description_en')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('reading_time_minutes')->default(1);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
