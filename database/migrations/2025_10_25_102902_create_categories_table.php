<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });
        
        $categories = [
            ['name' => 'Business', 'slug' => 'business'],
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => 'Entertainment', 'slug' => 'entertainment'],
            ['name' => 'Health', 'slug' => 'health'],
            ['name' => 'Science', 'slug' => 'science'],
            ['name' => 'Sports', 'slug' => 'sports'],
            ['name' => 'Politics', 'slug' => 'politics'],
            ['name' => 'World', 'slug' => 'world'],
            ['name' => 'Environment', 'slug' => 'environment'],
            ['name' => 'Education', 'slug' => 'education'],
        ];

        $timestamp = now();
        foreach ($categories as &$category) {
            $category['created_at'] = $timestamp;
            $category['updated_at'] = $timestamp;
        }

        DB::table('categories')->insert($categories);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
