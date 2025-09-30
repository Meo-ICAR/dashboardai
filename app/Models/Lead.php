<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'campagna',
        'lista',
        'ragione_sociale',
        'cognome',
        'nome',
        'telefono',
        'ultimo_operatore',
        'esito',
        'data_richiamo',
        'operatore_richiamo',
        'scadenza_anagrafica',
        'indirizzo1',
        'indirizzo2',
        'indirizzo3',
        'comune',
        'provincia',
        'cap',
        'regione',
        'paese',
        'email',
        'p_iva',
        'codice_fiscale',
        'telefono2',
        'telefono3',
        'telefono4',
        'sesso',
        'nota',
        'attivo',
        'altro1',
        'altro2',
        'altro3',
        'altro4',
        'altro5',
        'altro6',
        'altro7',
        'altro8',
        'altro9',
        'altro10',
        'chiamate',
        'ultima_chiamata',
        'creato_da',
        'durata_ultima_chiamata',
        'totale_durata_chiamate',
        'chiamate_giornaliere',
        'chiamate_mensili',
        'data_creazione',
        'company_id',
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'data_richiamo' => 'datetime',
        'scadenza_anagrafica' => 'datetime',
        'ultima_chiamata' => 'datetime',
        'data_creazione' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'chiamate' => 'integer',
        'chiamate_giornaliere' => 'integer',
        'chiamate_mensili' => 'integer',
    ];

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }
}
