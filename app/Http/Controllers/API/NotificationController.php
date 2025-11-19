<?php

namespace App\Http\Controllers\API;

// app/Http/Controllers/NotificationController.php
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;

class NotificationController extends Controller
{
    protected function getAccessToken()
    {
        $serviceAccount = json_decode(file_get_contents(storage_path('app/firebase-service-account.json')), true);

        $now = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccount['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600
        ];

        $jwt = JWT::encode($payload, $serviceAccount['private_key'], 'RS256');

        $res = Http::asForm()->post($serviceAccount['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        return $res->json()['access_token'];
    }

    public function send(Request $request)
    {
        $request->validate([
            'token'=>'required|string',
            'title'=>'required|string',
            'body'=>'required|string'
        ]);
        $serviceAccount = json_decode(file_get_contents(storage_path('app/firebase-service-account.json')), true);
        $projectId = $serviceAccount['project_id'];

        $accessToken = $this->getAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $payload = [
            'message' => [
                'token' => $request->token,
                'notification' => [
                    'title' => $request->title,
                    'body' => $request->body
                ]
            ]
        ];

        $res = Http::withToken($accessToken)->post($url, $payload);

        return response()->json($res->json());
    }

    public function saveToken(Request $request){
        $request->validate(['token'=>'required|string']);
        $user = $request->user();
        $user->fcm_token = $request->token;
        $user->save();
        return response()->json(['status'=>'ok']);
    }
}
