<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('larastitial.tables.interstitials', 'interstitials');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            // Basic info
            $table->string('name')->unique();
            $table->string('title');

            // Type configuration
            $table->string('type')->default('modal'); // modal, full_page, inline
            $table->string('content_type')->default('database'); // blade_view, database, form

            // Content
            $table->text('content')->nullable();
            $table->string('blade_view')->nullable();

            // Trigger configuration
            $table->string('trigger_event')->nullable();
            $table->json('trigger_routes')->nullable();
            $table->timestamp('trigger_schedule_start')->nullable();
            $table->timestamp('trigger_schedule_end')->nullable();

            // Audience targeting
            $table->string('audience_type')->default('all'); // all, authenticated, guest, roles, custom
            $table->json('audience_roles')->nullable();
            $table->string('audience_condition')->nullable();

            // Frequency control
            $table->string('frequency')->default('once'); // always, once, once_per_session, every_x_days
            $table->unsignedInteger('frequency_days')->nullable();

            // Display settings
            $table->integer('priority')->default(0)->index();
            $table->json('cta_buttons')->nullable();
            $table->boolean('allow_dismiss')->default(true);
            $table->boolean('allow_dont_show_again')->default(false);
            $table->string('redirect_after')->nullable();
            $table->string('queue_behavior')->default('inherit'); // inherit, show_with_others, exclusive
            $table->string('inline_slot')->nullable()->index();

            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['is_active', 'priority']);
            $table->index(['is_active', 'type']);
            $table->index(['trigger_schedule_start', 'trigger_schedule_end']);
        });
    }

    public function down(): void
    {
        $tableName = config('larastitial.tables.interstitials', 'interstitials');
        Schema::dropIfExists($tableName);
    }
};
