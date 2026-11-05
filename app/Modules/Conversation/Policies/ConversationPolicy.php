<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Policies;

use App\Modules\Account\Models\User;
use App\Modules\Conversation\Enums\ConversationState;
use App\Modules\Conversation\Models\Conversation;

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
