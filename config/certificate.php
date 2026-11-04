<?php

declare(strict_types=1);

return [
    // Rated conversations a learner needs before a certificate can be issued.
    'min_conversations' => 5,
    // A conversation shorter than this says too little about the learner to count.
    'min_turns' => 3,
    // Scores average the latest conversations, so they show the current ability.
    'recent_conversations' => 10,
];
