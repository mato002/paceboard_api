<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_report_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'community_report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_verifications');
    }
};
