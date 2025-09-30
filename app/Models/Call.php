<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero_chiamato',
        'data_inizio',
        'durata',
        'stato_chiamata',
        'esito',
        'utente',
        'company_id',
    ];

    protected $casts = [
        'data_inizio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
