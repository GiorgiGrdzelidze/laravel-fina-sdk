<?php

declare(strict_types=1);

/**
 * Operation DTO: description of a user-defined additional field.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

/**
 * Row of every get*AdditionalFields endpoint: `name` is the database column
 * (usr_column_NNN) that `add_fields[].field` refers to, `header` is the label
 * the FINA user gave it.
 */
final readonly class AdditionalFieldDescriptorDto
{
    public function __construct(
        public string $name,
        public string $header,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['name'] ?? ''),
            (string) ($data['header'] ?? ''),
        );
    }
}
