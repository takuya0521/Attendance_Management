<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminUpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i'],
            'note'       => ['required', 'string'],

            'break_start'   => ['array'],
            'break_start.*' => ['nullable', 'date_format:H:i'],
            'break_end'     => ['array'],
            'break_end.*'   => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // after() の中で fails() を呼ぶと再帰的に評価されて落ちることがあるため、
            // 失敗ルール配列を参照して早期returnする（結果は同じ）
            if (!empty($v->failed())) return;

            /** @var \App\Models\Attendance $attendance */
            $attendance = $this->route('attendance');
            $date = $attendance->date->format('Y-m-d');

            $start = strtotime($date . ' ' . $this->input('start_time'));
            $end   = strtotime($date . ' ' . $this->input('end_time'));

            if ($start === false || $end === false || $start > $end) {
                $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $bs = $this->input('break_start', []);
            $be = $this->input('break_end', []);

            $n = max(count($bs), count($be));
            for ($i = 0; $i < $n; $i++) {
                $s = $bs[$i] ?? null;
                $e = $be[$i] ?? null;

                if (!$s && !$e) continue;

                if (!$s || !$e) {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                $bStart = strtotime($date . ' ' . $s);
                $bEnd   = strtotime($date . ' ' . $e);

                if ($bStart === false || $bEnd === false || $bStart > $bEnd) {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                if ($bStart < $start) {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                if ($bStart > $end) {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                if ($bEnd > $end) {
                    $v->errors()->add("break_end.$i", '休憩時間もしくは退勤時間が不適切な値です');
                    return;
                }
            }
        });
    }
}
