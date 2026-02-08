@extends('layouts.app')

@section('title', '勤怠登録')

@section('content')
  <div class="card" style="max-width:720px;margin:0 auto;">
    <h1>勤怠登録</h1>

    {{-- FN018: 現在日時（UIと同じ形式で表示） --}}
    <div style="margin:12px 0;">
      <div class="kicker">現在日時</div>
      <div style="font-size:18px;font-weight:700;">
        {{ $now->format('Y-m-d H:i') }}
      </div>
    </div>

    {{-- FN019: ステータス表示 --}}
    <div style="margin:12px 0;">
      <div class="kicker">ステータス</div>
      <div style="font-size:18px;font-weight:700;">
        {{ $status }}
      </div>
    </div>

    {{-- 本日の記録（任意：確認用） --}}
    <div style="margin:12px 0;">
      <div class="kicker">本日の記録</div>
      <div style="display:flex;gap:18px;flex-wrap:wrap;">
        <div>出勤：{{ $attendance?->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '—' }}</div>
        <div>退勤：{{ $attendance?->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '—' }}</div>
      </div>
    </div>

    {{-- FN020〜FN022: ボタン出し分け --}}
    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
      @if ($status === '勤務外')
        {{-- FN020: 勤務外 → 出勤（1日1回） --}}
        <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;" novalidate>
          @csrf
          <input type="hidden" name="action" value="clock_in">
          <button class="btn" type="submit">出勤</button>
        </form>

      @elseif ($status === '出勤中')
        {{-- FN021: 出勤中 → 休憩入（何回でも） --}}
        <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;" novalidate>
          @csrf
          <input type="hidden" name="action" value="break_start">
          <button class="btn" type="submit">休憩入</button>
        </form>

        {{-- FN022: 出勤中 → 退勤（1日1回） --}}
        <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;" novalidate>
          @csrf
          <input type="hidden" name="action" value="clock_out">
          <button class="btn" type="submit">退勤</button>
        </form>

      @elseif ($status === '休憩中')
        {{-- FN021: 休憩中 → 休憩戻（何回でも） --}}
        <form method="POST" action="{{ route('attendance.stamp') }}" style="margin:0;" novalidate>
          @csrf
          <input type="hidden" name="action" value="break_end">
          <button class="btn" type="submit">休憩戻</button>
        </form>

      @elseif ($status === '退勤済')
        <div class="flash">本日の打刻は完了しています。</div>
      @endif
    </div>
  </div>
@endsection
