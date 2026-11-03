<?php

declare(strict_types=1);

return [
    // Learner's local hour (0–23) when the daily reminder goes out. The command runs hourly.
    'reminder_hour' => (int) env('NOTIFY_REMINDER_HOUR', 19),

    // A "words are waiting" reminder needs at least this many due words.
    'min_due_words' => (int) env('NOTIFY_MIN_DUE_WORDS', 5),

    // Learners idle for longer get no reminders: nagging people who left does not bring them back.
    'remind_inactive_days' => (int) env('NOTIFY_REMIND_INACTIVE_DAYS', 14),

    // Read or not, notifications older than this are pruned.
    'retention_days' => (int) env('NOTIFY_RETENTION_DAYS', 90),

    // Streak lengths that earn a congratulation.
    'streak_milestones' => [3, 7, 14, 30, 50, 100, 150, 200, 365, 500, 1000],
];
