<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_occupations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->timestamp('occupied_at');
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason')->nullable();
            $table->timestamps();

            $table->index(['bed_id', 'released_at']);
            $table->index(['patient_id', 'released_at']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX bed_occupations_active_bed_unique
             ON bed_occupations (bed_id) WHERE released_at IS NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX bed_occupations_active_patient_unique
             ON bed_occupations (patient_id) WHERE released_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_occupations');
    }
};
