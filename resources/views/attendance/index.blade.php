@extends('layouts.app')

@section('title', '勤怠登録')

{{-- 背景を白系に寄せるため、既存ページと同じクラス運用 --}}
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== Attendance stamp (見本寄せ) ===== */
    body.admin { background:#f3f3f3; color:#111; }

    .stampWrap{
      min-height: calc(100vh - 58px);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px 16px 80px;
      box-sizing:border-box;
    }

    .stampInner{
      width:100%;
      max-width:920px;
      text-align:center;
    }

    /* ステータスバッジ */
    .statusPill{
      display:inline-block;
      padding:8px 18px;
      border-radius:999px;
      background:#cfcfcf;
      color:#666;
      font-weight:800;
      letter-spacing:.02em;
      margin-bottom:22px;
    }

    /* 日付 */
    .dateText{
      font-size:32px;
      font-weight:900;
      margin:0 0 18px;
      color:#111;
    }

    /* 時刻 */
    .timeText{
      font-size:84px;
      font-weight:900;
      letter-spacing:.02em;
      margin:0 0 42px;
      color:#000;
    }

    /* ボタン群 */
    .btnRow{
      display:flex;
      justify-content:center;
      gap:44px;
      flex-wrap:wrap;
    }

    .btnBase{
      width:240px;
      height:78px;
      border-radius:18px;
      font-size:28px;
      font-weight:900;
      border:1px solid transparent;
      cursor:pointer;
    }

    .btnBlack{
      background:#000;
      border-color:#000;
      color:#fff;
    }

    .btnWhite{
      background:#fff;
      border-color:#e6e6e6;
      color:#111;
    }

    .thanks{
      margin-top:26px;
      font-size:26px;
      font-weight:900;
      color:#111;
    }

    /* SP */
    @media (max-width: 520px){
      .dateText{ font-size:24px; }
      .timeText{ font-size:64px; }
      .btnBase{ width:220px; height:72px; font-size:26px; }
      .btnRow{ gap:20px; }
    }
  </style>

  @php
    // 表示用
    $week = ['日','月','火','水','木','金','土'];
    $dateLabel = $now->format('Y年n月j日') . '(' . $week[$now->dayOfWeek] . ')';
    $timeLabel = $now->format('H:i');

    // コントローラ側の $status を想定：
    // '勤務外' / '出勤中' / '休憩中' / '退勤済'
    $statusLabel = $status ?? '勤務外';
  @endphp

  <div class="stampWrap">
    <div class="stampInner">
      <div class="statusPill">ステータス：{{ $statusLabel }}</div>

      <div class="dateText">{{ $dateLabel }}</div>
      <div class="timeText">{{ $timeLabel }}</div>

      {{-- 勤務外：出勤のみ --}}
      @if($statusLabel === '勤務外')
        <div class="btnRow">
          <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;">
            @csrf
            <input type="hidden" name="action" value="clock_in">
            <button type="submit" class="btnBase btnBlack">出勤</button>
          </form>
        </div>
      @endif

      {{-- 出勤中：退勤（黒）＋ 休憩入（白） --}}
      @if($statusLabel === '出勤中')
        <div class="btnRow">
          <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;">
            @csrf
            <input type="hidden" name="action" value="clock_out">
            <button type="submit" class="btnBase btnBlack">退勤</button>
          </form>

          <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;">
            @csrf
            <input type="hidden" name="action" value="break_start">
            <button type="submit" class="btnBase btnWhite">休憩入</button>
          </form>
        </div>
      @endif

      {{-- 休憩中：休憩戻（白） --}}
      @if($statusLabel === '休憩中')
        <div class="btnRow">
          <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;">
            @csrf
            <input type="hidden" name="action" value="break_end">
            <button type="submit" class="btnBase btnWhite">休憩戻</button>
          </form>
        </div>
      @endif

      {{-- 退勤済：メッセージのみ --}}
      @if($statusLabel === '退勤済')
        <div class="thanks">お疲れ様でした。</div>
      @endif
    </div>
  </div>
@endsection
