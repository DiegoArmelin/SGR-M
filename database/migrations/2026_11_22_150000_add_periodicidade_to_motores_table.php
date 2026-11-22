<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Motores também passam a receber periodicidade percentual via CSV. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motores', function (Blueprint $table) {
            // Nullable preserva todos os motores já cadastrados.
            $table->decimal('periodicidade', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('motores', function (Blueprint $table) {
            $table->dropColumn('periodicidade');
        });
    }
};
