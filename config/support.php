<?php

declare(strict_types=1);

return [
    /** The public Telegram channel the app invites learners to; the row is hidden while this is empty. */
    'telegram_url' => env('SUPPORT_TELEGRAM_URL'),

    /** Tickets one learner may send per hour; more is a stuck button or abuse, not a conversation. */
    'per_hour' => 5,
];
