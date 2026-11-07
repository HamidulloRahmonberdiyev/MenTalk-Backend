<?php

use App\Modules\Account\AccountServiceProvider;
use App\Modules\Ai\AiServiceProvider;
use App\Modules\Bootstrap\BootstrapServiceProvider;
use App\Modules\Certificate\CertificateServiceProvider;
use App\Modules\Conversation\ConversationServiceProvider;
use App\Modules\Language\LanguageServiceProvider;
use App\Modules\Mistakes\MistakesServiceProvider;
use App\Modules\Notification\NotificationServiceProvider;
use App\Modules\Progress\ProgressServiceProvider;
use App\Modules\Pronunciation\PronunciationServiceProvider;
use App\Modules\Speech\SpeechServiceProvider;
use App\Modules\Translator\TranslatorServiceProvider;
use App\Modules\Vocabulary\VocabularyServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    AccountServiceProvider::class,
    AiServiceProvider::class,
    BootstrapServiceProvider::class,
    CertificateServiceProvider::class,
    ConversationServiceProvider::class,
    LanguageServiceProvider::class,
    MistakesServiceProvider::class,
    NotificationServiceProvider::class,
    ProgressServiceProvider::class,
    PronunciationServiceProvider::class,
    SpeechServiceProvider::class,
    TranslatorServiceProvider::class,
    VocabularyServiceProvider::class,
    AdminPanelProvider::class,
];
