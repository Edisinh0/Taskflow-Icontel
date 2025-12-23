<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;

class IndustryController extends Controller
{
    /**
     * Display a listing of all industries.
     *
     * This endpoint is public (no authentication required) as industries
     * are a reference catalog used during client and template management.
     */
    public function index()
    {
        $industries = Industry::orderBy('name')->get();

        return response()->json($industries);
    }

    /**
     * Store a newly created industry in storage.
     *
     * This is primarily used during Sweet CRM integration to populate
     * the industry catalog automatically.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:industries|max:255',
            'slug' => 'required|string|unique:industries|max:255',
        ]);

        $industry = Industry::create($validated);

        return response()->json($industry, 201);
    }

    /**
     * Display the specified industry.
     */
    public function show(Industry $industry)
    {
        // Load related templates and clients
        $industry->load(['templates' => function ($query) {
            $query->where('is_active', true)->orderBy('name');
        }, 'clients' => function ($query) {
            $query->where('status', 'active')->orderBy('name');
        }]);

        return response()->json($industry);
    }

    /**
     * Update the specified industry in storage.
     */
    public function update(Request $request, Industry $industry)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:industries,name,' . $industry->id . '|max:255',
            'slug' => 'sometimes|required|string|unique:industries,slug,' . $industry->id . '|max:255',
        ]);

        $industry->update($validated);

        return response()->json($industry);
    }

    /**
     * Remove the specified industry from storage.
     *
     * Note: This will fail if there are templates or clients using this industry
     * due to foreign key constraints.
     */
    public function destroy(Industry $industry)
    {
        $industry->delete();

        return response()->json(null, 204);
    }
}
