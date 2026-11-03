<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTO\NotificationMessage;
use App\Enums\InterfaceLanguage;
use App\Models\DatabaseNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A notification put into words. The language is `?lang=` when the app sends it (it may have
 * just switched and not saved the profile yet), otherwise the learner's interface language.
 *
 * @mixin DatabaseNotification
 */
final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $language = self::language($request);
        $message = $this->resource->message();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'kind' => $message->kind->value,
            'title' => NotificationMessage::render($message->title, $language),
            'body' => NotificationMessage::render($message->body, $language),
            'action' => $message->action === null ? null : [
                'target' => $message->action->value,
                'label' => (string) __($message->action->labelKey(), [], $language->value),
            ],
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    public static function language(Request $request): InterfaceLanguage
    {
        return InterfaceLanguage::tryFrom((string) $request->query('lang'))
            ?? $request->user()?->profile?->interfaceLanguage()
            ?? InterfaceLanguage::Uzbek;
    }
}
