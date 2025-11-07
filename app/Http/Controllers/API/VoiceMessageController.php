<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Message;
use App\Events\ChatMessageEvent;
use App\Http\Controllers\Controller;



class VoiceMessageController extends Controller
{
    public function storeVoice(Request $request,$room)
    {
        $request->merge(['room_id' => (int) $request->room_id]);

        $request->validate([
            'voice' => 'required|file|mimes:webm,ogg,mpeg,mp4,wav,mp3|max:20480',
            'room_id' => 'required|integer'
        ]);
        
        

        $path = $request->file('voice')->store('voices', 'public');

        $message = Message::create([
            'room_id' => $room,
            'user_id' => Auth()->id(),
            'type' => 'voice',
            'content' => $path,
        ]);

        broadcast(new ChatMessageEvent(message: $message->load(relations: 'user')));

        return response()->json([
            'message' => 'Voice uploaded successfully',
            'data' => $message,
            'type' => 'voice',
            'url' => asset('storage/'.$path),
            'content'=> Storage::url($path),
        ]);
    }
}

