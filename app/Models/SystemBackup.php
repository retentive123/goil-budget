<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemBackup extends Model
{
    protected $fillable = [
        'filename','path','size_bytes','type',
        'status','notes','error_message',
        'created_by','completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'size_bytes'   => 'integer',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sizeForHumans(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
        return $bytes . ' B';
    }
}
