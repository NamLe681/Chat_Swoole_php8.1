<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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

        $user = Auth() -> user();

        $room = ChatRoom::create($validated);

        $room->users()->attach($user->id);

        return response()->json($room->loadCount('users'), 201);
    }

    public function show(ChatRoom $room)
    {
        $room->load(['users' => function ($query) {
            $query->select('users.id', 'name', 'email');
        }]);

        return response()->json($room);
    }

    public function messages(ChatRoom $room)
    {
        $messages = $room->messages()
                         ->with('user:id,name,email')
                         ->latest()
                         ->paginate(50);

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
        ]);
    
        return response()->json($message, 201);
    }
    
}
