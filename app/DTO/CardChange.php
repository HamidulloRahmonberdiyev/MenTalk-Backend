<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class CardChange
{
    public function __construct(
        public string $word,
        public string $translation,
        public ?string $example,
        public ?string $form,
        public int $stage,
        public int $streak,
        public int $lapses,
        public CarbonImmutable $dueAt,
        public CarbonImmutable $updatedAt,
        public bool $deleted,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['word'],
            $data['translation'],
            $data['example'] ?? null,
            $data['form'] ?? null,
            (int) $data['stage'],
            (int) ($data['streak'] ?? 0),
            (int) ($data['lapses'] ?? 0),
            CarbonImmutable::parse($data['due_at']),
            CarbonImmutable::parse($data['updated_at']),
            (bool) ($data['deleted'] ?? false),
        );
    }

    public function toRow(string $userId, string $id, CarbonImmutable $now): array
    {
        return [
            'id' => $id,
            'user_id' => $userId,
            'word' => $this->word,
            'translation' => $this->translation,
            'example' => $this->example,
            'form' => $this->form,
            'stage' => $this->stage,
            'streak' => $this->streak,
            'lapses' => $this->lapses,
            'due_at' => $this->dueAt,
            'updated_at' => $this->updatedAt,
            'synced_at' => $now,
            'deleted_at' => $this->deleted ? $now : null,
        ];
    }
}
