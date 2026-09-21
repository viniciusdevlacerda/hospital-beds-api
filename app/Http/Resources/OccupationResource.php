<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BedOccupation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BedOccupation */
final class OccupationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'active' => $this->isActive(),
            'occupied_at' => $this->occupied_at->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'release_reason' => $this->release_reason?->value,
            'bed' => $this->whenLoaded('bed', fn (): array => [
                'code' => $this->bed->code,
                'sector' => $this->bed->sector,
            ]),
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'name' => $this->patient->name,
                'cpf' => $this->patient->cpf->masked(),
            ]),
        ];
    }
}
