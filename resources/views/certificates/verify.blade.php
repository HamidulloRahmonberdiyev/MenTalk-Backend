<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Certificate {{ $certificate->serial }} · MenTalk</title>
<style>
* { box-sizing: border-box; margin: 0; }
body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; background: #0B1F3A; color: #0B1F3A; font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; }
main { width: 100%; max-width: 440px; padding: 32px 28px; border-radius: 16px; background: #FBF8F1; border: 1px solid #C9A85C; }
.status { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #E3F5EA; color: #17723F; font-size: 13px; font-weight: 600; }
h1 { margin-top: 18px; font-family: Georgia, 'Times New Roman', serif; font-size: 28px; line-height: 1.2; overflow-wrap: anywhere; }
.summary { margin-top: 8px; color: #33445E; font-size: 15px; line-height: 1.5; }
dl { margin-top: 22px; border-top: 1px solid #E4D8BC; }
dl div { display: flex; justify-content: space-between; gap: 16px; padding: 11px 0; border-bottom: 1px solid #E4D8BC; font-size: 14px; }
dt { color: #6B7A90; }
dd { margin: 0; font-weight: 600; text-align: right; }
p.notice { margin-top: 18px; color: #6B7A90; font-size: 12px; line-height: 1.5; }
</style>
</head>
<body>
<main>
  <span class="status">✓ Valid certificate</span>
  <h1>{{ $certificate->holder_name }}</h1>
  <p class="summary">{{ $certificate->level->label() }} ({{ $certificate->level->cefr() }}) · {{ strtoupper($certificate->language) }}</p>
  <dl>
    <div><dt>Certificate No.</dt><dd>{{ $certificate->serial }}</dd></div>
    <div><dt>Date of issue</dt><dd>{{ $certificate->issued_at->format('j F Y') }}</dd></div>
    <div><dt>Overall score</dt><dd>{{ number_format($certificate->overall, 1) }} / 10</dd></div>
    <div><dt>Speaking</dt><dd>{{ number_format($certificate->speaking, 1) }}</dd></div>
    <div><dt>Vocabulary</dt><dd>{{ number_format($certificate->vocabulary, 1) }}</dd></div>
    <div><dt>Grammar</dt><dd>{{ number_format($certificate->grammar, 1) }}</dd></div>
  </dl>
  <p class="notice">Issued by MenTalk from in-app assessment. This is not the result of an official or proctored language examination.</p>
</main>
</body>
</html>
