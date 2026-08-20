<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['body', 'position'])]
class GratitudeItem extends Model
{
    // The table keeps only `created_at`: an item is written once and never edited.
    public const UPDATED_AT = null;

    /** @return BelongsTo<GratitudeEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(GratitudeEntry::class, 'entry_id');
    }
}
