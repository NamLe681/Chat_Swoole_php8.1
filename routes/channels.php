<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatRoom;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


Broadcast::channel('presence-chat.{roomId}', function ($user, $roomId) {
    \Log::info('Authorizing channel', [
        'channel' => 'presence-chat.' . $roomId,
        'user_id' => $user ? $user->id : null,
        'room_id' => $roomId
    ]);

    if (!$user) {
        \Log::warning('No authenticated user');
        return null;
    }

    $room = ChatRoom::find($roomId);
    if (!$room) {
        \Log::warning('Room not found', ['room_id' => $roomId]);
        return null;
    }

    if (!$room->users()->where('user_id', $user->id)->exists()) {
        \Log::warning('User not authorized for room', ['user_id' => $user->id, 'room_id' => $roomId]);
        return null;
    }

    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('presence-room.{roomId}', function ($user, $roomId) {
    if ($user && $user->canJoinRoom($roomId)) {
        return ['id' => $user->id, 'name' => $user->name];
    }
    return null;
});