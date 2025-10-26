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
        // Index ads.is_active for fast filtering of active videos
        Schema::table('ads', function (Blueprint $table) {
            $table->index('is_active', 'idx_ads_is_active');
        });

        // Composite index to accelerate queries that search ad_views by user, ad and completed_at
        Schema::table('ad_views', function (Blueprint $table) {
            // user_id and ad_id are already indexed via foreignId, but composite index helps the common queries
            $table->index(['user_id', 'ad_id', 'completed_at'], 'idx_ad_views_user_ad_completed');
            // an additional index on (user_id, completed_at) benefits queries that find active views for a user
            $table->index(['user_id', 'completed_at'], 'idx_ad_views_user_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('idx_ads_is_active');
        });

        Schema::table('ad_views', function (Blueprint $table) {
            $table->dropIndex('idx_ad_views_user_ad_completed');
            $table->dropIndex('idx_ad_views_user_completed');
        });
    }
};
