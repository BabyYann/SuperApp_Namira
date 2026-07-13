<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send push notification to specific device tokens.
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendNotification(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        try {
            if (!file_exists(storage_path('app/firebase-service-account.json'))) {
                Log::warning('FCM credentials file not found. Push notifications will be skipped.');
                return;
            }

            $accessToken = $this->getAccessToken();
            $credentials = json_decode(file_get_contents(storage_path('app/firebase-service-account.json')), true);
            $projectId = $credentials['project_id'];

            foreach ($tokens as $token) {
                $messagePayload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                    ],
                ];

                if (!empty($data)) {
                    // Stringify values as FCM data payloads require strings
                    $stringData = array_map('strval', $data);
                    $messagePayload['message']['data'] = $stringData;
                }

                $response = Http::withoutVerifying()
                    ->withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $messagePayload);

                if ($response->failed()) {
                    Log::error("FCM failed for token: " . substr($token, 0, 20) . "... Error: " . $response->body());
                    // Cleanup inactive tokens if token expired or unregistered
                    $resData = $response->json();
                    $errorCode = $resData['error']['status'] ?? '';
                    if ($errorCode === 'NOT_FOUND' || $errorCode === 'UNREGISTERED') {
                        \App\Models\UserDeviceToken::where('token', $token)->delete();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('FCM Notification error: ' . $e->getMessage());
        }
    }

    /**
     * Send push notification to a specific user's registered devices.
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->toArray();
        $this->sendNotification($tokens, $title, $body, $data);
    }

    /**
     * Get OAuth2 Access Token for FCM from Google APIs.
     *
     * @return string
     * @throws \Exception
     */
    protected function getAccessToken(): string
    {
        return cache()->remember('fcm_access_token', 3000, function () {
            $credentials = json_decode(file_get_contents(storage_path('app/firebase-service-account.json')), true);

            $privateKey = $credentials['private_key'];
            $clientEmail = $credentials['client_email'];

            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);

            $now = time();
            $payload = json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signature = '';
            openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            $response = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to retrieve Firebase access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }
}
