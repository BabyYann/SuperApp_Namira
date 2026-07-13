<?php

namespace App\Helpers;

class WhatsAppHelper
{
    public static function formatPhone($phone)
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Check if starts with 08, replace with 628
        if (substr($phone, 0, 2) === '08') {
            return '62' . substr($phone, 1);
        }

        // Check if starts with 62, keep it
        if (substr($phone, 0, 2) === '62') {
            return $phone;
        }

        // Check if starts with 8, add 62
        if (substr($phone, 0, 1) === '8') {
            return '62' . $phone;
        }

        return $phone;
    }

    public static function generateLink($phone, $message)
    {
        $formattedPhone = self::formatPhone($phone);
        $encodedMessage = urlencode($message);
        return "https://wa.me/{$formattedPhone}?text={$encodedMessage}";
    }

    public static function send($phone, $message)
    {
        try {
            $queueItem = \App\Modules\Yayasan\Models\WhatsAppQueue::create([
                'phone' => self::formatPhone($phone),
                'message' => $message,
                'status' => 'pending',
            ]);
            \Log::info("Pushed WhatsApp message to queue: ID {$queueItem->id} to {$queueItem->phone}");
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to push WhatsApp message to queue: " . $e->getMessage());
            return false;
        }
    }

    public static function sendDirectly($phone, $message)
    {
        $wahaUrl = \App\Modules\Yayasan\Models\SystemSetting::getSetting('waha_url', env('WAHA_URL', 'http://localhost:3000'));
        $apiKey = \App\Modules\Yayasan\Models\SystemSetting::getSetting('waha_api_key', env('WAHA_API_KEY'));
        $session = \App\Modules\Yayasan\Models\SystemSetting::getSetting('waha_session', env('WAHA_SESSION', 'namira1'));

        $formattedPhone = self::formatPhone($phone);
        // WAHA requires number in format "628xxx@c.us"
        $chatId = $formattedPhone . '@c.us';

        $request = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
            'X-Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        if (config('app.env') === 'local') {
            $request = $request->withoutVerifying();
        }

        $response = $request->post("{$wahaUrl}/api/sendText", [
            'chatId' => $chatId,
            'text' => $message,
            'session' => $session,
        ]);

        return $response;
    }
}
