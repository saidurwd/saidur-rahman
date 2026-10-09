<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->boolean('starred')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
