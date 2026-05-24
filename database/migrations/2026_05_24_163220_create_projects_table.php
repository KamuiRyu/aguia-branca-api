<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['PLANEJADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO'])->default('PLANEJADO');
            $table->foreignId('idea_origin_id')->constrained('ideas')->onDelete('cascade');

            // Dados adicionais
            $table->string('responsible_team')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->decimal('expected_roi', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
