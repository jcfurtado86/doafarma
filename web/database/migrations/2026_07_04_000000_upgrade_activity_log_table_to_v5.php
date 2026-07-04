<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades the activity_log table to the spatie/laravel-activitylog v5 schema.
 *
 * - Adds the attribute_changes column (stores tracked model changes).
 * - Drops the batch_uuid column (batch system removed in v5).
 * - Moves existing change data ("attributes" and "old" keys) from the
 *   properties column to the new attribute_changes column.
 */
return new class () extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->json('attribute_changes')->nullable()->after('causer_id');
            $table->dropColumn('batch_uuid');
        });

        DB::table('activity_log')
            ->whereNotNull('properties')
            ->orderBy('id')
            ->eachById(function (object $row): void {
                /** @var array<string, mixed> $properties */
                $properties = json_decode((string) $row->properties, true) ?? [];

                $changes   = array_intersect_key($properties, array_flip(['attributes', 'old']));
                $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

                DB::table('activity_log')->where('id', $row->id)->update([
                    'attribute_changes' => $changes === [] ? null : json_encode($changes),
                    'properties'        => $remaining === [] ? null : json_encode($remaining),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('activity_log')
            ->whereNotNull('attribute_changes')
            ->orderBy('id')
            ->eachById(function (object $row): void {
                /** @var array<string, mixed> $changes */
                $changes = json_decode((string) $row->attribute_changes, true) ?? [];

                /** @var array<string, mixed> $properties */
                $properties = json_decode((string) ($row->properties ?? ''), true) ?? [];

                $merged = array_merge($properties, $changes);

                DB::table('activity_log')->where('id', $row->id)->update([
                    'properties' => $merged === [] ? null : json_encode($merged),
                ]);
            });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn('attribute_changes');
            $table->uuid('batch_uuid')->nullable()->after('properties');
        });
    }
};
