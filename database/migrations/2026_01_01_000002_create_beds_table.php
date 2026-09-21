<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('sector');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('sector');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
