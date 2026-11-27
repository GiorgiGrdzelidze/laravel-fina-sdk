<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveLoyaltyGroup (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * @see LoyaltyApi::saveGroup()
 */
final class LoyaltyGroupPayload implements ValidatesPayload
{
    /**
     * @param  int  $id  Group ID (0 for new).
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $comment = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'comment' => $this->comment,
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'name' => ['required', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
