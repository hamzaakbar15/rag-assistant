<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,txt|max:10240',
        ]);

        $path = $request->file('file')->store('documents');

        Document::create([
            'user_id' => auth()->id(),
            'title' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Document uploaded successfully.');
    }

    public function destroy(Document $document)
    {
        abort_unless($document->user_id === auth()->id(), 403);

        $document->delete();

        return back()->with('status', 'Document deleted.');
    }
}