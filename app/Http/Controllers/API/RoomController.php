<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatRoom;
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

        $sampleUser = User::firstOrCreate([
            'email' => 'sample@example.com',
        ], [
            'name' => 'Sample User',
            'password' => bcrypt('password'),
        ]);

        $room = ChatRoom::create($validated);

        $room->users()->attach($sampleUser->id);

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
}
