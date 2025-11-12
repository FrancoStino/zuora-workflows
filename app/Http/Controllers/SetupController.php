<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupController extends Controller
{
    /**
     * Check if setup is required
     */
    public static function isSetupRequired(): bool
    {
        try {
            if (!Schema::hasTable('setup_completed')) {
                return true;
            }

            $setupRecord = DB::table('setup_completed')->first();
            return !$setupRecord || !$setupRecord->completed;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if setup is completed
     */
    public static function isSetupCompleted(): bool
    {
        return !self::isSetupRequired();
    }
}
