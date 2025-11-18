<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\SpotifyController;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpotifyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeSpotifyHttp(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'fake_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.spotify.com/v1/search*' => Http::response([
                'tracks' => [
                    'items' => [
                        [
                            'id' => 'track1',
                            'name' => 'Song 1',
                            'artists' => [['name' => 'Artist 1']],
                            'album' => [
                                'name' => 'Album 1',
                                'images' => [['url' => 'https://example.com/img1.jpg']],
                            ],
                            'external_urls' => ['spotify' => 'https://spotify.com/track1'],
                            'preview_url' => 'https://example.com/preview1.mp3',
                        ],
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/tracks/track1' => Http::response([
                'id' => 'track1',
                'name' => 'Song 1',
            ], 200),
        ]);
    }

    public function test_can_search_tracks(): void
    {
        $this->fakeSpotifyHttp();

        $request = Request::create('/api/spotify/search', 'GET', [
            'q' => 'Song',
            'limit' => 5,
        ]);

        $controller = app(SpotifyController::class);
        $response = $controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('tracks', $data);
        $this->assertIsArray($data['tracks']);
        $this->assertCount(1, $data['tracks']);
        $this->assertEquals('track1', $data['tracks'][0]['id']);
    }

    public function test_can_get_single_track(): void
    {
        $this->fakeSpotifyHttp();

        $request = Request::create('/api/spotify/track/track1', 'GET');

        $controller = app(SpotifyController::class);
        $response = $controller->getTrack($request, 'track1');

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('track1', $data['id'] ?? null);
        $this->assertEquals('Song 1', $data['name'] ?? null);
    }

    public function test_can_send_music_message_to_room(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();

        Auth::login($user);

        $track = [
            'id' => 'track1',
            'name' => 'Song 1',
        ];

        $request = Request::create("/api/spotify/music/{$room->id}", 'POST', [
            'track' => $track,
            'room_id' => $room->id,
        ]);

        $controller = app(SpotifyController::class);
        $response = $controller->sendMusicMessage($request, $room->id);

        $this->assertEquals(201, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals($room->id, $data['room_id'] ?? null);
        $this->assertEquals($user->id, $data['user_id'] ?? null);
        $this->assertEquals('music', $data['type'] ?? null);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'type' => 'music',
        ]);
    }
}
