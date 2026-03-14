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
        Schema::create('allowed_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique(); // e.g. mbouutrecht.nl
            $table->string('note')->nullable(); // optionele omschrijving
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_domains');
    }
};
