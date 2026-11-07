<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MenTalk Certificate {{ $certificate->serial }}</title>
<style>
{!! $fonts !!}
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
html, body { width: 842px; height: 595px; overflow: hidden; }
body { position: relative; font-family: 'Inter', sans-serif; color: #0B1F3A; }
.art { position: absolute; inset: 0; width: 842px; height: 595px; }
.abs { position: absolute; }
.serif { font-family: 'Playfair Display', serif; font-weight: 600; font-variant-numeric: lining-nums; font-feature-settings: "lnum" 1; }
.italic { font-family: 'Playfair Display', serif; font-style: italic; font-weight: 400; }
.caps { font-weight: 600; letter-spacing: .2em; text-transform: uppercase; }

.brand { left: 0; top: 40px; width: 236px; display: flex; align-items: center; justify-content: center; gap: 9px; }
.brand img { width: 30px; height: 30px; border-radius: 8px; }
.brand span { color: #FBF8F1; font-size: 19px; letter-spacing: .01em; }
.cefr { left: 0; top: 189px; width: 236px; text-align: center; color: #F4E3B5; font-size: 60px; line-height: 72px; }
.cefr.wide { font-size: 44px; }
.cefr-note { left: 0; top: 262px; width: 236px; text-align: center; color: #D9BE7C; font-size: 6.5px; }
.level { left: 0; top: 338px; width: 236px; text-align: center; color: #FBF8F1; font-size: 17px; }
.level-note { left: 0; top: 364px; width: 236px; text-align: center; color: #AFC0D8; font-size: 9.5px; }
.qr { left: 83px; top: 421px; width: 70px; height: 70px; }
.qr svg { display: block; width: 70px; height: 70px; }
.scan { left: 0; top: 510px; width: 236px; text-align: center; color: #D9BE7C; font-size: 6.5px; }
.link { left: 14px; top: 524px; width: 208px; text-align: center; color: #AFC0D8; font-size: 7px; line-height: 10px; word-break: break-all; }

.eyebrow { left: 284px; top: 46px; color: #A67C2D; font-size: 8.5px; letter-spacing: .3em; }
.title { left: 284px; top: 62px; font-size: 29px; line-height: 38px; }
.certify { left: 284px; top: 121px; color: #5B6B82; font-size: 14px; }
.name { left: 284px; top: 140px; width: 512px; height: 62px; display: flex; align-items: center; line-height: 1.16; overflow: hidden; }
.statement { left: 284px; top: 222px; width: 512px; color: #33445E; font-size: 12px; line-height: 19px; }
.statement b { color: #0B1F3A; font-weight: 600; }

.score { top: 286px; width: 121px; height: 96px; text-align: center; }
.score .label { position: absolute; left: 0; right: 0; top: 14px; color: #6B7A90; font-size: 7px; letter-spacing: .16em; }
.score .value { position: absolute; left: 0; right: 0; top: 28px; font-size: 32px; line-height: 40px; }
.score .max { position: absolute; left: 0; right: 0; top: 70px; color: #8592A6; font-size: 8px; }
.score.total .label { color: #D9BE7C; }
.score.total .value { color: #F4E3B5; }
.score.total .max { color: #AFC0D8; }

.meta { top: 406px; }
.meta .label { color: #6B7A90; font-size: 7px; letter-spacing: .16em; }
.meta .value { margin-top: 5px; font-size: 12.5px; font-weight: 600; }
.issuer { left: 284px; top: 470px; width: 300px; }
.issuer .sign { font-size: 19px; line-height: 26px; }
.issuer .role { margin-top: 6px; padding-top: 6px; border-top: 1px solid #C9A85C; width: 190px; color: #6B7A90; font-size: 8px; }
.notice { left: 284px; top: 529px; width: 340px; color: #7C899C; font-size: 7.5px; line-height: 11px; }
.mark { left: 708px; top: 487px; width: 36px; height: 36px; border-radius: 10px; }
</style>
</head>
<body>
<svg class="art" viewBox="0 0 842 595" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="night" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#143A6B"/><stop offset=".55" stop-color="#0B1F3A"/><stop offset="1" stop-color="#071527"/>
    </linearGradient>
    <linearGradient id="gold" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#8F6A24"/><stop offset=".3" stop-color="#E9CF8F"/><stop offset=".55" stop-color="#B98F3E"/><stop offset=".8" stop-color="#F1DDA6"/><stop offset="1" stop-color="#8F6A24"/>
    </linearGradient>
    <radialGradient id="glow" cx=".5" cy=".5" r=".5">
      <stop offset="0" stop-color="#1F5CA8" stop-opacity=".55"/><stop offset="1" stop-color="#1F5CA8" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="seal" cx=".5" cy=".42" r=".6">
      <stop offset="0" stop-color="#17406F"/><stop offset="1" stop-color="#0A1C35"/>
    </radialGradient>
  </defs>

  <rect width="842" height="595" fill="#FBF8F1"/>
  <g fill="none" stroke="url(#gold)" stroke-width=".5" transform="translate(726 505) scale(.76) translate(-118 -232)">
    @foreach ($rosette as $path)
      <path d="{{ $path }}"/>
    @endforeach
  </g>
  <circle cx="726" cy="505" r="45" fill="#FBF8F1" stroke="url(#gold)" stroke-width="1.2"/>
  <circle cx="726" cy="505" r="40.5" fill="none" stroke="#C9A85C" stroke-width=".5" stroke-dasharray="1 2.4"/>

  <rect width="236" height="595" fill="url(#night)"/>
  <g fill="none" stroke="#D9BE7C" stroke-width=".4" opacity=".16">
    @foreach ($waves as $path)
      <path d="{{ $path }}"/>
    @endforeach
  </g>
  <circle cx="118" cy="232" r="118" fill="url(#glow)"/>
  <rect x="236" width="2.5" height="595" fill="url(#gold)"/>

  <g fill="none" stroke="url(#gold)" stroke-width=".55">
    @foreach ($rosette as $path)
      <path d="{{ $path }}"/>
    @endforeach
  </g>
  <circle cx="118" cy="232" r="60" fill="url(#seal)" stroke="url(#gold)" stroke-width="1.6"/>
  <circle cx="118" cy="232" r="54" fill="none" stroke="#D9BE7C" stroke-width=".5" stroke-dasharray="1 2.4"/>

  <rect x="77" y="415" width="82" height="82" rx="7" fill="#FBF8F1"/>

  <rect x="252" y="14" width="576" height="567" fill="none" stroke="url(#gold)" stroke-width="1.1"/>
  <rect x="256" y="18" width="568" height="559" fill="none" stroke="#C9A85C" stroke-width=".35"/>

  <rect x="284" y="207.5" width="216" height="1" fill="url(#gold)"/>
  <path d="M506 204l4 4-4 4-4-4z" fill="#B98F3E"/>
  <path d="M516 208H796" stroke="#C9A85C" stroke-width=".35"/>

  @foreach ([284, 415, 546] as $x)
    <rect x="{{ $x }}" y="286" width="121" height="96" rx="6" fill="#FFFFFF" stroke="#E4D8BC" stroke-width=".8"/>
  @endforeach
  <rect x="677" y="286" width="121" height="96" rx="6" fill="url(#night)"/>
  <rect x="680.5" y="289.5" width="114" height="89" rx="4" fill="none" stroke="#D9BE7C" stroke-width=".5"/>
</svg>

<div class="abs brand"><img src="{{ $logo }}" alt=""><span class="serif">MenTalk</span></div>
<div class="abs cefr serif {{ strlen($certificate->level->cefr()) > 2 ? 'wide' : '' }}">{{ $certificate->level->cefr() }}</div>
<div class="abs cefr-note caps">CEFR estimate</div>
<div class="abs level serif">{{ $certificate->level->label() }}</div>
<div class="abs level-note">{{ $language }} language</div>
<div class="abs qr">{!! $qr !!}</div>
<div class="abs scan caps">Scan to verify</div>
<div class="abs link">{{ $verifyLabel }}</div>

<div class="abs eyebrow caps">MenTalk Language Assessment</div>
<div class="abs title serif">Certificate of Language Proficiency</div>
<div class="abs certify italic">This is to certify that</div>
<div class="abs name serif" style="font-size: {{ $nameSize }}px">{{ $certificate->holder_name }}</div>
<div class="abs statement">
  has demonstrated <b>{{ $certificate->level->label() }} ({{ $certificate->level->cefr() }})</b> proficiency in <b>{{ $language }}</b>,
  assessed through {{ $certificate->basis() }}.
</div>

@foreach ([['Speaking', $certificate->speaking, 284], ['Vocabulary', $certificate->vocabulary, 415], ['Grammar', $certificate->grammar, 546]] as [$label, $value, $x])
  <div class="abs score" style="left: {{ $x }}px">
    <div class="label caps">{{ $label }}</div>
    {{-- A skill no activity measured yet (no conversation) says so, never shows a zero. --}}
    <div class="value serif">{{ $value === null ? '—' : number_format($value, 1) }}</div>
    <div class="max">{{ $value === null ? 'not assessed' : 'out of 10' }}</div>
  </div>
@endforeach
<div class="abs score total" style="left: 677px">
  <div class="label caps">Overall</div>
  <div class="value serif">{{ number_format($certificate->overall, 1) }}</div>
  <div class="max">out of 10</div>
</div>

<div class="abs meta" style="left: 284px"><div class="label caps">Certificate No.</div><div class="value">{{ $certificate->serial }}</div></div>
<div class="abs meta" style="left: 480px"><div class="label caps">Date of issue</div><div class="value">{{ $certificate->issued_at->format('j F Y') }}</div></div>
@if ($certificate->placement_score !== null)
  <div class="abs meta" style="left: 660px"><div class="label caps">Placement test</div><div class="value">{{ $certificate->placement_score }}%</div></div>
@else
  <div class="abs meta" style="left: 660px"><div class="label caps">Saved words</div><div class="value">{{ $certificate->words }}</div></div>
@endif

<img class="abs mark" src="{{ $logo }}" alt="">

<div class="abs issuer">
  <div class="sign italic">MenTalk</div>
  <div class="role">Issued electronically by MenTalk</div>
</div>

<div class="abs notice">
  Scores reflect the holder's activity in the MenTalk app up to the date of issue: recent rated conversations and saved vocabulary. This certificate reflects in-app assessment
  and is not the result of an official or proctored language examination.
</div>
</body>
</html>
