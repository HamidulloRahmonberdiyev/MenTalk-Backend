<?php

declare(strict_types=1);

return [
    'keys' => array_values(array_filter(explode(',', (string) env('GEMINI_KEYS')))),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-3.8-flash'),
    'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-3.8-flash-tts'),
    // Another Gemini TTS model, tried when the main one refuses or keeps failing; same voices, so the tutor sounds the same.
    'tts_fallback_model' => env('GEMINI_TTS_FALLBACK_MODEL'),
    'voice' => env('GEMINI_VOICE', 'Sulafat'),
    'timeout' => 25,
    'key_cooldown' => 30,
    'google_client_ids' => array_values(array_filter(explode(',', (string) env('GOOGLE_CLIENT_IDS')))),
];
