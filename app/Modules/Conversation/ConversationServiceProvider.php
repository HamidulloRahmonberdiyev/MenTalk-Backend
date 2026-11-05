<?php

declare(strict_types=1);

namespace App\Modules\Conversation;

use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\ScenarioBrief;
use App\Modules\Conversation\Policies\ConversationPolicy;
use App\Modules\Language\Models\Language;
use App\Shared\Providers\ModuleServiceProvider;

final class ConversationServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [Conversation::class => ConversationPolicy::class];

    public function boot(): void
    {
        parent::boot();

        // Declared here so the language module stays unaware of the scenarios written for it.
        Language::resolveRelationUsing('briefs', fn (Language $language) => $language->hasMany(ScenarioBrief::class, 'language', 'code'));
    }
}
