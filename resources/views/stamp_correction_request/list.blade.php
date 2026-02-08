@extends('layouts.app')

@section('title', '申請一覧')
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== Request list (見本寄せ) ===== */
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

    /* タブ（承認待ち / 承認済み） */
    .tabs{
      max-width:920px;
      margin:0 auto 0;
      padding:0 6px;
    }
    .tabs__inner{
      display:flex;
      gap:80px;
      align-items:flex-end;
      padding:18px 0 10px;
    }
    .tab{
      color:#111;
      font-weight:900;
      text-decoration:none;
      padding:10px 6px;
      position:relative;
      opacity:.75;
    }
    .tab.is-active{ opacity:1; }
    .tab.is-active::after{
      content:"";
      position:absolute;
      left:0;
      right:0;
      bottom:-10px;
      height:2px;
      background:#111;
    }
    .tabs__line{
      height:2px;
      background:#111;
      opacity:.35;
      width:100%;
      margin-top:10px;
    }

    /* テーブル */
    .tableWrap{
      max-width:920px;
      margin:26px auto 0;
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
      white-space:nowrap;
    }
    tbody td{
      padding:12px 14px;
      border-bottom:1px solid #e8e8e8;
      text-align:center;
      color:#555;
      font-weight:700;
      white-space:nowrap;
    }
    tbody tr:last-child td{ border-bottom:0; }

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
      padding:0 6px;
    }
  </style>

  @php
    // status は controller から渡ってくる想定（無ければクエリから）
    $status = $status ?? request('status', 'pending');
    $isPending = ($status === 'pending');

    $pendingUrl  = route('stamp_request.index', ['status' => 'pending']);
    $approvedUrl = route('stamp_request.index', ['status' => 'approved']);

    $labelStatus = function($s){
      return $s === 'pending' ? '承認待ち' : '承認済み';
    };

    $fmtDate = function($v){
      if (!$v) return '';
      try { return \Carbon\Carbon::parse($v)->format('Y/m/d'); } catch (\Throwable $e) { return ''; }
    };
  @endphp

  <div class="pageTitle">申請一覧</div>

  <div class="tabs">
    <div class="tabs__inner">
      <a class="tab {{ $isPending ? 'is-active' : '' }}" href="{{ $pendingUrl }}">承認待ち</a>
      <a class="tab {{ !$isPending ? 'is-active' : '' }}" href="{{ $approvedUrl }}">承認済み</a>
    </div>
    <div class="tabs__line" aria-hidden="true"></div>
  </div>

  <div class="tableWrap">
    <table>
      <thead>
        <tr>
          <th>状態</th>
          <th>名前</th>
          <th>対象日時</th>
          <th>申請理由</th>
          <th>申請日時</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @foreach($requests as $r)
          @php
            $attendance = $r->attendance ?? null;

            // ✅ Optionalを“最後の値”に使わない（stringを返す）
            $userName  = (string) optional(optional($attendance)->user)->name;

            // date が Carbon / string 両対応
            $targetDate = $attendance ? $fmtDate($attendance->date) : '';

            $reason    = $r->requested_note ?? '';
            $appliedAt = $fmtDate($r->created_at);

            // 詳細URL（admin / user）
            if (auth()->user()->is_admin) {
              $detailUrl = route('stamp_request.approve.show', $r) . '?status=' . $status;
            } else {
              $detailUrl = $attendance ? route('attendance.show', $attendance) : '#';
            }
          @endphp
          <tr>
            <td>{{ $labelStatus($r->status) }}</td>
            <td>{{ $userName }}</td>
            <td>{{ $targetDate }}</td>
            <td>{{ $reason }}</td>
            <td>{{ $appliedAt }}</td>
            <td><a class="linkBtn" href="{{ $detailUrl }}">詳細</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($requests->isEmpty())
    <div class="emptyMsg">該当する申請はありません。</div>
  @endif
@endsection
