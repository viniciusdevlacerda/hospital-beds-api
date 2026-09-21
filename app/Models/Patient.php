<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\CpfCast;
use App\ValueObjects\Cpf;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property Cpf $cpf
 * @property BedOccupation|null $currentOccupation
 */
final class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    protected $fillable = ['name', 'cpf'];

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

    /** @param  Builder<$this>  $query */
    public function scopeWithCpf(Builder $query, Cpf $cpf): void
    {
        $query->where('cpf', $cpf->digits);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['cpf' => CpfCast::class];
    }
}
