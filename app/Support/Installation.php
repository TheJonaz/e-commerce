<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class Installation
{
    /**
     * Treat the shop as installed if either the lock file exists, or the DB
     * shows a complete install (users table with an admin row). The latter
     * heals the lock back into place when it's been accidentally removed.
     */
    public static function isInstalled(): bool
    {
        $lockPath = storage_path('install.lock');

        if (is_file($lockPath)) {
            return true;
        }

        try {
            if (Schema::hasTable('users') && User::where('role', User::ROLE_ADMIN)->exists()) {
                @file_put_contents($lockPath, now()->toIso8601String());
                return true;
            }
        } catch (\Throwable $e) {
            // DB unreachable or settings table not present — not installed.
        }

        return false;
    }
}
