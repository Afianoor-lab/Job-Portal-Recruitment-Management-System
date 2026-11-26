<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    | Must be a PRIVATE disk. Voice notes are never served straight from the web
    | server, only through the signed route (see routes/Api/voice.php).
    | Define the disk in config/filesystems.php (see docs/voice-notes.md).
    */
    'disk' => env('VOICE_DISK', 'voice'),

    // Largest accepted file, in KILOBYTES. Keep it below PHP's upload_max_filesize and post_max_size.
    'max_size_kb' => 10240, // 10 MB

    // Smaller than this is treated as an empty or broken recording.
    'min_size_bytes' => 200,

    // The duration is sent by the frontend (the server does not decode audio).
    'min_duration_seconds' => 1,
    'max_duration_seconds' => 300,

    // Optional waveform for the player: up to this many peaks, each 0..100.
    'waveform_max_points' => 120,

    // Lifetime of signed playback links. The frontend refreshes them with GET /voice-notes/{id}.
    'url_ttl_minutes' => 15,

    // Voice notes uploaded but never sent with a message are deleted after this many hours.
    'pending_ttl_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Allowed formats
    |--------------------------------------------------------------------------
    | content type (detected from the file CONTENT) => extension used for the stored file.
    | The extension never comes from the client's file name.
    */
    'allowed' => [
        'audio/webm' => 'webm', // Chrome, Edge, Firefox (MediaRecorder)
        'audio/ogg' => 'ogg',   // Firefox, Opus in Ogg
        'audio/mp4' => 'm4a',   // Safari (MediaRecorder), iOS
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/aac' => 'aac',
    ],

    // Different names PHP's detection may report for the same thing.
    // Browsers record audio inside containers that PHP often reports as "video/...".
    'mime_aliases' => [
        'video/webm' => 'audio/webm',
        'video/mp4' => 'audio/mp4',
        'video/ogg' => 'audio/ogg',
        'application/ogg' => 'audio/ogg',
        'audio/opus' => 'audio/ogg',
        'audio/x-opus+ogg' => 'audio/ogg',
        'audio/x-m4a' => 'audio/mp4',
        'audio/m4a' => 'audio/mp4',
        'audio/mp3' => 'audio/mpeg',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-wav' => 'audio/wav',
        'audio/wave' => 'audio/wav',
        'audio/vnd.wave' => 'audio/wav',
        'audio/x-aac' => 'audio/aac',
        'audio/x-hx-aac-adts' => 'audio/aac',
    ],

    /*
    |--------------------------------------------------------------------------
    | ffprobe (optional, recommended on the live server)
    |--------------------------------------------------------------------------
    | Without it the server cannot tell an audio-only recording from a video that is
    | stored in the same container (webm / mp4), and it trusts the duration sent by
    | the frontend. With it, videos are refused and the real duration is checked.
    | Set the full path in .env, for example VOICE_FFPROBE=/usr/bin/ffprobe
    | or VOICE_FFPROBE="C:\ffmpeg\bin\ffprobe.exe". Leave empty to skip.
    */
    'ffprobe' => env('VOICE_FFPROBE'),

    // The real duration may be this many seconds longer than the maximum (encoder rounding).
    'duration_tolerance_seconds' => 3,
];
