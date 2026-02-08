@extends('layouts.app')

@section('title', '月次勤怠')
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== Monthly attendance (見本寄せ) ===== */
    body.admin { background:#f3f3f3; color:#111; }

    /* タイトル（左の縦バー） */
    .pageTitle{
      max-width:1100px;
      margin:52px auto 18px;
      padding:0 16px;
      display:flex;
      align-items:center;
      gap:16px;
      font-size:30px;
      font-weight:900;
      color:#111;
    }
    .pageTitle::before{
      content:"";
      width:6px;
      height:34px;
      background:#111;
      display:block;
    }

    /* 月バー（前月 / 2023/06 / 翌月） */
    .monthBar{
      max-width:920px;
      margin:0 auto 18px;
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:10px;
      padding:14px 20px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      color:#666;
      font-weight:800;
    }
    .monthBar__btn{
      color:#666;
      font-weight:800;
      text-decoration:none;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .monthBar__btn:hover{ opacity:.85; }

    .monthBar__mid{
      display:flex;
      align-items:center;
      gap:10px;
      color:#111;
      font-size:20px;
      font-weight:900;
      letter-spacing:.01em;
    }

    /* テーブル */
    .tableWrap{
      max-width:920px;
      margin:0 auto;
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:10px;
      overflow:hidden;
      box-shadow:0 2px 0 rgba(0,0,0,.06);
    }
    table{ width:100%; border-collapse:collapse; }
    thead th{
      background:#f6f6f6;
      color:#666;
      font-weight:800;
      padding:12px 14px;
      border-bottom:2px solid #e0e0e0;
      text-align:center;
      font-size:13px;
    }
    tbody td{
      padding:12px 14px;
      border-bottom:1px solid #e8e8e8;
      text-align:center;
      color:#555;
      font-weight:700;
    }
    tbody td:first-child{
      text-align:left;
      padding-left:22px;
      font-weight:800;
      color:#666;
    }

    .linkBtn{
      color:#000;
      font-weight:900;
      text-decoration:none;
    }
    .linkBtn:hover{ opacity:.8; }
    .linkBtn--disabled{ color:#bbb; cursor:default; pointer-events:none; }

    /* CSV出力（右下） */
    .actions{
      max-width:920px;
      margin:18px auto 0;
      display:flex;
      justify-content:flex-end;
      padding:0 6px;
    }
    .csvBtn{
      background:#000;
      border:1px solid #000;
      color:#fff;
      border-radius:4px;
      padding:12px 28px;
      font-size:16px;
      font-weight:900;
      text-decoration:none;
      display:inline-block;
    }
    .csvBtn:hover{ opacity:.9; }

    .emptyMsg{
      max-width:920px;
      margin:14px auto 0;
      color:#666;
      font-weight:700;
    }
  </style>

  @php
    // controller: AdminStaffAttendanceController@index で渡ってくる想定
    // $user, $base(Carbon), $days([['date'=>Carbon,'attendance'=>Attendance|null], ...]), $prevMonth('Y-m'), $nextMonth('Y-m')

    $userName = $user->name;

    $monthLabel = $base->format('Y/m');

    $prevUrl = url("/admin/attendance/staff/{$user->id}") . '?month=' . $prevMonth;
    $nextUrl = url("/admin/attendance/staff/{$user->id}") . '?month=' . $nextMonth;

    $csvUrl  = url("/admin/attendance/staff/{$user->id}/export") . '?month=' . $base->format('Y-m');

    $fmtHM2 = function (int $minutes) {
      $minutes = abs($minutes);
      $h = intdiv($minutes, 60);
      $m = $minutes % 60;
      return sprintf('%d:%02d', $h, $m);
    };
  @endphp

  <div class="pageTitle">{{ $userName }}さんの勤怠</div>

  <div class="monthBar">
    <a class="monthBar__btn" href="{{ $prevUrl }}">← 前月</a>

    <div class="monthBar__mid">
      <span aria-hidden="true">📅</span>
      <span>{{ $monthLabel }}</span>
    </div>

    <a class="monthBar__btn" href="{{ $nextUrl }}">翌月 →</a>
  </div>

  <div class="tableWrap">
    <table>
      <thead>
        <tr>
          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th>詳細</th>
        </tr>
      </thead>

      <tbody>
        @foreach($days as $row)
          @php
            /** @var \Carbon\CarbonInterface $d */
            $d = $row['date'];
            /** @var \App\Models\Attendance|null $a */
            $a = $row['attendance'];

            $breakMinutes = 0;
            if ($a) {
              foreach ($a->breakTimes as $b) {
                if ($b->start_time && $b->end_time) {
                  // DBの時刻が逆転しててもマイナス表示にならないように必ず正にする
                  $breakMinutes += abs($b->start_time->diffInMinutes($b->end_time, false));
                }
              }
            }

            $workMinutes = null;
            if ($a && $a->start_time && $a->end_time) {
              $total = $a->end_time->diffInMinutes($a->start_time);
              $workMinutes = max(0, $total - $breakMinutes);
            }

            // 詳細は attendance がある日のみ飛べる
            $detailUrl = $a ? route('admin.attendance.show', $a) : null;
          @endphp

          <tr>
            <td>{{ $d->format('m/d') }}({{ ['日','月','火','水','木','金','土'][$d->dayOfWeek] }})</td>
            <td>{{ $a?->start_time?->format('H:i') ?? '' }}</td>
            <td>{{ $a?->end_time?->format('H:i') ?? '' }}</td>
            <td>{{ $breakMinutes ? $fmtHM2($breakMinutes) : '' }}</td>
            <td>{{ is_null($workMinutes) ? '' : $fmtHM2($workMinutes) }}</td>
            <td>
              @if($detailUrl)
                <a class="linkBtn" href="{{ $detailUrl }}">詳細</a>
              @else
                <span class="linkBtn linkBtn--disabled">詳細</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="actions">
    <a class="csvBtn" href="{{ $csvUrl }}">CSV出力</a>
  </div>

  @if(empty($days))
    <div class="emptyMsg">この月の勤怠データがありません。</div>
  @endif
@endsection
