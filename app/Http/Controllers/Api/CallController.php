<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CallController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Call::query();

        $likeFilterable = [
            'numero_chiamato','stato_chiamata','esito','utente','company_id'
        ];

        foreach ($likeFilterable as $column) {
            if ($request->filled($column)) {
                $query->where($column, 'like', '%' . $request->string($column) . '%');
            }
        }

        // data_inizio range: from_data_inizio / to_data_inizio
        if ($request->filled('from_data_inizio')) {
            $query->where('data_inizio', '>=', $request->date('from_data_inizio'));
        }
        if ($request->filled('to_data_inizio')) {
            $query->where('data_inizio', '<=', $request->date('to_data_inizio'));
        }

        if ($request->filled('order_by')) {
            $orderBy = in_array($request->string('order_by'), array_merge($likeFilterable, ['data_inizio','id','created_at']))
                ? $request->string('order_by')
                : 'data_inizio';
            $direction = $request->string('order_dir', 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orderBy, $direction);
        } else {
            $query->latest('data_inizio');
        }

        $perPage = (int) $request->integer('per_page', 15);
        return $query->paginate(max(1, min(100, $perPage)))->withQueryString();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_chiamato' => ['required','string','max:255'],
            'data_inizio' => ['required','date'],
            'durata' => ['nullable','string','max:50'],
            'stato_chiamata' => ['nullable','string','max:100'],
            'esito' => ['nullable','string','max:100'],
            'utente' => ['nullable','string','max:255'],
            'company_id' => ['nullable','string','max:36'],
        ]);

        $call = Call::create($data);
        return response()->json($call, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Call $call)
    {
        return $call->load('lead');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Call $call)
    {
        $data = $request->validate([
            'numero_chiamato' => ['sometimes','required','string','max:255'],
            'data_inizio' => ['sometimes','required','date'],
            'durata' => ['nullable','string','max:50'],
            'stato_chiamata' => ['nullable','string','max:100'],
            'esito' => ['nullable','string','max:100'],
            'utente' => ['nullable','string','max:255'],
            'company_id' => ['nullable','string','max:36'],
        ]);

        $call->update($data);
        return $call;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Call $call)
    {
        $call->delete();
        return response()->noContent();
    }
}
