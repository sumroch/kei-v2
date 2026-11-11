<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the admin data tables (default sort + common filters) and the public API.
 */
return new class extends Migration
{
    private const INDEXES = [
        // Admin reservations: newest first, filtered by store and/or type.
        'reservations' => [['created_at'], ['store_id', 'created_at'], ['type_reservation', 'created_at']],
        // Admin + public photo lists: ordered by delivery date, filtered by store.
        'photos' => [['date'], ['store_id', 'date']],
        // Public API: active cars / slides of one site.
        'cars' => [['store_type', 'active']],
        'slides' => [['store_type', 'active', 'order_slide']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->index($columns);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->dropIndex($columns);
                }
            });
        }
    }
};
