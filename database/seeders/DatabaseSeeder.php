<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        $tz = 'Asia/Tokyo';
        $today = Carbon::now($tz)->startOfDay();

        // 管理者・一般ユーザー（設計書のダミーデータ要件）
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'email_verified_at' => Carbon::now($tz),
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_admin' => false,
            'email_verified_at' => Carbon::now($tz),
        ]);

        $staff = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'is_admin' => false,
            'email_verified_at' => Carbon::now($tz),
        ]);

        // 便利関数：勤怠＋休憩を作成
        $makeAttendance = function (User $u, Carbon $date, string $start, ?string $end, array $breaks, ?string $note) use ($tz) {
            $startAt = $date->copy()->setTimeFromTimeString($start);
            $endAt = $end ? $date->copy()->setTimeFromTimeString($end) : null;

            $attendance = Attendance::create([
                'user_id' => $u->id,
                'date' => $date->copy(),
                'start_time' => $startAt,
                'end_time' => $endAt,
                'note' => $note,
            ]);
            foreach ($breaks as [$bs, $be]) {
                $attendance->breakTimes()->create([
                    'start_time' => $date->copy()->setTimeFromTimeString($bs),
                    'end_time' => $date->copy()->setTimeFromTimeString($be),
                ]);
            }

            return $attendance;
        };

        // 今日・昨日・一昨日あたりにデータを用意（画面に出やすくする）
        $attApproved = $makeAttendance(
            $user,
            $today->copy()->subDays(2),
            '09:00',
            '18:00',
            [['12:00', '13:00']],
            'ダミー（承認済み申請あり）'
        );

        $attPending = $makeAttendance(
            $user,
            $today->copy()->subDays(1),
            '09:10',
            '18:20',
            [['12:00', '13:00'], ['15:00', '15:15']],
            'ダミー（承認待ち申請あり）'
        );

        $makeAttendance(
            $user,
            $today->copy(),
            '09:00',
            null,
            [],
            'ダミー（出勤中）'
        );

        $makeAttendance(
            $staff,
            $today->copy(),
            '10:00',
            '19:00',
            [['13:00', '14:00']],
            'ダミー（スタッフ）'
        );

        // 承認済みの修正申請（管理者が承認した想定）
        $reqApproved = StampCorrectionRequest::create([
            'attendance_id' => $attApproved->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'requested_start_time' => $attApproved->start_time,
            'requested_end_time' => $attApproved->end_time ?? $attApproved->start_time->copy()->addHours(9),
            'requested_note' => '承認済み（ダミー）',
            'approved_at' => Carbon::now($tz),
            'approved_by' => $admin->id,
        ]);
        $reqApproved->breaks()->create([
            'start_time' => $attApproved->date->copy()->setTime(12, 0),
            'end_time' => $attApproved->date->copy()->setTime(13, 0),
        ]);

        // 承認待ちの修正申請（この勤怠はロックされる想定）
        $reqPending = StampCorrectionRequest::create([
            'attendance_id' => $attPending->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_start_time' => $attPending->date->copy()->setTime(9, 0),
            'requested_end_time' => $attPending->date->copy()->setTime(18, 0),
            'requested_note' => '承認待ち（ダミー）',
            'approved_at' => null,
            'approved_by' => null,
        ]);
        $reqPending->breaks()->create([
            'start_time' => $attPending->date->copy()->setTime(12, 0),
            'end_time' => $attPending->date->copy()->setTime(13, 0),
        ]);
    }
}