<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'status',
        'notes',
        'last_contacted_at',
    ];

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }
}
