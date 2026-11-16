<!doctype html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#2AABEE">
<title>{{ __('telegram.page_title', [], $locale) }}</title>
<style>
* { box-sizing: border-box; margin: 0; }
body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; background: #F3F6FA; color: #101828; font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; }
main { width: 100%; max-width: 400px; padding: 32px 24px 28px; text-align: center; border-radius: 28px; background: #FFFFFF; border: 2px solid #E4E9F0; border-bottom-width: 6px; border-bottom-color: #D3DBE5; }
.badge { width: 84px; height: 84px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #E3F8EE; font-size: 42px; }
h1 { margin-top: 20px; font-size: 26px; line-height: 1.2; font-weight: 800; letter-spacing: -0.4px; }
p { margin-top: 10px; color: #475467; font-size: 16px; line-height: 1.5; }
a.button { display: block; margin-top: 24px; padding: 17px 20px; border-radius: 16px; background: #2AABEE; box-shadow: 0 4px 0 #1A84BF; color: #FFFFFF; font-size: 17px; font-weight: 700; text-decoration: none; }
a.button:active { transform: translateY(4px); box-shadow: none; }
p.hint { margin-top: 18px; color: #667085; font-size: 13px; }
@media (prefers-color-scheme: dark) {
  body { background: #1B1C21; color: #FFFFFF; }
  main { background: #2A2D35; border-color: #3B3F4A; }
  .badge { background: #1C3527; }
  p { color: #B9C0CC; }
  p.hint { color: #9AA3B2; }
}
</style>
</head>
<body>
<main>
  <div class="badge" aria-hidden="true">✅</div>
  <h1>{{ __('telegram.page_heading', [], $locale) }}</h1>
  <p>{{ __('telegram.page_text', [], $locale) }}</p>
  <a class="button" href="{{ $appUrl }}">{{ __('telegram.page_button', [], $locale) }}</a>
  <p class="hint">{{ __('telegram.page_hint', [], $locale) }}</p>
</main>
<script>
  setTimeout(function () { window.location.href = @json($appUrl); }, 150);
</script>
</body>
</html>
