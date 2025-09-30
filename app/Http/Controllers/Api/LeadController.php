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

        $filterable = [
            'first_name', 'last_name', 'email', 'phone', 'status',
        ];

        foreach ($filterable as $column) {
            if ($request->filled($column)) {
                $query->where($column, 'like', '%' . $request->string($column) . '%');
            }
        }

        if ($request->filled('order_by')) {
            $orderBy = in_array($request->string('order_by'), array_merge($filterable, ['id', 'created_at']))
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
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:leads,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'last_contacted_at' => ['nullable', 'date'],
        ]);

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
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('leads', 'email')->ignore($lead->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'last_contacted_at' => ['nullable', 'date'],
        ]);

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
