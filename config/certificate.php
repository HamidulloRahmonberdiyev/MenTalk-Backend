<?php

declare(strict_types=1);

/**
 * How a certificate level is worked out from a learner's activity (`LevelEstimate`). Any activity
 * at all (a saved word, a rated chat, the placement test) earns a certificate; how much there is
 * decides how high a level it may show.
 */
return [
    // Rated conversations needed before a certificate may show the top level (B2+).
    'min_conversations' => 5,
    // A conversation shorter than this says too little about the learner to count.
    'min_turns' => 3,
    // Scores average the latest conversations, so they show the current ability.
    'recent_conversations' => 10,

    // How much the placement test and each rated chat (up to `recent_conversations`) weigh against
    // each other. The word list only decides when there is neither.
    'placement_weight' => 2.0,
    'conversation_weight' => 0.6,

    // Average conversation score (0-10) needed for elementary, intermediate and advanced.
    'score_thresholds' => [4.0, 6.0, 8.0],
    // Words on the learner's list needed for the same three steps. Lower than CEFR word counts:
    // the list holds the words a learner saved, not every word they know.
    'word_thresholds' => [150, 500, 1500],
    // The list size that scores a full 10 for vocabulary when no conversation was rated.
    'words_for_full_score' => 1500,
];
