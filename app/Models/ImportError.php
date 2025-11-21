<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'row_number',
        'row_data',
        'error_message',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}


