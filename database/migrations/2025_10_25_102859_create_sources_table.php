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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('api_endpoint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        $sources = [
            [
                'name' => 'NewsAPI',
                'slug' => 'newsapi',
                'api_endpoint' => 'https://newsapi.org/v2',
                'is_active' => true,
            ],
            [
                'name' => 'The Guardian',
                'slug' => 'guardian',
                'api_endpoint' => 'https://content.guardianapis.com',
                'is_active' => true,
            ],
            [
                'name' => 'New York Times',
                'slug' => 'nytimes',
                'api_endpoint' => 'https://api.nytimes.com/svc',
                'is_active' => true,
            ],
        ];

        $timestamp = now();
        foreach ($sources as &$source) {
            $source['created_at'] = $timestamp;
            $source['updated_at'] = $timestamp;
        }

        DB::table('sources')->insert($sources);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
