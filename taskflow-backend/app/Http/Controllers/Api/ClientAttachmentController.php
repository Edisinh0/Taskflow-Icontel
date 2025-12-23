<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientAttachmentController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip|max:10240', // 10MB
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:contract,base_plan,invoice,general',
        ]);

        $file = $request->file('file');
        $path = $file->store('client-attachments/' . $client->id, 'public');

        $attachment = $client->attachments()->create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'category' => $request->category,
        ]);

        return response()->json($attachment, 201);
    }

    public function destroy(Client $client, ClientAttachment $attachment)
    {
        // Validar que el attachment pertenece al cliente especificado
        if ($attachment->client_id !== $client->id) {
            return response()->json([
                'message' => 'No autorizado para eliminar este archivo.'
            ], 403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}
