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
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->string('sector')->nullable();
            $table->string('expected_impact')->nullable();
            $table->string('urgency')->nullable();
            $table->enum('status', ['PENDENTE_ANALISE', 'EM_ANALISE', 'APROVADA', 'REPROVADA', 'EM_PROJETO'])->default('PENDENTE_ANALISE');

            // Relação com Usuário (Autor)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('author_name')->nullable();
            $table->string('author_registration_id')->nullable();

            // Relação com Gestor (Avaliador)
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('manager_responsible_name')->nullable();
            $table->text('evaluation_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
