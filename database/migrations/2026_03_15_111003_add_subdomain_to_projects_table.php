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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('subdomain')->nullable()->unique()->after('domain');
            $table->string('dns_status')->nullable()->after('subdomain'); // null | pending | active | failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropColumn(['subdomain', 'dns_status']);
        });
    }
};
