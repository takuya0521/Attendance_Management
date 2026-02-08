<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStampCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'string'],
            'end_time'   => ['required', 'string'],
            'note'       => ['required', 'string'],

            'break_start'   => ['array'],
            'break_start.*' => ['nullable', 'string'],
            'break_end'     => ['array'],
            'break_end.*'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    private function isHHMM($value): bool
    {
        if (!is_string($value)) return false;
        if (strlen($value) !== 5) return false;
        if ($value[2] !== ':') return false;

        $h = substr($value, 0, 2);
        $m = substr($value, 3, 2);

        if (!ctype_digit($h) || !ctype_digit($m)) return false;

        $hh = (int) $h;
        $mm = (int) $m;

        return (0 <= $hh && $hh <= 23) && (0 <= $mm && $mm <= 59);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // ★ここで fails() を呼ぶと再帰して落ちるので禁止
            // 既に別ルールで失敗しているなら、追加チェックはしない
            if (!empty($v->failed())) return;

            $attendance = $this->route('attendance');
            if (!$attendance || !$attendance->date) return;

            $date = $attendance->date->format('Y-m-d');

            $st = $this->input('start_time');
            $et = $this->input('end_time');

            // HH:MM 形式チェック（正規表現なし）
            $stOk = $this->isHHMM($st);
            $etOk = $this->isHHMM($et);

            if (!$stOk || !$etOk) {
                if (!$stOk) {
                    $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                }
                if (!$etOk) {
                    $v->errors()->add('end_time', '出勤時間もしくは退勤時間が不適切な値です');
                }
                return;
            }

            $start = strtotime($date . ' ' . $st);
            $end   = strtotime($date . ' ' . $et);

            if ($start === false || $end === false) {
                $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // ID11: 出勤が退勤より後（または同じ）はNG
            if ($start >= $end) {
                $v->errors()->add('start_time', '出勤時間が不適切な値です');
                return;
            }

            $bs = $this->input('break_start', []);
            $be = $this->input('break_end', []);

            $n = max(is_array($bs) ? count($bs) : 0, is_array($be) ? count($be) : 0);

            for ($i = 0; $i < $n; $i++) {
                $s = is_array($bs) ? ($bs[$i] ?? null) : null;
                $e = is_array($be) ? ($be[$i] ?? null) : null;

                // 空行は無視（フォームの未入力枠）
                if (($s === null || $s === '') && ($e === null || $e === '')) continue;

                // 片側だけ入力はNG
                if ($s === null || $s === '' || $e === null || $e === '') {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                // HH:MM 形式チェック
                if (!$this->isHHMM($s) || !$this->isHHMM($e)) {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                $bStart = strtotime($date . ' ' . $s);
                $bEnd   = strtotime($date . ' ' . $e);

                if ($bStart === false || $bEnd === false || $bStart >= $bEnd) {
                    $v->errors()->add("break_start.$i", '休憩時間が不適切な値です');
                    return;
                }

                // 勤務時間外の休憩はNG
                if ($bStart < $start || $bStart > $end) {
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
