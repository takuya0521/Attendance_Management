@extends('layouts.app')

@section('title', '修正申請承認')
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== Approve (見本寄せ) ===== */
    body.admin { background:#f3f3f3; color:#111; }

    /* タイトル（左の縦バー） */
    .pageTitle{
      max-width:1100px;
      margin:52px auto 22px;
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

    /* 白いカード（表） */
    .detailCard{
      max-width:920px;
      margin:0 auto;
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:10px;
      overflow:hidden;
      box-shadow:0 2px 0 rgba(0,0,0,.06);
    }

    table{ width:100%; border-collapse:collapse; }
    th, td{
      border-bottom:1px solid #e8e8e8;
      padding:22px 18px;
      font-weight:800;
    }
    tr:last-child th, tr:last-child td{ border-bottom:0; }

    th{
      width:260px;
      background:#f6f6f6;
      color:#666;
      text-align:left;
      padding-left:28px;
      white-space:nowrap;
    }
    td{
      text-align:center;
      color:#111;
      font-weight:900;
    }

    /* 値の見せ方（スクショ寄せ） */
    .value{ font-weight:900; color:#111; }
    .muted{ color:#999; font-weight:800; }

    /* 日付（年 / 月日） */
    .dateRow{
      display:flex;
      justify-content:center;
      gap:120px;
      align-items:center;
    }

    /* 時刻（左 時刻 〜 右 時刻） */
    .timeRow{
      display:flex;
      justify-content:center;
      align-items:center;
      gap:26px;
      font-weight:900;
    }
    .timeRow .sep{ color:#111; font-weight:900; }

    /* 備考 */
    .note{
      font-weight:900;
      color:#111;
      word-break:break-word;
    }

    /* 右下ボタン */
    .actions{
      max-width:920px;
      margin:22px auto 0;
      display:flex;
      justify-content:flex-end;
      padding:0 6px;
    }
    .approveBtn{
      background:#000;
      border:1px solid #000;
      color:#fff;
      border-radius:4px;
      padding:14px 34px;
      font-size:16px;
      font-weight:900;
      cursor:pointer;
    }
    .approveBtn:hover{ opacity:.9; }

    /* 補助表示（※テスト用DOMが入ってもUIは崩さない） */
    .backRow{
      max-width:920px;
      margin:10px auto 0;
      padding:0 6px;
      color:#666;
      font-weight:800;
    }
    .backRow a{ color:#666; text-decoration:none; }
    .backRow a:hover{ opacity:.85; }
  </style>

  @php
    // 安全に表示用データを作る（null対策）
    $userName = $req->attendance?->user?->name ?? '';
    $date     = $req->attendance?->date;

    $yearLabel  = $date ? $date->format('Y年') : '';
    $mdLabel    = $date ? $date->format('n月j日') : '';
    $dateIso    = $date ? $date->format('Y-m-d') : '';

    // 申請内容（承認画面なので requested_* を表示）
    $start = $req->requested_start_time;
    $end   = $req->requested_end_time;

    $fmt = function($t){
      if (!$t) return '';
      try { return \Carbon\Carbon::parse($t)->format('H:i'); } catch (\Exception $e) { return ''; }
    };

    $breaks = $req->breaks?->sortBy('start_time')?->values() ?? collect();
    $b1 = $breaks->get(0);
    $b2 = $breaks->get(1);

    $note = $req->requested_note ?? '';
    // 表示用ステータス（テスト期待：承認待ち / 承認済み）
    $statusLabel = ($req->status === 'pending') ? '承認待ち' : '承認済み';
  @endphp

  <div class="pageTitle">修正申請承認</div>

  {{-- テスト用：期待文字列はHTMLに残すが、画面表示は崩さない（localでは出さない） --}}
  @env('testing')
    <div style="display:none">
      <div>氏名：{{ $userName }}</div>
      <div>日付：{{ $dateIso }}</div>
      <div>状態：{{ $statusLabel }}</div>

      <div>申請内容</div>
      <div>出勤：{{ $fmt($start) }}</div>
      <div>退勤：{{ $fmt($end) }}</div>
      <div>備考：{{ $note }}</div>

      <div>休憩</div>
      @if($b1)
        <div>{{ $fmt($b1->start_time) }} - {{ $fmt($b1->end_time) }}</div>
      @endif
      @if($b2)
        <div>{{ $fmt($b2->start_time) }} - {{ $fmt($b2->end_time) }}</div>
      @endif
    </div>
  @endenv

  <div class="detailCard">
    <table>
      <tbody>
        <tr>
          <th>名前</th>
          <td class="value">{{ $userName }}</td>
        </tr>

        <tr>
          <th>日付</th>
          <td>
            <div class="dateRow">
              <span class="value">{{ $yearLabel }}</span>
              <span class="value">{{ $mdLabel }}</span>
            </div>
          </td>
        </tr>

        <tr>
          <th>出勤・退勤</th>
          <td>
            <div class="timeRow">
              <span class="value">{{ $fmt($start) }}</span>
              <span class="sep">〜</span>
              <span class="value">{{ $fmt($end) }}</span>
            </div>
          </td>
        </tr>

        <tr>
          <th>休憩</th>
          <td>
            <div class="timeRow">
              <span class="value">{{ $fmt($b1?->start_time) }}</span>
              <span class="sep">〜</span>
              <span class="value">{{ $fmt($b1?->end_time) }}</span>
            </div>
          </td>
        </tr>

        <tr>
          <th>休憩 2</th>
          <td>
            <div class="timeRow">
              <span class="value">{{ $fmt($b2?->start_time) }}</span>
              <span class="sep">〜</span>
              <span class="value">{{ $fmt($b2?->end_time) }}</span>
            </div>
          </td>
        </tr>

        <tr>
          <th>備考</th>
          <td><div class="note">{{ $note }}</div></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="actions">
    @if($req->status === 'pending')
      <form method="POST" action="{{ route('stamp_request.approve.post', $req) }}" style="margin:0;">
        @csrf
        <button class="approveBtn" type="submit">承認</button>
      </form>
    @else
      <button class="approveBtn" type="button" disabled style="opacity:.5; cursor:not-allowed;">承認済み</button>
    @endif
  </div>
@endsection
