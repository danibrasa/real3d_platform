<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El material con el que el equipo monta el visor: planos, renders, 360, modelo.
 *
 * Hasta ahora llegaba por fuera -- correo, WhatsApp, un enlace de Drive -- y
 * no habia forma de saber que habia llegado ni de que faltaba. Es aparte de
 * project_files, que son los ficheros que el visor sirve: esto es lo que la
 * promotora entrega, y aquello lo que el equipo monta con ello.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('original_name');
            // Un fichero subido, o un enlace para lo que no cabe por el formulario.
            $table->string('storage_path')->nullable();
            $table->string('enlace', 500)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_material');
    }
};
