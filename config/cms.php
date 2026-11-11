<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk that stores uploaded photos and videos. The API
    | returns absolute URLs built from this disk, so in production point
    | it at S3 / R2 (with a CDN URL) or keep "public" behind HTTPS.
    |
    */

    'disk' => env('CMS_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'image_max_kb' => 10 * 1024,
        'video_max_kb' => 10 * 1024,
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'video_mimes' => ['mp4'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trash
    |--------------------------------------------------------------------------
    |
    | Deleted items stay in the trash for this many days before they are
    | removed for good by the scheduled "model:prune" command.
    |
    */

    'trash_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Front-End Revalidation
    |--------------------------------------------------------------------------
    |
    | After every save the CMS calls POST {url}/api/revalidate with the
    | shared secret so changes appear on the live site immediately.
    | Leave the URL empty to rely on the front end's cache time only.
    |
    */

    'frontend' => [
        'url' => env('FRONTEND_URL'),
        'revalidate_secret' => env('FRONTEND_REVALIDATE_SECRET'),
        'tags' => ['site', 'pages', 'settings', 'services', 'projects', 'reviews'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icons
    |--------------------------------------------------------------------------
    |
    | The icon keys the front end can draw. The admin stores a key only.
    |
    */

    'icons' => [
        'chat-circle-text',
        'seal-check',
        'clipboard-text',
        'buildings',
        'ruler',
        'hammer',
        'calculator',
        'hard-hat',
        'list-checks',
        'calendar-check',
        'receipt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Form
    |--------------------------------------------------------------------------
    */

    'contact' => [
        'duplicate_window_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Reverse proxies (comma separated, or "*") whose X-Forwarded-* headers
    | are trusted, so Laravel sees https when it sits behind nginx.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
