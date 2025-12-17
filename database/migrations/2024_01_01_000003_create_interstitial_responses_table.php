<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('larastitial.tables.responses', 'interstitial_responses');
        $interstitialsTable = config('larastitial.tables.interstitials', 'interstitials');

        Schema::create($tableName, function (Blueprint $table) use ($interstitialsTable) {
            $table->id();
            $table->foreignId('interstitial_id')
                ->constrained($interstitialsTable)
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->json('data');
            $table->timestamps();

            $table->index(['interstitial_id', 'user_id']);
        });
    }

    public function down(): void
    {
        $tableName = config('larastitial.tables.responses', 'interstitial_responses');
        Schema::dropIfExists($tableName);
    }
};
