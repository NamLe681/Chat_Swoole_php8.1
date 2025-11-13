<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\ChatMessageEvent;

class DrawMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function sendDrawingMessage(Request $request, $room)
    {
        $userId = auth()->id();
        $request->validate([
            'image' => 'required|string',
        ]);

        $imageData = $request->image;
        $fileName = 'drawing_' . time() . '.png';
        $path = storage_path('app/public/drawings/' . $fileName);

        $img = str_replace('data:image/png;base64,', '', $imageData);
        file_put_contents($path, base64_decode($img));

        $message = Message::create([
            'room_id' => $room,
            'user_id' => $userId,
            'content' => $path,
            'type' => 'drawing',
        ]);

        broadcast(new ChatMessageEvent($message->load('user')));

        return response()->json($message, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
