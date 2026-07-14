<?php

namespace App\Integrations\Data;

use Spatie\LaravelData\Data;

class LeadRow extends Data
{
    public function __construct(
        public string $source,
        public string $externalId,
        public string $receivedAt,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $campaign = null,
        public ?string $medium = null,
        public string $status = 'new',
        public ?float $value = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toUpsertRow(?int $appId): array
    {
        return [
            'source' => $this->source,
            'external_id' => $this->externalId,
            'app_id' => $appId,
            'campaign' => $this->campaign,
            'medium' => $this->medium,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'value' => $this->value,
            'received_at' => $this->receivedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
