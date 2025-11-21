<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'error_count',
        'batch_count',
        'completed_batches',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }
}


