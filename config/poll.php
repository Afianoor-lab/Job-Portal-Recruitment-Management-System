<?php

return [
    // Dedicated queue so poll start/close jobs aren't blocked by other work.
    // Run: php artisan queue:work --queue=polls,default
    'queue' => env('POLL_QUEUE', 'polls'),
];
