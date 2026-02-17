<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("projects", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("slug")->unique();
            $table->text("description")->nullable();
            $table->string("location")->nullable();
            $table->enum("status", ["draft", "published"])->default("draft");
            $table->string("thumbnail_path", 500)->nullable();
            $table->foreignId("created_by")->constrained("users")->cascadeOnDelete();
            $table->timestamps();

            $table->index("status");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("projects");
    }
};
