<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /** @param array<string, mixed> $validated_data */
    public static function registerUser(array $validated_data): self
    {
        return self::create($validated_data);
    }

    /** @param array<string, mixed> $validated_data */
    public function updateProfile(array $validated_data): void
    {
        $this->fill($validated_data);

        if ($this->isDirty('email')) {
            $this->email_verified_at = null;
        }

        $this->save();
    }

    public function updatePassword(string $password): void
    {
        $this->update(['password' => $password]);
    }

    public function deleteAccount(): void
    {
        $this->delete();
    }

    /** @param array<string, mixed> $validated_data */
    public static function sendPasswordResetLink(array $validated_data): string
    {
        return Password::sendResetLink($validated_data);
    }

    /** @param array<string, mixed> $validated_data */
    public static function resetUserPassword(array $validated_data): string
    {
        return Password::reset($validated_data, function (self $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });
    }
}
