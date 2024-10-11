<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create(WireChat::formatTableName('attachments'), function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // Adds 'attachable_id' and 'attachable_type' columns
            $table->string('file_path');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('url');
            $table->string('mime_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(WireChat::formatTableName('attachments'));
    }
};
