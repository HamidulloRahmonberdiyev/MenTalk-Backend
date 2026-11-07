<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=595">
<title>MenTalk Certificate {{ $certificate->serial }}</title>
{{--
  A4 portrait, 595 x 842 CSS px, laid out like a test report form: numbered sections, ruled boxes,
  one result panel. Printed to PDF by the app and shown in it as is, so every position is absolute
  and nothing depends on the viewer's fonts or width.
--}}
<style>
{!! $fonts !!}
@page { margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
html, body { width: 595px; height: 842px; overflow: hidden; background: #FBF8F1; }
body { position: relative; font-family: 'Inter', sans-serif; color: #0B1F3A; -webkit-font-smoothing: antialiased; }
.art { position: absolute; inset: 0; width: 595px; height: 842px; }
.abs { position: absolute; }
.serif { font-family: 'Playfair Display', serif; font-weight: 600; font-variant-numeric: lining-nums; font-feature-settings: "lnum" 1; }
.italic { font-family: 'Playfair Display', serif; font-style: italic; font-weight: 400; }
.caps { font-weight: 600; letter-spacing: .2em; text-transform: uppercase; }
.center { left: 36px; width: 523px; text-align: center; }

/* Header band */
.brand { left: 36px; top: 33px; display: flex; align-items: center; gap: 11px; }
.brand img { width: 36px; height: 36px; border-radius: 9px; }
.brand .word { color: #FBF8F1; font-size: 21px; line-height: 24px; }
.brand .sub { margin-top: 3px; color: #D9BE7C; font-size: 6px; }
.ref { right: 36px; top: 30px; text-align: right; }
.ref .label { color: #D9BE7C; font-size: 5.8px; }
.ref .value { margin-top: 3px; margin-bottom: 7px; color: #FBF8F1; font-size: 11px; font-weight: 600; letter-spacing: .06em; }

/* Title */
.eyebrow { top: 120px; color: #A67C2D; font-size: 7px; letter-spacing: .34em; }
.title { top: 132px; font-size: 26px; line-height: 34px; }
.statement { top: 186px; left: 62px; width: 471px; text-align: center; color: #33445E; font-size: 9.6px; line-height: 15px; }
.statement b { color: #0B1F3A; font-weight: 600; }

/* Numbered section headings */
.sec { left: 36px; width: 523px; display: flex; align-items: center; gap: 8px; }
.sec .no { width: 14px; height: 14px; border-radius: 3px; background: #0B1F3A; color: #E9CF8F; font-size: 7.5px; font-weight: 600; display: flex; align-items: center; justify-content: center; }
.sec .name { color: #0B1F3A; font-size: 6.8px; letter-spacing: .22em; }
.sec .line { flex: 1; height: 1px; background: linear-gradient(90deg, #C9A85C, rgba(201,168,92,0)); }

/* Holder box */
.box { left: 36px; width: 523px; background: #FFFFFF; border: .8px solid #E4D8BC; border-radius: 4px; }
.cell { position: absolute; padding: 8px 12px; }
.cell .label { color: #8592A6; font-size: 5.8px; }
.cell .value { margin-top: 4px; font-size: 10.5px; font-weight: 600; line-height: 14px; }
.holder .value { margin-top: 3px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Result panel */
.cefr { left: 36px; width: 170px; text-align: center; color: #F4E3B5; font-size: 52px; line-height: 64px; }
.cefr.wide { font-size: 38px; }
.level { left: 36px; width: 170px; text-align: center; color: #FBF8F1; font-size: 15px; }
.level-note { left: 36px; width: 170px; text-align: center; color: #AFC0D8; font-size: 7.5px; }

.skill { left: 218px; width: 341px; height: 33px; background: #FFFFFF; border: .8px solid #E4D8BC; border-radius: 4px; display: flex; align-items: center; padding: 0 14px; }
.skill .label { width: 84px; color: #33445E; font-size: 6.5px; letter-spacing: .18em; }
.skill .bar { display: flex; gap: 2.5px; }
.skill .seg { width: 14px; height: 7px; border-radius: 1.5px; background: #EEE6D3; overflow: hidden; }
.skill .seg i { display: block; height: 100%; background: linear-gradient(90deg, #B98F3E, #E9CF8F); }
.skill .missing { color: #8592A6; font-size: 8.5px; }
.skill .score { margin-left: auto; display: flex; align-items: baseline; gap: 3px; }
.skill .score .value { font-size: 17px; line-height: 20px; }
.skill .score .max { color: #8592A6; font-size: 7px; }
.skill.total { background: linear-gradient(100deg, #143A6B, #0B1F3A 60%, #071527); border-color: #0B1F3A; }
.skill.total .label, .skill.total .score .value { color: #E9CF8F; }
.skill.total .score .max { color: #AFC0D8; }
.skill.total .seg { background: rgba(217,190,124,.18); }

/* CEFR scale */
.rung { top: 570px; width: 127.75px; height: 36px; border-radius: 4px; text-align: center; padding-top: 5px; }
.rung .code { font-size: 14px; line-height: 16px; }
.rung .name { margin-top: 2px; font-size: 5.5px; letter-spacing: .18em; }
.rung.done { background: #F3EBD7; color: #8F6A24; }
.rung.current { background: linear-gradient(100deg, #143A6B, #0B1F3A 60%, #071527); color: #F4E3B5; }
.rung.current .name { color: #D9BE7C; }
.rung.ahead { background: #FFFFFF; border: .8px solid #E4D8BC; color: #A3AEBD; }

.descriptor { top: 640px; left: 36px; width: 523px; color: #33445E; font-size: 10.5px; line-height: 15.5px; }

/* Footer */
.qr { left: 41px; top: 717px; width: 54px; height: 54px; }
.qr svg { display: block; width: 54px; height: 54px; }
.verify { left: 112px; top: 718px; width: 168px; }
.verify .label { color: #A67C2D; font-size: 6px; }
.verify .text { margin-top: 6px; color: #5B6B82; font-size: 7.5px; line-height: 11px; }
.verify .link { margin-top: 3px; color: #0B1F3A; font-size: 7.5px; font-weight: 600; line-height: 10px; word-break: break-all; }
.issuer { left: 300px; top: 716px; width: 160px; }
.issuer .sign { font-size: 22px; line-height: 28px; }
.issuer .role { margin-top: 5px; padding-top: 5px; border-top: .8px solid #C9A85C; color: #6B7A90; font-size: 6.5px; line-height: 9px; }
.mark { left: 515px; top: 730px; width: 24px; height: 24px; border-radius: 6px; }
.notice { top: 794px; left: 36px; width: 523px; text-align: center; color: #7C899C; font-size: 6.4px; line-height: 9.5px; }
</style>
</head>
<body>
<svg class="art" viewBox="0 0 595 842" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="night" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#143A6B"/><stop offset=".55" stop-color="#0B1F3A"/><stop offset="1" stop-color="#071527"/>
    </linearGradient>
    <linearGradient id="gold" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#8F6A24"/><stop offset=".3" stop-color="#E9CF8F"/><stop offset=".55" stop-color="#B98F3E"/><stop offset=".8" stop-color="#F1DDA6"/><stop offset="1" stop-color="#8F6A24"/>
    </linearGradient>
    <radialGradient id="seal" cx=".5" cy=".42" r=".6">
      <stop offset="0" stop-color="#17406F"/><stop offset="1" stop-color="#0A1C35"/>
    </radialGradient>
    <radialGradient id="glow" cx=".5" cy=".5" r=".5">
      <stop offset="0" stop-color="#1F5CA8" stop-opacity=".5"/><stop offset="1" stop-color="#1F5CA8" stop-opacity="0"/>
    </radialGradient>
    <clipPath id="band"><rect x="14" y="14" width="567" height="86"/></clipPath>
    <clipPath id="panel"><rect x="36" y="380" width="170" height="156" rx="5"/></clipPath>
  </defs>

  {{-- Paper and its double gold frame --}}
  <rect width="595" height="842" fill="#FBF8F1"/>
  <rect x="9.5" y="9.5" width="576" height="823" fill="none" stroke="url(#gold)" stroke-width="1.2"/>
  <rect x="14" y="14" width="567" height="814" fill="none" stroke="#C9A85C" stroke-width=".4"/>

  {{-- Header band: night blue with banknote waves, closed by a gold rule --}}
  <rect x="14" y="14" width="567" height="86" fill="url(#night)"/>
  <g clip-path="url(#band)" fill="none" stroke="#D9BE7C" stroke-width=".4" opacity=".2">
    <g transform="matrix(0 1 1 0 0 14)">
      @foreach ($waves as $path)
        <path d="{{ $path }}"/>
      @endforeach
    </g>
  </g>
  <rect x="14" y="100" width="567" height="2" fill="url(#gold)"/>

  {{-- Title ornament --}}
  <path d="M197 176.5H290" stroke="#C9A85C" stroke-width=".5"/>
  <path d="M297.5 172l4.5 4.5-4.5 4.5-4.5-4.5z" fill="url(#gold)"/>
  <path d="M305 176.5H398" stroke="#C9A85C" stroke-width=".5"/>

  {{-- Holder box dividers --}}
  <path d="M36.5 306H558.5" stroke="#E4D8BC" stroke-width=".8"/>
  <path d="M211 306V348" stroke="#E4D8BC" stroke-width=".8"/>

  {{-- Result panel: the level on a guilloche seal --}}
  <rect x="36" y="380" width="170" height="156" rx="5" fill="url(#night)"/>
  <g clip-path="url(#panel)">
    <circle cx="121" cy="432" r="90" fill="url(#glow)"/>
    <g fill="none" stroke="url(#gold)" stroke-width=".45" opacity=".55">
      @foreach ($rosette as $path)
        <path d="{{ $path }}"/>
      @endforeach
    </g>
  </g>
  <circle cx="121" cy="432" r="42" fill="url(#seal)" stroke="url(#gold)" stroke-width="1.4"/>
  <circle cx="121" cy="432" r="37.5" fill="none" stroke="#D9BE7C" stroke-width=".45" stroke-dasharray="1 2.4"/>

  {{-- Marker over the level reached on the scale --}}
  @foreach ($levels as $index => $level)
    @if ($level === $certificate->level)
      <path d="M{{ 36 + $index * 131.75 + 63.875 - 4 }} 563l4 5 4-5z" fill="#0B1F3A"/>
    @endif
  @endforeach

  {{-- Footer --}}
  <path d="M36 702H559" stroke="#C9A85C" stroke-width=".5"/>
  <rect x="36" y="712" width="64" height="64" rx="5" fill="#FFFFFF" stroke="#E4D8BC" stroke-width=".8"/>
  <g fill="none" stroke="url(#gold)" stroke-width=".45">
    @foreach ($seal as $path)
      <path d="{{ $path }}"/>
    @endforeach
  </g>
  <circle cx="527" cy="742" r="20" fill="url(#seal)" stroke="url(#gold)" stroke-width="1.1"/>
</svg>

<div class="abs brand">
  <img src="{{ $logo }}" alt="">
  <div>
    <div class="word serif">MenTalk</div>
    <div class="sub caps">Language Assessment</div>
  </div>
</div>
<div class="abs ref">
  <div class="label caps">Certificate No.</div>
  <div class="value">{{ $certificate->serial }}</div>
  <div class="label caps">Date of issue</div>
  <div class="value">{{ $certificate->issued_at->format('j F Y') }}</div>
</div>

<div class="abs center eyebrow caps">Language Proficiency</div>
<div class="abs center title serif">Certificate of Language Proficiency</div>
<div class="abs statement">
  This is to certify that the holder named below has demonstrated
  <b>{{ $certificate->level->label() }} ({{ $certificate->level->cefr() }})</b> proficiency in <b>{{ $language }}</b>,
  assessed through {{ $certificate->basis() }}.
</div>

<div class="abs sec" style="top: 244px"><span class="no">1</span><span class="name caps">Holder</span><span class="line"></span></div>
<div class="abs box" style="top: 262px; height: 86px">
  <div class="cell holder" style="left: 0; top: 0; width: 523px">
    <div class="label caps">Name of holder</div>
    <div class="value serif" style="font-size: {{ $nameSize }}px">{{ $certificate->holder_name }}</div>
  </div>
  <div class="cell" style="left: 0; top: 44px; width: 175px">
    <div class="label caps">Language assessed</div>
    <div class="value">{{ $language }}</div>
  </div>
  <div class="cell" style="left: 175px; top: 44px; width: 348px">
    <div class="label caps">Evidence assessed</div>
    <div class="value">
      {{ implode(' · ', array_filter([
          $certificate->placement_score !== null ? "Placement test {$certificate->placement_score}%" : null,
          $certificate->conversations > 0 ? $certificate->conversations.' rated '.str('conversation')->plural($certificate->conversations) : null,
          $certificate->words > 0 ? $certificate->words.' saved '.str('word')->plural($certificate->words) : null,
      ])) }}
    </div>
  </div>
</div>

<div class="abs sec" style="top: 362px"><span class="no">2</span><span class="name caps">Assessment results</span><span class="line"></span></div>
<div class="abs cefr serif {{ strlen($certificate->level->cefr()) > 2 ? 'wide' : '' }}" style="top: 400px">{{ $certificate->level->cefr() }}</div>
<div class="abs level serif" style="top: 495px">{{ $certificate->level->label() }}</div>
<div class="abs level-note" style="top: 514px">{{ $language }}</div>

@foreach ([...$skills, 'Overall' => $certificate->overall] as $label => $value)
  <div class="abs skill {{ $label === 'Overall' ? 'total' : '' }}" style="top: {{ 380 + $loop->index * 41 }}px">
    <span class="label caps">{{ $label }}</span>
    @if ($value === null)
      {{-- No activity measured this skill yet (no rated conversation): said so, never shown as a zero. --}}
      <span class="missing italic">not assessed yet</span>
    @else
      <span class="bar">
        @for ($segment = 0; $segment < 10; $segment++)
          <span class="seg"><i style="width: {{ round(max(0, min(1, $value - $segment)) * 100) }}%"></i></span>
        @endfor
      </span>
    @endif
    <span class="score"><span class="value serif">{{ $value === null ? '—' : number_format($value, 1) }}</span><span class="max">/ 10</span></span>
  </div>
@endforeach

<div class="abs sec" style="top: 548px"><span class="no">3</span><span class="name caps">CEFR scale</span><span class="line"></span></div>
@php($reached = array_search($certificate->level, $levels, true))
@foreach ($levels as $index => $level)
  <div class="abs rung {{ $index < $reached ? 'done' : ($index === $reached ? 'current' : 'ahead') }}" style="left: {{ 36 + $index * 131.75 }}px">
    <div class="code serif">{{ $level->cefr() }}</div>
    <div class="name caps">{{ $level->label() }}</div>
  </div>
@endforeach

<div class="abs sec" style="top: 620px"><span class="no">4</span><span class="name caps">What this level means</span><span class="line"></span></div>
<div class="abs descriptor italic">{{ $descriptor }}</div>

<div class="abs qr">{!! $qr !!}</div>
<div class="abs verify">
  <div class="label caps">Scan to verify</div>
  <div class="text">Confirm that it is genuine at</div>
  <div class="link">{{ $verifyLabel }}</div>
</div>
<div class="abs issuer">
  <div class="sign italic">MenTalk</div>
  <div class="role">Issued electronically by MenTalk<br>Valid as at the date of issue</div>
</div>
<img class="abs mark" src="{{ $logo }}" alt="">

<div class="abs notice">
  Scores reflect the holder's activity in the MenTalk app up to the date of issue: recent tutor-rated conversations, the placement test and saved vocabulary.
  This certificate reflects in-app assessment and is not the result of an official or proctored language examination.
</div>
</body>
</html>
