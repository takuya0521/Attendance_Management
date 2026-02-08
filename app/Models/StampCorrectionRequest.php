<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'user_id',
        'status',
        'requested_start_time',
        'requested_end_time',
        'requested_note',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'requested_start_time' => 'datetime',
        'requested_end_time' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(StampCorrectionRequestBreakTime::class, 'stamp_correction_request_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
