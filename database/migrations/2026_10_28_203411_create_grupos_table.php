<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->integer('numero_ciclo');
            $table->integer('ano');
            $table->integer('numero_grupo');
            $table->boolean('abierto')->default(true);
            $table->unique(['curso_id', 'numero_ciclo', 'ano', 'numero_grupo']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};