<?php

namespace Modules\Addons\DriveBackup\Models;

use Illuminate\Database\Eloquent\Model;

class DriveBackupLog extends Model
{
    protected $table = 'gdrive_backup_logs';

    protected $fillable = [
        'type',
        'status',
        'file_name',
        'drive_file_id',
        'size_bytes',
        'message',
        'triggered_by',
    ];
}
