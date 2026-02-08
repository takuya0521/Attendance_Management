<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequestBreakTime extends Model
{
    protected $table = 'stamp_correction_request_break_times';

    protected $fillable = [
        'stamp_correction_request_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(StampCorrectionRequest::class, 'stamp_correction_request_id');
    }
}
