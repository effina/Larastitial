<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('larastitial.tables.views', 'interstitial_views');
        $interstitialsTable = config('larastitial.tables.interstitials', 'interstitials');

        Schema::create($tableName, function (Blueprint $table) use ($interstitialsTable) {
            $table->id();
            $table->foreignId('interstitial_id')
                ->constrained($interstitialsTable)
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('action'); // viewed, dismissed, completed, dont_show_again
            $table->timestamp('viewed_at');
            $table->timestamps();

            // Composite indexes for frequency checking
            $table->index(['interstitial_id', 'user_id']);
            $table->index(['interstitial_id', 'session_id']);
            $table->index(['interstitial_id', 'user_id', 'action']);
            $table->index(['interstitial_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        $tableName = config('larastitial.tables.views', 'interstitial_views');
        Schema::dropIfExists($tableName);
    }
};
