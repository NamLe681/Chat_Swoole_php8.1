<?php

namespace App\Http\Controllers\API;

use App\Events\ChatMessageEvent;
use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = ChatRoom::withCount('users')->get();

        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = Auth()->user();

        $room = ChatRoom::create($validated);

        $room->users()->attach($user->id);

        return response()->json($room->loadCount('users'), 201);
    }

    public function show(ChatRoom $room)
    {

        $userId = Auth::id();
        $rooms = ChatRoom::withCount('users')
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->get();

        return response()->json($rooms);
    }

    public function messages(ChatRoom $room)
    {
        $messages = $room->messages()
            ->with('user:id,name')
            ->latest()
            ->cursorPaginate(50);

        return response()->json($messages);
    }

    public function postmessage(Request $request, $room)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        $message = Message::create([
            'room_id' => $room,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'type' => 'text',
        ]);
        broadcast(new ChatMessageEvent($message->load('user')));

        return response()->json($message, 201);

    }

    public function addUserToRoom(Request $request, $room, $userId)
    {
        // $authUser = Auth::user();
        \Log::info('AddUserToRoom called', [
            'roomId' => $room,
            'userId' => $userId
        ]);

        // $userId = $request->input('user_id');

        $user = User::findOrFail($userId);

        $chatRoom = ChatRoom::findOrFail($room);

        if (! $chatRoom->users()->where('users.id', $user->id)->exists()) {
            $chatRoom->users()->attach($user->id);
        }

        return response()->json([
            'message' => 'User added to room successfully.',
            'room_id' => $chatRoom->id,
            'user_id' => $user->id,
        ], 200);
    }
}
