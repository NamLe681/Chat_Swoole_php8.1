<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SpotifyService
{
    protected $accessToken;
    protected $expiresAt;

    public function __construct()
    {
        $this->getAccessToken();
    }

    protected function getAccessToken()
    {
        if ($this->accessToken && $this->expiresAt > now()) {
            return $this->accessToken;
        }

        $response = Http::asForm()->withBasicAuth(
            config('services.spotify.client_id'),
            config('services.spotify.client_secret')
        )->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'client_credentials',
        ]);

        $this->accessToken = $response->json('access_token');
        $this->expiresAt = now()->addSeconds($response->json('expires_in') - 60);

        return $this->accessToken;
    }

    public function searchTracks(string $query, int $limit = 10)
    {
        $this->getAccessToken();

        $response = Http::withToken($this->accessToken)
            ->get('https://api.spotify.com/v1/search', [
                'q' => $query,
                'type' => 'track',
                'limit' => $limit
            ]);

        $tracks = $response->json('tracks.items');

        return collect($tracks)->map(function ($track): array {
            return [
                'id' => $track['id'],
                'name' => $track['name'],
                'artist' => implode(', ', array_column($track['artists'], 'name')),
                'album' => $track['album']['name'],
                'url' => $track['external_urls']['spotify'],
                'preview_url' => $track['preview_url'],
                'image' => $track['album']['images'][0]['url'] ?? null
            ];
        });
    }
}
