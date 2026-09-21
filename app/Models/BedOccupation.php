<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReleaseReason;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bed_id
 * @property int $patient_id
 * @property CarbonImmutable $occupied_at
 * @property CarbonImmutable|null $released_at
 * @property ReleaseReason|null $release_reason
 * @property Bed $bed
 * @property Patient $patient
 */
final class BedOccupation extends Model
{
    protected $fillable = ['bed_id', 'patient_id', 'occupied_at', 'released_at', 'release_reason'];

    /** @return BelongsTo<Bed, $this> */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isActive(): bool
    {
        return $this->released_at === null;
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('released_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occupied_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
            'release_reason' => ReleaseReason::class,
        ];
    }
}
