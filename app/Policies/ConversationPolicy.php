<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ConversationState;
use App\Models\Conversation;
use App\Models\User;

final class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation) && $conversation->state === ConversationState::Active;
    }
}
