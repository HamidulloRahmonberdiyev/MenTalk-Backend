<?php

declare(strict_types=1);

namespace App\Modules\Notification\DTO;

use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Notification\Enums\NotificationAction;
use App\Modules\Notification\Enums\NotificationKind;

/**
 * A notification as stored in `notifications.data`. Its texts are either a translation key with
 * parameters (system events) or text per interface language (announcements written by an admin);
 * both are turned into a sentence only when read, so they follow the learner's current language.
 *
 * @phpstan-type Text array{key: string, params?: array<string, scalar>}|array{text: array<string, string>}
 */
final readonly class NotificationMessage
{
    /**
     * @param  Text  $title
     * @param  Text  $body
     */
    public function __construct(
        public NotificationKind $kind,
        public array $title,
        public array $body,
        public ?NotificationAction $action = null,
    ) {}

    /** Title and body from `lang/{locale}/notifications.php`, under `$key.title` and `$key.body`. */
    public static function translated(NotificationKind $kind, string $key, array $params = [], ?NotificationAction $action = null): self
    {
        return new self(
            $kind,
            ['key' => "notifications.{$key}.title", 'params' => $params],
            ['key' => "notifications.{$key}.body", 'params' => $params],
            $action,
        );
    }

    /**
     * @param  array<string, string>  $title  by interface language
     * @param  array<string, string>  $body  by interface language
     */
    public static function written(NotificationKind $kind, array $title, array $body, ?NotificationAction $action = null): self
    {
        return new self($kind, ['text' => array_filter($title)], ['text' => array_filter($body)], $action);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            NotificationKind::tryFrom((string) ($data['kind'] ?? '')) ?? NotificationKind::News,
            (array) ($data['title'] ?? []),
            (array) ($data['body'] ?? []),
            NotificationAction::tryFrom((string) ($data['action'] ?? '')),
        );
    }

    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'title' => $this->title, 'body' => $this->body, 'action' => $this->action?->value];
    }

    /** The finished sentence in `$language`; written text falls back to Uzbek, then to any language it has. */
    public static function render(array $text, InterfaceLanguage $language): string
    {
        if (isset($text['key'])) {
            return (string) __($text['key'], $text['params'] ?? [], $language->value);
        }

        $written = $text['text'] ?? [];

        return (string) ($written[$language->value] ?? $written[InterfaceLanguage::Uzbek->value] ?? reset($written) ?: '');
    }
}
