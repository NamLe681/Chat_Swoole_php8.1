<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SpotifyService;

class SpotifyController extends Controller
{
    protected $spotify;

    public function __construct(SpotifyService $spotify)
    {
        $this->spotify = $spotify;
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $tracks = $this->spotify->searchTracks($request->q, $request->limit ?? 10);

        return response()->json([
            'success' => true,
            'tracks' => $tracks
        ]);
    }
}

