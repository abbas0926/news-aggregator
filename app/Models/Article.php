<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = ['source_id', 'category_id', 'title', 'description',
                            'content', 'author', 'url', 'url_to_image', 'published_at'];


    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
