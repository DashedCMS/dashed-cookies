<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('dashed__cookies')) {
            Schema::create('dashed__cookies', function (Blueprint $table) {
                $table->id();
                $table->string('site_id');
                // Een naam met * is een patroon (_ga_*, _hjSession_*).
                $table->string('name');
                $table->string('provider')->nullable();
                // necessary, preferences, analytics, marketing of unclassified
                $table->string('category')->default('unclassified');
                $table->json('purpose')->nullable();
                $table->json('duration')->nullable();
                // cookie, local_storage of session_storage
                $table->string('type')->default('cookie');
                $table->string('privacy_url')->nullable();
                // builtin, scan of manual
                $table->string('source')->default('manual');
                $table->string('service_key')->nullable();
                // Met de hand aangepast: de synchronisatie overschrijft hem dan niet meer.
                $table->boolean('customized')->default(false);
                $table->boolean('visible')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique(['site_id', 'name', 'type']);
                $table->index(['site_id', 'category']);
            });
        }

        if (! Schema::hasTable('dashed__cookie_consents')) {
            Schema::create('dashed__cookie_consents', function (Blueprint $table) {
                $table->id();
                $table->uuid('consent_id');
                $table->string('site_id')->nullable();
                $table->string('locale', 10)->nullable();
                // accept_all, reject_all, custom of withdraw
                $table->string('action');
                $table->json('categories');
                $table->unsignedInteger('version');
                // Gepseudonimiseerd: HMAC van het IP-adres met de APP_KEY.
                $table->string('ip_hash', 64)->nullable();
                $table->string('user_agent', 200)->nullable();
                $table->string('url', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('consent_id');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__cookie_consents');
        Schema::dropIfExists('dashed__cookies');
    }
};
