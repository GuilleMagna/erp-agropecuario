<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reintegros_iva MODIFY estado ENUM('pendiente','presentado','acreditado','rechazado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reintegros_iva MODIFY estado ENUM('pendiente','acreditado','rechazado') NOT NULL DEFAULT 'pendiente'");
    }
};
