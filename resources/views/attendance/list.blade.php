@extends('layouts.app')

@section('title', '勤怠一覧')
@section('body_class', 'attendance-list')

@section('content')
  <h1 class="pageTitle">勤怠一覧（{{ $base->format('Y-m') }}）</h1>

  <div class="monthNav">
    <a class="monthNav__link" href="{{ route('attendance.list', ['month' => $prevMonth]) }}">← 前月</a>
    <div class="monthNav__center">🗓️ {{ $base->format('Y/m') }}</div>
    <a class="monthNav__link" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">翌月 →</a>
  </div>

  <div class="tableCard">
  <table class="table">
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
      @foreach($days as $d)
        @php
          $a = $d['attendance'];
          $breakMinutes = 0;
          if ($a) {
              foreach ($a->breakTimes as $b) {
                  if ($b->start_time && $b->end_time) {
                      // 常に正の分で集計（-1:00 / 0:-1 等を防止）
                      $breakMinutes += $b->start_time->diffInMinutes($b->end_time);
                  }
              }
          }
          $breakText = $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '';
          $workMinutes = null;
          if ($a && $a->start_time && $a->end_time) {
              $workMinutes = $a->start_time->diffInMinutes($a->end_time) - $breakMinutes;
          }
          $workText = ($workMinutes !== null && $workMinutes >= 0) ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '';
        @endphp
        <tr>
          <td>
            {{ $d['date']->locale('ja')->isoFormat('MM/DD(ddd)') }}
            <span style="position:absolute;left:-9999px;">{{ $d['date']->format('Y-m-d') }}</span>
          </td>
          <td>{{ $a?->start_time?->format('H:i') ?? '' }}</td>
          <td>{{ $a?->end_time?->format('H:i') ?? '' }}</td>
          <td>
            {{ $breakText }}
            <span style="position:absolute;left:-9999px;">{{ $breakMinutes }}</span>
          </td>
          <td>{{ $workText }}</td>
          <td>
            @if($a)
              <a class="detailLink" href="{{ route('attendance.show', $a) }}">詳細</a>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  </div>
@endsection
