<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Bed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Bed */
final class BedResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'sector' => $this->sector,
            'description' => $this->description,
            'status' => $this->status()->value,
            'status_label' => $this->status()->label(),
            'occupation' => $this->currentOccupation === null
                ? null
                : OccupationResource::make($this->currentOccupation),
        ];
    }
}
