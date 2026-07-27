<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuscleGroup extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    /** @var list<string> */
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'sort',
    ];

    /** @var list<string> */
    protected array $translatable = ['name'];

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('name_ar');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }
}
