<?php

namespace App\Models;

use Database\Factories\UpJurusanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $admin_jurusan_id
 * @property string $name
 * @property string|null $description
 * @property User $adminJurusan
 * @property Collection<int, User> $picketOfficers
 * @property Collection<int, Product> $products
 */
#[Fillable(['admin_jurusan_id', 'name', 'description'])]
class UpJurusan extends Model
{
    /** @use HasFactory<UpJurusanFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function adminJurusan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_jurusan_id');
    }

    /**
     * @return HasMany<UpJurusanConsignment, $this>
     */
    public function consignments(): HasMany
    {
        return $this->hasMany(UpJurusanConsignment::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function picketOfficers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
