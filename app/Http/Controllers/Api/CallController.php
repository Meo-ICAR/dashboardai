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
        $query = Call::query()->with('lead');

        $filterable = [
            'lead_id', 'direction', 'result',
        ];

        foreach ($filterable as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->input($column));
            }
        }

        if ($request->filled('from')) {
            $query->where('called_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('called_at', '<=', $request->date('to'));
        }

        if ($request->filled('order_by')) {
            $orderBy = in_array($request->string('order_by'), array_merge($filterable, ['called_at', 'id', 'created_at']))
                ? $request->string('order_by')
                : 'called_at';
            $direction = $request->string('order_dir', 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orderBy, $direction);
        } else {
            $query->latest('called_at');
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
            'lead_id' => ['nullable', 'exists:leads,id'],
            'called_at' => ['required', 'date'],
            'direction' => ['required', 'string', Rule::in(['inbound', 'outbound'])],
            'duration' => ['nullable', 'string', 'max:50'],
            'result' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $call = Call::create($data);
        return response()->json($call->load('lead'), 201);
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
            'lead_id' => ['nullable', 'exists:leads,id'],
            'called_at' => ['sometimes', 'required', 'date'],
            'direction' => ['sometimes', 'required', 'string', Rule::in(['inbound', 'outbound'])],
            'duration' => ['nullable', 'string', 'max:50'],
            'result' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $call->update($data);
        return $call->load('lead');
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
