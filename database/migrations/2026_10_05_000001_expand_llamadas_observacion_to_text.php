<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas', function ($table): void {
            $table->text('observacion')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberately irreversible: reducing TEXT to VARCHAR(255) could
        // truncate existing observations, which is not a safe rollback.
    }
};
