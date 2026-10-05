<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeederUserResolver
{
    /**
     * Resolve or create a user safely for seeders.
     * 
     * If a user with the same email already exists:
     * - If the name matches (same person), reuse the user.
     * - If the name is DIFFERENT (different person having email collision in seeder),
     *   generate a unique email for this new person and create their own account.
     */
    public static function resolve(string $name, string $email, string $password, ?int $unitId = null): User
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            return $user;
        }

        // Check if existing user is the SAME person
        if (self::isSamePerson($user->name, $name)) {
            return $user;
        }

        // Different person has collided with this email!
        // Generate a new unique email based on their full name
        $domain = explode('@', $email)[1] ?? 'namira.school';
        $words = array_values(array_filter(explode(' ', self::normalize($name))));
        $base = implode('.', array_slice($words, 0, 2)) ?: 'user';

        $candidate = "{$base}@{$domain}";
        $i = 2;
        while (User::where('email', $candidate)->exists()) {
            $candidate = "{$base}.{$i}@{$domain}";
            $i++;
        }

        $newUser = User::create([
            'name' => $name,
            'email' => $candidate,
            'password' => Hash::make($password),
        ]);
        $newUser->forceFill(['email_verified_at' => now()])->save();

        return $newUser;
    }

    private static function normalize(string $string): string
    {
        return Str::of($string)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();
    }

    private static function isSamePerson(string $nameA, string $nameB): bool
    {
        $normA = self::normalize($nameA);
        $normB = self::normalize($nameB);

        if ($normA === $normB) {
            return true;
        }

        // If one name is substring of the other and high similarity
        similar_text($normA, $normB, $percent);
        return $percent >= 80;
    }
}
