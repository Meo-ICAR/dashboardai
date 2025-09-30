<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedChart extends Model
{
    protected $fillable = [
        'title',
        'description',
        'ai_configuration',
        'aiSql',
        'aiChart',
    ];

    protected $casts = [
        'ai_configuration' => 'array',
        'aiChart' => 'array',
    ];
}
