<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Collected post list only. Tokens are NEVER stored here.
        Schema::create('collected_posts', function (Blueprint $table) {
            $table->id();
            $table->string('page_id')->index();
            $table->string('post_id');
            $table->text('message')->nullable();
            $table->dateTime('created_time')->nullable();
            $table->timestamps();
            $table->unique(['page_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collected_posts');
    }
};
