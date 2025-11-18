<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\DrawMessageController;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class DrawMessageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_drawing_message(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();

        Auth::login($user);

        $engine = Mockery::mock();
        $engine->shouldReceive('upload')
            ->once()
            ->andReturnSelf();
        $engine->shouldReceive('getSecurePath')
            ->once()
            ->andReturn('https://example.com/drawing.png');

        Cloudinary::swap($engine);

        $request = Request::create("/api/rooms/{$room->id}/draw", 'POST', [
            'drawing' => 'base64-image-data',
        ]);

        $controller = app(DrawMessageController::class);
        $response = $controller->sendDrawingMessage($request, $room->id);

        $this->assertEquals(201, $response->getStatusCode());

        $message = $response->getData(true);
        $this->assertEquals($room->id, $message['room_id'] ?? null);
        $this->assertEquals($user->id, $message['user_id'] ?? null);
        $this->assertEquals('drawing', $message['type'] ?? null);
        $this->assertEquals('https://example.com/drawing.png', $message['content'] ?? null);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'type' => 'drawing',
            'content' => 'https://example.com/drawing.png',
        ]);
    }
}
