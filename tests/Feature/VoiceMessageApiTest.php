<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\VoiceMessageController;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceMessageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_voice_message(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['name' => 'Tester']);
        $room = ChatRoom::factory()->create();

        Auth::login($user);

        $file = UploadedFile::fake()->create('voice.webm', 100, 'audio/webm'); // extension webm satisfies mimes rule

        $request = Request::create("/api/messages/voice/{$room->id}", 'POST', [
            'room_id' => $room->id,
        ], [], [
            'voice' => $file,
        ]);

        $controller = app(VoiceMessageController::class);
        $response = $controller->storeVoice($request, $room->id);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Voice uploaded successfully', $data['message'] ?? null);
        $this->assertEquals('voice', $data['type'] ?? null);

        $messageData = $data['data'] ?? null;
        $this->assertNotNull($messageData);
        $this->assertEquals($room->id, $messageData['room_id'] ?? null);
        $this->assertEquals($user->id, $messageData['user_id'] ?? null);
        $this->assertEquals('voice', $messageData['type'] ?? null);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'type' => 'voice',
        ]);

        $path = $messageData['content'] ?? null;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertNotEmpty($data['url'] ?? null);
        $this->assertNotEmpty($data['content'] ?? null);
    }
}
