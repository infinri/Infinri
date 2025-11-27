<?php

declare(strict_types=1);

use App\Core\Database\Migration;
use App\Core\Database\Schema\Blueprint;

/**
 * Create Pages Table Migration
 * 
 * Creates the pages table for static content.
 */
class CreatePagesTable extends Migration
{
    public function up(): void
    {
        $this->schema()->create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        $this->schema()->drop('pages');
    }
}
