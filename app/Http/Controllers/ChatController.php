<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SupportAssistant;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat.index');
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $response = (new SupportAssistant)->prompt($request->message);

        return response()->json([
            'answer' => (string) $response,
        ]);
    }
}
