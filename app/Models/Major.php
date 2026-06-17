<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $major_group_id
 * @property string $code
 * @property string $name
 * @property int $grade_min
 * @property int $grade_max
 * @property MajorGroup $majorGroup
 */
#[Fillable(['major_group_id', 'code', 'name', 'grade_min', 'grade_max'])]
class Major extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_min' => 'integer',
            'grade_max' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MajorGroup, $this>
     */
    public function majorGroup(): BelongsTo
    {
        return $this->belongsTo(MajorGroup::class);
    }

    /**
     * @return HasMany<SchoolClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }
}
