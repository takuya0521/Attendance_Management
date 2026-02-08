@extends('layouts.app')

@section('title', '勤怠詳細')
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== このページだけで見本寄せ（layoutを触らなくてもOK） ===== */
    body.admin { background:#f3f3f3; color:#111; }

    /* タイトル（左の縦バー） */
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

    /* 画面中央の白い枠 */
    .detailWrap{
      max-width:920px;
      margin:0 auto;
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:10px;
      overflow:hidden;
      box-shadow:0 2px 0 rgba(0,0,0,.06);
    }

    .detailTable{
      width:100%;
      border-collapse:collapse;
    }
    .detailTable tr{
      border-bottom:1px solid #e8e8e8;
    }
    .detailTable th,
    .detailTable td{
      padding:22px 22px;
      vertical-align:middle;
    }
    .detailTable th{
      width:240px;
      color:#666;
      font-weight:800;
      text-align:left;
      background:#fafafa;
    }

    /* 右側（内容側） */
    .cellCenter{
      text-align:center;
      font-weight:900;
      color:#111;
      letter-spacing:.02em;
    }
    .dateCell{
      display:flex;
      justify-content:center;
      gap:140px;
      font-weight:900;
      color:#111;
      letter-spacing:.02em;
    }

    /* 時刻入力 */
    .timeRow{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:18px;
    }
    .timeInput{
      width:92px;
      height:36px;
      border:1px solid #d8d8d8;
      border-radius:4px;
      text-align:center;
      font-weight:900;
      color:#111;
      background:#fff;
      outline:none;
    }
    .tilde{
      font-weight:900;
      color:#111;
    }

    /* 備考 */
    .noteArea{
      width:360px;
      height:64px;
      border:1px solid #d8d8d8;
      border-radius:4px;
      padding:10px 12px;
      resize:none;
      outline:none;
      font-weight:700;
    }

    /* エラー */
    .err{
      margin-top:8px;
      color:#ff6b6b;
      font-size:13px;
      font-weight:700;
      text-align:center;
    }

    /* フッター操作（ボタン右下） */
    .actions{
      max-width:920px;
      margin:18px auto 0;
      padding:0 6px;
      display:flex;
      justify-content:flex-end;
      align-items:center;
      gap:14px;
    }
    .saveBtn{
      background:#000;
      border:1px solid #000;
      color:#fff;
      border-radius:4px;
      padding:14px 42px;
      font-size:18px;
      font-weight:900;
      cursor:pointer;
    }
    .saveBtn:hover{ opacity:.9; }
    .backLink{
      color:#666;
      font-weight:800;
      text-decoration:none;
    }
    .backLink:hover{ opacity:.85; }

    /* lockedメッセージ */
    .lockedBox{
      max-width:920px;
      margin:0 auto;
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:10px;
      padding:18px 22px;
      color:#666;
      font-weight:800;
    }
  </style>

  @php
    $breaks = $attendance->breakTimes->sortBy('start_time')->values();
    $yearText = $attendance->date->format('Y年');
    $mdText = $attendance->date->format('n月j日');
  @endphp

  <div class="pageTitle">勤怠詳細</div>

  @if($locked)
    <div class="lockedBox">
      承認待ちのため修正はできません。
    </div>
  @else
    <form method="POST" action="{{ route('admin.attendance.update', $attendance) }}">
      @csrf

      @error('locked')
        <div class="lockedBox" style="border-color:#ff6b6b; color:#ff6b6b; margin-bottom:16px;">
          {{ $message }}
        </div>
      @enderror

      <div class="detailWrap">
        <table class="detailTable">
          <tr>
            <th>名前</th>
            <td class="cellCenter">{{ $attendance->user->name }}</td>
          </tr>

          <tr>
            <th>日付</th>
            <td>
              <div class="dateCell">
                <span>{{ $yearText }}</span>
                <span>{{ $mdText }}</span>
              </div>
            </td>
          </tr>

          <tr>
            <th>出勤・退勤</th>
            <td>
              <div class="timeRow">
                <input
                  class="timeInput @error('start_time') invalid @enderror"
                  name="start_time"
                  value="{{ old('start_time', $attendance->start_time?->format('H:i')) }}"
                  placeholder="09:00"
                >
                <span class="tilde">〜</span>
                <input
                  class="timeInput @error('end_time') invalid @enderror"
                  name="end_time"
                  value="{{ old('end_time', $attendance->end_time?->format('H:i')) }}"
                  placeholder="18:00"
                >
              </div>
              @error('start_time') <div class="err">{{ $message }}</div> @enderror
              @error('end_time') <div class="err">{{ $message }}</div> @enderror
            </td>
          </tr>

          @for($i=0; $i<2; $i++)
            <tr>
              <th>{{ $i === 0 ? '休憩' : '休憩 ' . ($i + 1) }}</th>
              <td>
                <div class="timeRow">
                  <input
                    class="timeInput @error("break_start.$i") invalid @enderror"
                    name="break_start[]"
                    value="{{ old("break_start.$i", $breaks->get($i)?->start_time?->format('H:i') ?? '') }}"
                    placeholder=""
                  >
                  <span class="tilde">〜</span>
                  <input
                    class="timeInput @error("break_end.$i") invalid @enderror"
                    name="break_end[]"
                    value="{{ old("break_end.$i", $breaks->get($i)?->end_time?->format('H:i') ?? '') }}"
                    placeholder=""
                  >
                </div>
                @error("break_start.$i") <div class="err">{{ $message }}</div> @enderror
                @error("break_end.$i") <div class="err">{{ $message }}</div> @enderror
              </td>
            </tr>
          @endfor

          <tr>
            <th>備考</th>
            <td>
              <div style="display:flex; justify-content:center;">
                <textarea class="noteArea @error('note') invalid @enderror" name="note">{{ old('note', $attendance->note) }}</textarea>
              </div>
              @error('note') <div class="err">{{ $message }}</div> @enderror
            </td>
          </tr>
        </table>
      </div>

      <div class="actions">
        <button class="saveBtn" type="submit">修正</button>
      </div>
    </form>
  @endif
@endsection
