<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceMessageController extends Controller
{
    public function storeVoice(Request $request, $room)
    {
        $request->merge(['room_id' => (int) $request->room_id]);

        $request->validate([
            'voice' => 'required|file|mimetypes:audio/webm,audio/ogg,audio/mpeg,video/mp4,audio/wav,audio/mp3|max:20480',
            'room_id' => 'required|integer',
        ]);

        $path = $request->file('voice')->store('voices', 'public');

        $message = Message::create([
            'room_id' => $room,
            'user_id' => Auth()->id(),
            'type' => 'voice',
            'content' => $path,
        ]);

        try {
            broadcast(new ChatMessageEvent(message: $message->load(relations: 'user')));
        } catch (BroadcastException $e) {
            \Log::warning('Broadcast failed for VoiceMessageEvent', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Voice uploaded successfully',
            'data' => $message,
            'type' => 'voice',
            'url' => asset('storage/'.$path),
            'content' => Storage::url($path),
        ]);
    }
}
