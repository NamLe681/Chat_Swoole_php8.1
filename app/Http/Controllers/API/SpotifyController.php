<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SpotifyService;
use App\Models\Message;
use App\Events\ChatMessageEvent;
use Illuminate\Broadcasting\BroadcastException;

class SpotifyController extends Controller
{
    protected $spotify;

    public function __construct(SpotifyService $spotify)
    {
        $this->spotify = $spotify;
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $tracks = $this->spotify->searchTracks($request->q, $request->limit ?? 10);

        return response()->json(['tracks' => $tracks]);

    }


    public function getTrack(Request $request, $id){
        $track = $this->spotify->searchTrack($id);
        return response()->json( $track);
    }

    public function sendMusicMessage(Request $request, $room)
    {
        $request->merge(['room_id' => (int) $request->room_id]);

        $request->validate([
            'track' => 'required',
            'room_id' => 'required|integer',
        ]); 

        $message = Message::create([
            'room_id' => $room,
            'user_id' => auth()->id(),
            'content' => json_encode($request->track),
            'type' => 'music',
        ]);

        try {
            broadcast(new ChatMessageEvent($message->load('user')));
        } catch (BroadcastException $e) {
            \Log::warning('Broadcast failed for MusicMessageEvent', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($message, 201);
    }

}

