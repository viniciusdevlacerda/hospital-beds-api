<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BedStatus;
use Database\Factories\BedFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $code
 * @property string $sector
 * @property string|null $description
 * @property BedOccupation|null $currentOccupation
 */
final class Bed extends Model
{
    /** @use HasFactory<BedFactory> */
    use HasFactory;

    protected $fillable = ['code', 'sector', 'description'];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @return HasMany<BedOccupation, $this> */
    public function occupations(): HasMany
    {
        return $this->hasMany(BedOccupation::class);
    }

    /** @return HasOne<BedOccupation, $this> */
    public function currentOccupation(): HasOne
    {
        return $this->hasOne(BedOccupation::class)->active();
    }

    public function isOccupied(): bool
    {
        return $this->currentOccupation !== null;
    }

    public function status(): BedStatus
    {
        return $this->isOccupied() ? BedStatus::Occupied : BedStatus::Free;
    }

    /** @param  Builder<$this>  $query */
    public function scopeOccupied(Builder $query): void
    {
        $query->whereHas('currentOccupation');
    }

    /** @param  Builder<$this>  $query */
    public function scopeFree(Builder $query): void
    {
        $query->whereDoesntHave('currentOccupation');
    }

    /** @param  Builder<$this>  $query */
    public function scopeWithStatus(Builder $query, BedStatus $status): void
    {
        match ($status) {
            BedStatus::Occupied => $query->occupied(),
            BedStatus::Free => $query->free(),
        };
    }

    /** @param  Builder<$this>  $query */
    public function scopeInSector(Builder $query, string $sector): void
    {
        $query->where('sector', $sector);
    }
}
