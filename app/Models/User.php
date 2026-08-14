<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    // Sanctum's trait, and only Sanctum's. Passport ships a trait of the same name whose
    // `tokens`, `createToken`, `tokenCan` and `$accessToken` all collide with these, so
    // taking both is a fatal error rather than a merge. Nothing is lost by leaving it out:
    // `withAccessToken` is the only one of them Passport calls on the model, and Sanctum's
    // version of it does the job. The routes that would have wanted the rest -- Passport's
    // JSON API for managing clients and tokens -- are not registered.
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
