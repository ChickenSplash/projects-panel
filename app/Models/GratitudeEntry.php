<?php

namespace App\Models;

use Database\Factories\GratitudeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entry_date'])]
// `entry_date` stays a plain `Y-m-d` string: it is a calendar day, not a moment,
// and a date cast would write midnight into it and stop the lookups matching.
class GratitudeEntry extends Model
{
    /** @use HasFactory<GratitudeEntryFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<GratitudeItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(GratitudeItem::class, 'entry_id')->orderBy('position');
    }
}
