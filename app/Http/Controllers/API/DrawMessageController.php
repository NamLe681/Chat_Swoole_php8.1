<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
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
        $userId = auth()->id();
        $request->validate([
            'drawing' => 'required|string',
        ]);

        $imageData = $request->drawing;
        $fileName = 'drawings/drawing_' . time() . '.png';
        $path = storage_path('app/public/' . $fileName);

        $img = str_replace('data:image/png;base64,', '', $imageData);
        file_put_contents($path, base64_decode($img));
        // dump('path', $path);
        // dump('img',$img);
        $imageToCloudinary = Cloudinary::upload($path, [
            'folder' => 'drawings',
            'public_id' => 'drawing_' . time(),
        ]);

        $message = Message::create([
            'room_id' => $room,
            'user_id' => $userId,
            'content' => $imageToCloudinary->getSecurePath(),
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
