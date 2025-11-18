<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;

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

        $request->validate([
            'drawing' => 'required|string',
        ]);

        $userId = auth()->id();
        $drawing = $request->drawing;
        try {
            $uploaded = Cloudinary::upload($drawing, [
                'public_id' => 'drawing_' . time(),
            ]);

            $url = $uploaded->getSecurePath();
            if (!$url) {
                return response()->json(['error' => 'Upload failed'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $message = Message::create([
            'room_id' => $room,
            'user_id' => $userId,
            'content' => $url,
            'type' => 'drawing',
        ]);

        try {
            broadcast(new ChatMessageEvent($message->load('user')));
        } catch (BroadcastException $e) {
            \Log::warning('Broadcast failed for DrawMessageEvent', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

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
