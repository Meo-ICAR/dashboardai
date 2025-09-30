<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lead::query();

        $likeFilterable = [
            'legacy_id','campagna','lista','ragione_sociale','cognome','nome','telefono','ultimo_operatore','esito',
            'operatore_richiamo','indirizzo1','indirizzo2','indirizzo3','comune','provincia','cap','regione','paese',
            'email','p_iva','codice_fiscale','telefono2','telefono3','telefono4','sesso','nota','altro1','altro2',
            'altro3','altro4','altro5','altro6','altro7','altro8','altro9','altro10','creato_da','durata_ultima_chiamata',
            'totale_durata_chiamate','company_id'
        ];
        $eqFilterable = ['attivo','chiamate','chiamate_giornaliere','chiamate_mensili'];

        foreach ($likeFilterable as $column) {
            if ($request->filled($column)) {
                $query->where($column, 'like', '%' . $request->string($column) . '%');
            }
        }
        foreach ($eqFilterable as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->input($column));
            }
        }

        // Generic created_at range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        // data_richiamo range: from_data_richiamo / to_data_richiamo
        if ($request->filled('from_data_richiamo')) {
            $query->where('data_richiamo', '>=', $request->date('from_data_richiamo'));
        }
        if ($request->filled('to_data_richiamo')) {
            $query->where('data_richiamo', '<=', $request->date('to_data_richiamo'));
        }

        // ultima_chiamata range: from_ultima_chiamata / to_ultima_chiamata
        if ($request->filled('from_ultima_chiamata')) {
            $query->where('ultima_chiamata', '>=', $request->date('from_ultima_chiamata'));
        }
        if ($request->filled('to_ultima_chiamata')) {
            $query->where('ultima_chiamata', '<=', $request->date('to_ultima_chiamata'));
        }

        // data_creazione range: from_data_creazione / to_data_creazione
        if ($request->filled('from_data_creazione')) {
            $query->where('data_creazione', '>=', $request->date('from_data_creazione'));
        }
        if ($request->filled('to_data_creazione')) {
            $query->where('data_creazione', '<=', $request->date('to_data_creazione'));
        }

        if ($request->filled('order_by')) {
            $orderBy = in_array($request->string('order_by'), array_merge($likeFilterable, $eqFilterable, ['id', 'created_at']))
                ? $request->string('order_by')
                : 'id';
            $direction = $request->string('order_dir', 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orderBy, $direction);
        } else {
            $query->latest('id');
        }

        $perPage = (int) $request->integer('per_page', 15);
        return $query->paginate(max(1, min(100, $perPage)))->withQueryString();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'legacy_id' => ['nullable','string','max:20'],
            'campagna' => ['nullable','string','max:100'],
            'lista' => ['nullable','string','max:100'],
            'ragione_sociale' => ['nullable','string','max:255'],
            'cognome' => ['nullable','string','max:100'],
            'nome' => ['nullable','string','max:100'],
            'telefono' => ['nullable','string','max:20'],
            'ultimo_operatore' => ['nullable','string','max:255'],
            'esito' => ['nullable','string','max:100'],
            'data_richiamo' => ['nullable','date'],
            'operatore_richiamo' => ['nullable','string','max:255'],
            'scadenza_anagrafica' => ['nullable','date'],
            'indirizzo1' => ['nullable','string','max:255'],
            'indirizzo2' => ['nullable','string','max:255'],
            'indirizzo3' => ['nullable','string','max:255'],
            'comune' => ['nullable','string','max:100'],
            'provincia' => ['nullable','string','max:10'],
            'cap' => ['nullable','string','max:10'],
            'regione' => ['nullable','string','max:100'],
            'paese' => ['nullable','string','max:100'],
            'email' => ['nullable','email','max:255'],
            'p_iva' => ['nullable','string','max:50'],
            'codice_fiscale' => ['nullable','string','max:20'],
            'telefono2' => ['nullable','string','max:20'],
            'telefono3' => ['nullable','string','max:20'],
            'telefono4' => ['nullable','string','max:20'],
            'sesso' => ['nullable','string','max:10'],
            'nota' => ['nullable','string'],
            'attivo' => ['nullable','boolean'],
            'altro1' => ['nullable','string','max:255'],
            'altro2' => ['nullable','string','max:255'],
            'altro3' => ['nullable','string','max:255'],
            'altro4' => ['nullable','string','max:255'],
            'altro5' => ['nullable','string','max:255'],
            'altro6' => ['nullable','string','max:255'],
            'altro7' => ['nullable','string','max:255'],
            'altro8' => ['nullable','string','max:255'],
            'altro9' => ['nullable','string','max:255'],
            'altro10' => ['nullable','string','max:255'],
            'chiamate' => ['nullable','integer'],
            'ultima_chiamata' => ['nullable','date'],
            'creato_da' => ['nullable','string','max:255'],
            'durata_ultima_chiamata' => ['nullable','string','max:20'],
            'totale_durata_chiamate' => ['nullable','string','max:20'],
            'chiamate_giornaliere' => ['nullable','integer'],
            'chiamate_mensili' => ['nullable','integer'],
            'data_creazione' => ['nullable','date'],
            'company_id' => ['nullable','string','max:36'],
        ];
        $data = $request->validate($rules);

        $lead = Lead::create($data);
        return response()->json($lead, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead)
    {
        return $lead;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lead $lead)
    {
        $rules = [
            'legacy_id' => ['sometimes','nullable','string','max:20'],
            'campagna' => ['sometimes','nullable','string','max:100'],
            'lista' => ['sometimes','nullable','string','max:100'],
            'ragione_sociale' => ['sometimes','nullable','string','max:255'],
            'cognome' => ['sometimes','nullable','string','max:100'],
            'nome' => ['sometimes','nullable','string','max:100'],
            'telefono' => ['sometimes','nullable','string','max:20'],
            'ultimo_operatore' => ['sometimes','nullable','string','max:255'],
            'esito' => ['sometimes','nullable','string','max:100'],
            'data_richiamo' => ['sometimes','nullable','date'],
            'operatore_richiamo' => ['sometimes','nullable','string','max:255'],
            'scadenza_anagrafica' => ['sometimes','nullable','date'],
            'indirizzo1' => ['sometimes','nullable','string','max:255'],
            'indirizzo2' => ['sometimes','nullable','string','max:255'],
            'indirizzo3' => ['sometimes','nullable','string','max:255'],
            'comune' => ['sometimes','nullable','string','max:100'],
            'provincia' => ['sometimes','nullable','string','max:10'],
            'cap' => ['sometimes','nullable','string','max:10'],
            'regione' => ['sometimes','nullable','string','max:100'],
            'paese' => ['sometimes','nullable','string','max:100'],
            'email' => ['sometimes','nullable','email','max:255'],
            'p_iva' => ['sometimes','nullable','string','max:50'],
            'codice_fiscale' => ['sometimes','nullable','string','max:20'],
            'telefono2' => ['sometimes','nullable','string','max:20'],
            'telefono3' => ['sometimes','nullable','string','max:20'],
            'telefono4' => ['sometimes','nullable','string','max:20'],
            'sesso' => ['sometimes','nullable','string','max:10'],
            'nota' => ['sometimes','nullable','string'],
            'attivo' => ['sometimes','nullable','boolean'],
            'altro1' => ['sometimes','nullable','string','max:255'],
            'altro2' => ['sometimes','nullable','string','max:255'],
            'altro3' => ['sometimes','nullable','string','max:255'],
            'altro4' => ['sometimes','nullable','string','max:255'],
            'altro5' => ['sometimes','nullable','string','max:255'],
            'altro6' => ['sometimes','nullable','string','max:255'],
            'altro7' => ['sometimes','nullable','string','max:255'],
            'altro8' => ['sometimes','nullable','string','max:255'],
            'altro9' => ['sometimes','nullable','string','max:255'],
            'altro10' => ['sometimes','nullable','string','max:255'],
            'chiamate' => ['sometimes','nullable','integer'],
            'ultima_chiamata' => ['sometimes','nullable','date'],
            'creato_da' => ['sometimes','nullable','string','max:255'],
            'durata_ultima_chiamata' => ['sometimes','nullable','string','max:20'],
            'totale_durata_chiamate' => ['sometimes','nullable','string','max:20'],
            'chiamate_giornaliere' => ['sometimes','nullable','integer'],
            'chiamate_mensili' => ['sometimes','nullable','integer'],
            'data_creazione' => ['sometimes','nullable','date'],
            'company_id' => ['sometimes','nullable','string','max:36'],
        ];
        $data = $request->validate($rules);

        $lead->update($data);
        return $lead;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();
        return response()->noContent();
    }
}
