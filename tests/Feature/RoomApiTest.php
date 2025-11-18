<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\RoomController;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_room_and_is_attached_to_it(): void
    {
        $user = User::factory()->create();

        Auth::login($user);

        $payload = [
            'name' => 'My Room',
            'description' => 'Test description',
        ];

        $request = Request::create('/api/rooms', 'POST', $payload);
        $controller = app(RoomController::class);

        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());

        $room = $response->getData(true);
        $roomId = $room['id'] ?? null;
        $this->assertNotNull($roomId);

        $this->assertDatabaseHas('chat_rooms', [
            'id' => $roomId,
            'name' => 'My Room',
        ]);

        $this->assertDatabaseHas('room_user', [
            'room_id' => $roomId,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_fetch_only_rooms_they_belong_to(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $roomForUser = ChatRoom::factory()->create();
        $roomForOther = ChatRoom::factory()->create();

        $roomForUser->users()->attach($user->id);
        $roomForOther->users()->attach($otherUser->id);

        Auth::login($user);

        $controller = app(RoomController::class);
        // show() uses Auth::id() and ignores the ChatRoom argument value
        $response = $controller->show($roomForUser);

        $this->assertEquals(200, $response->getStatusCode());

        $rooms = $response->getData(true);
        $ids = collect($rooms)->pluck('id');

        $this->assertTrue($ids->contains($roomForUser->id));
        $this->assertFalse($ids->contains($roomForOther->id));
    }

    public function test_authenticated_user_can_post_text_message_to_room(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();

        Auth::login($user);

        $request = Request::create("/api/rooms/{$room->id}/messages", 'POST', [
            'content' => 'Hello world',
        ]);

        $controller = app(RoomController::class);
        $response = $controller->postmessage($request, $room->id);

        $this->assertEquals(201, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Hello world', $data['content'] ?? null);
        $this->assertEquals('text', $data['type'] ?? null);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'content' => 'Hello world',
            'type' => 'text',
        ]);
    }

    public function test_can_list_messages_for_room(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();

        Message::factory()->count(3)->create([
            'room_id' => $room->id,
            'user_id' => $user->id,
        ]);

        $controller = app(RoomController::class);
        $response = $controller->messages($room);

        $this->assertEquals(200, $response->getStatusCode());

        $json = $response->getData(true);
        $data = $json['data'] ?? $json;
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    public function test_can_add_user_to_room(): void
    {
        $owner = User::factory()->create();
        $userToAdd = User::factory()->create();
        $room = ChatRoom::factory()->create();

        $room->users()->attach($owner->id);

        $request = Request::create("/api/rooms/{$room->id}/add-user/{$userToAdd->id}", 'POST');

        $controller = app(RoomController::class);
        $response = $controller->addUserToRoom($request, $room->id, $userToAdd->id);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals($room->id, $data['room_id'] ?? null);
        $this->assertEquals($userToAdd->id, $data['user_id'] ?? null);

        $this->assertDatabaseHas('room_user', [
            'room_id' => $room->id,
            'user_id' => $userToAdd->id,
        ]);
    }
}
