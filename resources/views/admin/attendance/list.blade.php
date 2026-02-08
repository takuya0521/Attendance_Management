@extends('layouts.app')

@section('title', '勤怠一覧')
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== Admin look (このページだけで完結させる) ===== */
    body.admin { background:#f3f3f3 !important; color:#111 !important; }

    /* ヘッダー右のナビを「文字リンク」っぽく（既存pillを上書き） */
    body.admin .nav { gap:28px; }
    body.admin .pill{
      border:0 !important;
      padding:0 !important;
      border-radius:0 !important;
      background:transparent !important;
      color:#fff !important;
      font-weight:700;
      letter-spacing:.02em;
    }
    body.admin .pill:hover{ opacity:.85; }

    body.admin .btn{
      border:0 !important;
      background:transparent !important;
      padding:0 !important;
      border-radius:0 !important;
      color:#fff !important;
      font-weight:700;
      letter-spacing:.02em;
    }
    body.admin .btn:hover{ opacity:.85; filter:none !important; }

    /* 見本のタイトル（左の縦バー付き） */
    .pageTitle{
      max-width:1100px;
      margin:52px auto 22px;
      padding:0 16px;
      display:flex;
      align-items:center;
      gap:16px;
      font-size:32px;
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

    /* 日付バー */
    .dateBar{
      max-width:920px;
      margin:0 auto 28px;
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:10px;
      padding:16px 22px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      color:#666;
      font-weight:700;
    }
    .dateBar__btn{
      color:#666;
      font-weight:800;
      text-decoration:none;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .dateBar__btn:hover{ opacity:.85; }

    .dateBar__mid{
      display:flex;
      align-items:center;
      gap:10px;
      color:#111;
      font-size:22px;
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
      padding:14px 16px;
      border-bottom:2px solid #e0e0e0;
      text-align:center;
      font-size:14px;
    }
    tbody td{
      padding:14px 16px;
      border-bottom:1px solid #e8e8e8;
      text-align:center;
      color:#555;
      font-weight:700;
    }
    tbody td:first-child{ text-align:left; padding-left:22px; }
    .linkBtn{
      color:#000;
      font-weight:900;
      text-decoration:none;
    }
    .linkBtn:hover{ opacity:.8; }
    .emptyMsg{
      max-width:920px;
      margin:14px auto 0;
      color:#666;
      font-weight:700;
    }
  </style>

  @php
    // 見本は「2023年6月1日の勤怠」表記
    $titleDate = $date->format('Y年n月j日');
    $midDate = $date->format('Y/m/d');

    $fmt = function (int $minutes) {
      $minutes = abs($minutes);
      $h = intdiv($minutes, 60);
      $m = $minutes % 60;
      return sprintf('%d:%02d', $h, $m);
};
  @endphp

  <div class="pageTitle">{{ $titleDate }}の勤怠</div>

  <div class="dateBar">
    <a class="dateBar__btn" href="{{ route('admin.attendance.list', ['date' => $prev]) }}">
      ← 前日
    </a>

    <div class="dateBar__mid">
      <span aria-hidden="true">📅</span>
      <span>{{ $midDate }}</span>
    </div>

    <a class="dateBar__btn" href="{{ route('admin.attendance.list', ['date' => $next]) }}">
      翌日 →
    </a>
  </div>

  <div class="tableWrap">
    <table>
      <thead>
        <tr>
          <th>名前</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th>詳細</th>
        </tr>
      </thead>

      <tbody>
        @foreach($attendances as $a)
          @php
            $breakMinutes = 0;
            foreach ($a->breakTimes as $b) {
              if ($b->start_time && $b->end_time) {
                // 時刻が逆転しててもマイナス表示にならないよう必ず正にする
                $breakMinutes += abs($b->start_time->diffInMinutes($b->end_time, false));
              }
            }

            $workMinutes = null;
            if ($a->start_time && $a->end_time) {
              $total = $a->end_time->diffInMinutes($a->start_time);
              $workMinutes = max(0, $total - $breakMinutes);
            }
          @endphp

          <tr>
            <td>{{ $a->user->name }}</td>
            <td>{{ $a->start_time?->format('H:i') ?? '' }}</td>
            <td>{{ $a->end_time?->format('H:i') ?? '' }}</td>
            <td>{{ $breakMinutes ? $fmt($breakMinutes) : '' }}</td>
            <td>{{ is_null($workMinutes) ? '' : $fmt($workMinutes) }}</td>
            <td>
              <a class="linkBtn" href="{{ route('admin.attendance.show', $a) }}">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($attendances->isEmpty())
    <div class="emptyMsg">この日の勤怠データはありません。</div>
  @endif
@endsection
