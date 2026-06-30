<?php

namespace App\Services;

use App\Models\SystemSetting;

class SegregationService
{
    public static function enabled(): bool
    {
        return (bool) SystemSetting::get('enforce_segregation_of_duties', true);
    }

    /**
     * Check if the current user can authorise a record.
     *
     * @param  int|null  $createdById   — the user who created/submitted the record
     * @param  string    $action        — human-readable action e.g. "approve this budget"
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public static function canAuthorise(?int $createdById, string $action = 'authorise this record'): array
    {
        if (!static::enabled()) {
            return ['allowed' => true, 'reason' => ''];
        }

        if (is_null($createdById)) {
            return ['allowed' => true, 'reason' => ''];
        }

        if (auth()->id() === $createdById) {
            return [
                'allowed' => false,
                'reason'  => "You cannot {$action} because you were the one who submitted it. " .
                             "Segregation of duties requires a different user to authorise.",
            ];
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * Abort with a consistent error response if not allowed.
     * Works for both web (redirect back) and API (JSON).
     */
    public static function check(?int $createdById, string $action = 'authorise this record'): void
    {
        $result = static::canAuthorise($createdById, $action);

        if (!$result['allowed']) {
            if (request()->expectsJson()) {
                abort(response()->json(['message' => $result['reason']], 403));
            }

            // Store in session so view can show it nicely
            session()->flash('segregation_error', $result['reason']);

            abort(redirect()->back()->with('error', $result['reason']));
        }
    }
}
