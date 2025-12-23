<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\Request;

class ClientContactController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
        ]);

        if ($validated['is_primary'] ?? false) {
            $client->contacts()->update(['is_primary' => false]);
        }

        $contact = $client->contacts()->create($validated);

        return response()->json($contact, 201);
    }

    public function update(Request $request, ClientContact $contact)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
        ]);

        if ($validated['is_primary'] ?? false) {
            $contact->client->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($validated);

        return response()->json($contact);
    }

    public function destroy(ClientContact $contact)
    {
        $contact->delete();
        return response()->json(null, 204);
    }
}
