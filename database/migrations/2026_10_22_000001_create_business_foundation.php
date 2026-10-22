<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('phone')->nullable()->unique();
            $t->timestamp('phone_verified_at')->nullable();
            $t->string('locale', 5)->default('en');
            $t->boolean('is_platform_admin')->default(false);
        });
        Schema::create('businesses', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type');
            $t->string('locale', 5)->default('bn');
            $t->string('country', 2)->default('BD');
            $t->string('currency', 3)->default('BDT');
            $t->date('free_until');
            $t->timestamps();
        });
        Schema::create('business_user', function (Blueprint $t) {
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role')->default('owner');
            $t->unique(['business_id', 'user_id']);
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->string('name');
            $t->timestamps();
            $t->index(['business_id', 'created_at']);
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->string('key');
            $t->json('value')->nullable();
            $t->timestamps();
            $t->unique(['business_id', 'key']);
            $t->index(['business_id', 'created_at']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('event');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip', 45)->nullable();
            $t->text('device')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'settings', 'branches', 'business_user', 'businesses'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['phone', 'phone_verified_at', 'locale', 'is_platform_admin']));
    }
};
