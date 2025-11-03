<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use app\Event\ChatMessageEvent;
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

        $userId = Auth::id();
        $rooms = ChatRoom::withCount('users')
            ->whereHas('users', function($query) use ($userId) {
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
        broadcast(new ChatMessageEvent($message->load('user')));
    
        return response()->json($message, 201);

    }

    public function AddUserToRooom(Request $request, $room)
    {
        $user = Auth::user();

        $chatRoom = ChatRoom::findOrFail($room);

        $chatRoom->users()->attach($user->id);

        return response()->json(['message' => 'User added to room successfully.'], 200);
    }
    
}
