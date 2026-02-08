@extends('layouts.app')

@section('title', 'スタッフ一覧')
@section('body_class', 'admin')

@section('content')
  <style>
    /* ===== Staff list (このページだけで見本寄せ) ===== */
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

    /* テーブル枠 */
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

  <div class="pageTitle">スタッフ一覧</div>

  <div class="tableWrap">
    <table>
      <thead>
        <tr>
          <th>名前</th>
          <th>メールアドレス</th>
          <th>月次勤怠</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>
              <a class="linkBtn" href="{{ route('admin.staff.attendance', $u) }}">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($users->isEmpty())
    <div class="emptyMsg">スタッフが存在しません。</div>
  @endif
@endsection
