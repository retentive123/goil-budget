<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['event_key', 'label', 'subject', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Retrieve a template by its event key.
     * Returns null if not found or inactive.
     */
    public static function forEvent(string $key): ?self
    {
        return static::where('event_key', $key)->where('is_active', true)->first();
    }

    /**
     * Render subject and body with variable substitution.
     * Variables: {approver_name}, {dept_name}, {period_name}, {version}, {comments}, {deadline}
     */
    public function render(array $vars): array
    {
        $replace = fn(string $text) => str_replace(
            array_map(fn($k) => '{'.$k.'}', array_keys($vars)),
            array_values($vars),
            $text
        );

        return [
            'subject' => $replace($this->subject),
            'body'    => $replace($this->body),
        ];
    }
}
