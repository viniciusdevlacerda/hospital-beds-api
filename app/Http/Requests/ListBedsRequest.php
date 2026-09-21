<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BedStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListBedsRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 15;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(BedStatus::class)],
            'sector' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function status(): ?BedStatus
    {
        $status = $this->query('status');

        return is_string($status) ? BedStatus::tryFrom($status) : null;
    }

    public function sector(): ?string
    {
        $sector = $this->query('sector');

        return is_string($sector) && $sector !== '' ? $sector : null;
    }

    public function perPage(): int
    {
        return (int) ($this->query('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
