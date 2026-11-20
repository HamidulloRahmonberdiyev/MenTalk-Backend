# MenTalk — Mobil ilova (React Native) uchun API qo'llanma

MenTalk — AI bilan suhbat orqali ingliz tilini o'rganish ilovasi. Ushbu hujjat React Native ilovasi backend bilan qanday ishlashini tushuntiradi.

- **Base URL:** `https://<domen>/api/v1` (lokal: `http://localhost:8000/api/v1`)
- **Format:** JSON (`Accept: application/json`, `Content-Type: application/json`)
- **Auth:** Google Sign-In → Sanctum Bearer token (`Authorization: Bearer <token>`)
- **Muvaffaqiyatli javob:** `{ "data": ... }`
- **Xato javobi:** `application/problem+json` → `{ "type": "quota", "status": 429 }`

> Android emulyatorda lokal serverga `10.0.2.2` orqali ulaning (`localhost` ishlamaydi).

---

## 1. Tavsiya etilgan kutubxonalar

| Vazifa | Kutubxona |
|---|---|
| Google login | `@react-native-google-signin/google-signin` |
| Token saqlash | `react-native-keychain` yoki `expo-secure-store` |
| HTTP | `axios` yoki `fetch` |
| Server holati/kesh | `@tanstack/react-query` |
| Audio yozish | `expo-av` / `react-native-audio-recorder-player` |
| Audio ijro | `expo-av` / `react-native-sound` |
| Lokal DB (lug'at, offline) | `expo-sqlite` / `WatermelonDB` / `MMKV` |
| Fayl → base64 | `expo-file-system` / `react-native-fs` |

---

## 2. API klient (namuna)

```ts
// api/client.ts
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

export const api = axios.create({
  baseURL: 'https://api.mentalk.app/api/v1',
  headers: { Accept: 'application/json' },
  timeout: 30000, // AI so'rovlari sekin bo'lishi mumkin
});

api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (r) => r,
  async (error) => {
    const status = error.response?.status;
    if (status === 401) {
      await SecureStore.deleteItemAsync('token');
      // -> Login ekraniga yo'naltiring
    }
    return Promise.reject(error);
  },
);
```

---

## 3. Ilova ishga tushishi

### `GET /bootstrap` (token shart emas)

Ilova ochilganda chaqiring. Javob `ETag` bilan keshlanadi (5 daqiqa) — `If-None-Match` yuboring, `304` bo'lsa eski ma'lumotni ishlating.

```json
{
  "scenarios": [
    {
      "id": "cafe",
      "icon": "coffee",
      "tint": "#...",
      "gradient": ["#...", "#..."],
      "image_url": "https://.../storage/scenarios/cafe.jpg",
      "title": "At the café",
      "subtitle": "Order a coffee",
      "languages": ["ru", "en"]           // shu tillarda o'ynash mumkin
    }
  ],
  "languages": [                          // o'rgatiladigan tillar (admin paneldan qo'shiladi)
    { "code": "ru", "title": { "uz": "Rus tili", "ru": "Русский", "en": "Russian" }, "tutor_name": "Anna" },
    { "code": "en", "title": { "uz": "Ingliz tili", "ru": "Английский", "en": "English" }, "tutor_name": "Emma" }
  ],
  "default_language": "ru",
  "levels": ["beginner", "elementary", "intermediate", "advanced"],
  "goals": ["travel", "work", "study", "relocation", "family", "fun"],
  "experiences": ["none", "school", "self_taught", "courses", "immersion"],
  "referral_sources": ["instagram", "telegram", "tiktok", "youtube", "friend", "search", "store", "other"],
  "focus": ["speaking", "listening", "vocabulary", "grammar", "pronunciation", "confidence"],
  "features": { "translator": true, "vocabulary_sync": true, "hints": true },
  "min_app_version": "1.0.0"
}
```

**Nima qilish kerak:**
- `min_app_version` > o'rnatilgan versiya bo'lsa — "Ilovani yangilang" ekranini ko'rsating.
- `features` orqali funksiyalarni yoqing/o'chiring (masalan, `hints: false` bo'lsa maslahat tugmasini yashiring).
- `scenarios` — bosh ekrandagi suhbat mavzulari. Faqat `languages` ichida foydalanuvchi o'rganayotgan til bor stsenariylarni ko'rsating.
- `languages` — onboarding va profildagi "Qaysi tilni o'rganasiz?" tanlovi. Ro'yxat serverdan keladi: yangi til qo'shilsa, ilovani yangilash shart emas (til paketi ilovada bo'lsa).

---

## 4. Autentifikatsiya

### `POST /auth/google` (limit: 10 so'rov/daqiqa, IP bo'yicha)

Google'dan olingan `idToken` ni yuboring.

```json
// Request
{ "id_token": "<google idToken>", "device": "Pixel 8 / Android 15", "ui_language": "ru" }
// ui_language (ixtiyoriy): telefon tili. Faqat yangi profil yaratilganda qo'llanadi;
// mavjud foydalanuvchining tanlagan tili o'zgarmaydi.

// Response 200
{
  "data": {
    "token": "1|abcdef...",
    "user": { /* User obyekti, pastga qarang */ }
  }
}
```

```ts
const { idToken } = await GoogleSignin.signIn();
const { data } = await api.post('/auth/google', { id_token: idToken, device: Device.modelName });
await SecureStore.setItemAsync('token', data.data.token);
```

### Telegram orqali kirish (@mentalk_auth_bot)

Oqim: ilova urinish ochadi → Telegram'da botni ochadi → odam botda «Tasdiqlash»ni bosadi → ilova
`secret` bilan so'rab turib, sessiya tokenini **bir marta** oladi. Faqat linkni ochgan Telegram
foydalanuvchisi tasdiqlay oladi, shuning uchun begona yuborgan havola orqali hisobga kirib bo'lmaydi.

**`POST /auth/telegram`** (limit: 10/daqiqa, IP bo'yicha)

```json
// Request
{ "device": "Google Pixel 8 · Android 15", "ui_language": "uz" }   // device botdagi tasdiqlash xabarida ko'rinadi
// Response 201
{
  "data": {
    "id": "01jd…", "secret": "<64 belgi — faqat telefonda saqlanadi>",
    "bot_username": "mentalk_auth_bot",
    "app_url": "tg://resolve?domain=mentalk_auth_bot&start=<code>",  // avval shuni oching
    "bot_url": "https://t.me/mentalk_auth_bot?start=<code>",         // Telegram o'rnatilmagan bo'lsa
    "expires_at": "2026-11-16T10:05:00Z", "poll_interval_ms": 2000
  }
}
// 503 { "type": "telegram_unavailable" } — botning tokeni sozlanmagan
```

**`POST /auth/telegram/{id}`** — holatni so'rash (limit: 60/daqiqa, urinish bo'yicha)

```json
// Request
{ "secret": "<64 belgi>" }
// Response 200
{ "data": { "status": "pending" } }    // bot hali ochilmagan
{ "data": { "status": "opened" } }     // bot tasdiqlashni so'rayapti
{ "data": { "status": "approved", "token": "1|…", "user": { /* User */ } } }  // faqat bir marta
{ "data": { "status": "declined" } }   // botda bekor qilindi (yoki hisob bloklangan)
{ "data": { "status": "expired" } }    // muddati tugagan yoki token allaqachon olingan
// 404 — noto'g'ri id yoki secret · 403 { "type": "blocked" }
```

Server: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET` (.env), so'ng `php artisan telegram:setup`
(webhook + bot tavsiflari). Lokal kompyuterda: `php artisan telegram:setup --local` va `php artisan telegram:poll`.
Bot «MenTalk'ga qaytish» tugmasi `GET /telegram/return` sahifasi orqali `govori://telegram-return` ni ochadi.

### `DELETE /auth/session` — chiqish (logout)
`204 No Content`. Keyin tokenni qurilmadan o'chiring.

### `User` obyekti

```json
{
  "id": 1,
  "name": "Ali",
  "email": "ali@gmail.com",          // Telegram orqali ochilgan hisobda null
  "telegram_username": null,         // Telegram hisobida @username (bo'lsa)
  "avatar_url": "https://…",     // yuklangan rasm, bo'lmasa Google rasmi | null
  "avatar_custom": false,        // true: foydalanuvchi o'zi yuklagan (o'chirsa bo'ladi)
  "gender": "male",              // "male" | "female" | null
  "country": "UZ",               // ISO-2 | null
  "birth_date": "2000-05-20",
  "level": "beginner",           // beginner|elementary|intermediate|advanced | null
  "level_source": "placement",   // "placement" (test natijasi) | "self" (o'zi tanlagan) | null
  "placement_score": 75,         // test natijasi, % (0–100) | null
  "experience": "courses",       // bootstrap'dagi experiences[] | null
  "referral_source": "telegram", // bootstrap'dagi referral_sources[] | null
  "goals": ["travel", "work"],
  "focus": ["speaking"],         // bootstrap'dagi focus[] — qiynalayotgan ko'nikmalar
  "ui_language": "uz",           // "uz" | "ru" | "en" — interfeys tili
  "target_language": "ru",       // o'rganilayotgan til: bootstrap'dagi languages[].code
  "timezone": "Asia/Tashkent",
  "daily_goal_minutes": 15,
  "xp": 120,
  "streak": 3
}
```

---

## 5. Profil

| Metod | Yo'l | Tavsif |
|---|---|---|
| GET | `/me` | Joriy foydalanuvchi |
| PUT | `/me` | Profilni yangilash (barcha maydonlar ixtiyoriy) |
| DELETE | `/me` | Akkauntni o'chirish (`204`) — App Store/Play talabi uchun sozlamalarda tugma bo'lsin |
| POST | `/me/avatar` | Profil rasmini yuklash (`multipart/form-data`, maydon `photo`). Eski rasm o'chiriladi. Javob: `User` |
| DELETE | `/me/avatar` | Yuklangan rasmni o'chirish; `avatar_url` Google rasmiga qaytadi. Javob: `User` |

**`POST /me/avatar`:** `photo` — jpg/png/webp, max 5 MB, 64–4096 px. Limit: 10 so'rov/daqiqa. Ilova rasmni yuborishdan oldin 512×512 JPEG'ga kichraytiradi.

Google orqali kirganda `avatar_url` Google rasmiga (512 px) o'rnatiladi; foydalanuvchi o'z rasmini yuklasa, u ustun turadi. Ism faqat birinchi kirishda Google'dan olinadi, keyin foydalanuvchi o'zgartirgani saqlanadi.

**`PUT /me` maydonlari:**

| Maydon | Qoida |
|---|---|
| `name` | string, 2–120 |
| `gender` | `male` / `female` / `null` |
| `country` | 2 harfli kod / `null` |
| `birth_date` | sana, bugundan oldin / `null` |
| `level` | `beginner`/`elementary`/`intermediate`/`advanced` / `null` |
| `level_source` | `placement` / `self` / `null` |
| `placement_score` | 0–100 (% to'g'ri javob). `level_source: placement` bo'lsa majburiy, boshqa holatda yuborilmaydi |
| `experience` | `none`/`school`/`self_taught`/`courses`/`immersion` / `null` |
| `referral_source` | `instagram`/`telegram`/`tiktok`/`youtube`/`friend`/`search`/`store`/`other` / `null` |
| `goals` | massiv, max 6 ta, har biri string (max 24) |
| `focus` | massiv, takrorlanmas: `speaking`/`listening`/`vocabulary`/`grammar`/`pronunciation`/`confidence` |
| `ui_language` | `uz` / `ru` / `en` |
| `target_language` | faol o'rgatiladigan til kodi (`ru`, `en`, …) |
| `timezone` | IANA (`Asia/Tashkent`) |
| `daily_goal_minutes` | 1–240 |

Onboarding tugagach shu endpoint bilan `target_language`, `level`, `level_source` (+ `placement_score`), `experience`, `referral_source`, `goals`, `focus`, `daily_goal_minutes`, `timezone` ni yuboring.

Daraja testi ilova ichida o'tadi (savollar ilovada, internet kerak emas); server natijani saqlaydi va vaqtini (`placed_at`) belgilaydi. `level_source`siz boshqa `level` yuborilsa, u qo'lda tanlangan hisoblanadi: `level_source` `self` bo'ladi, test natijasi tozalanadi. `experience`, `focus` va test natijasi AI tutor promptiga (`{learner}`) qo'shiladi.

---

## 6. Suhbat (asosiy funksiya)

Suhbat oqimi:

```
POST /conversations              → suhbat boshlanadi, AI birinchi gapni aytadi
POST /conversations/{id}/turns   → foydalanuvchi matn/ovoz yuboradi, AI javob beradi (takrorlanadi)
POST /conversations/{id}/hint    → (ixtiyoriy) nima deyish kerakligi haqida maslahat
POST /conversations/{id}/finish  → yakunlash va natija (ball, xatolar, yangi so'zlar)
```

Hamma `/conversations*`, `/translate`, `/vocabulary/lookup` endpointlariga **limit: 20 so'rov/daqiqa** (foydalanuvchi bo'yicha) qo'llanadi.

### `POST /conversations` — boshlash

```json
// Request
{ "scenario_id": "cafe", "target_language": "en" }   // target_language ixtiyoriy

// Response 201
{
  "data": {
    "conversation_id": 42,
    "target_language": "en",
    "id": 100,
    "transcript": null,
    "reply": "Hi! What would you like to drink?",
    "emotion": "happy",
    "audio_url": "https://.../tts/abc.mp3",
    "audio_ms": 2400
  }
}
```

- `"defer_opening": true` yuborilsa, suhbat darhol (AI'siz) yaratiladi va javobda faqat `conversation_id`, `target_language` keladi. Tutorning birinchi gapini ilova `turns/stream` ga `{"opening": true}` yuborib oqimda oladi (pastda). Bu birinchi gap limitdan alohida hisoblanmaydi.
- `"voice": 4` — o'quvchi tanlagan tutor ovozi (`GET /tutor-voices` dagi `id`). Ovoz suhbatga yoziladi: suhbat oxirigacha (qayta urinishlar ham) shu ovozda. Erkak ovozda tutor tilning erkak ismi bilan va erkak shakllarida gapiradi. Yuborilmasa, o'chirilgan yoki yo'q ovoz bo'lsa — xatosiz standart ovoz.
- `target_language` yuborilmasa, profildagi `target_language` olinadi (eski ilova versiyalari uchun).
- Til suhbatga yoziladi: foydalanuvchi keyin tilni almashtirsa ham, boshlangan suhbat o'z tilida davom etadi. Maslahat va baholash ham shu tilda.
- Stsenariy shu tilda bo'lmasa — `404 scenario_unavailable`; til o'chirilgan bo'lsa — `422 unsupported_language`.

### `POST /conversations/{id}/turns` — gap yuborish

Matn **yoki** audio yuboriladi (ikkalasidan biri majburiy).

```json
// Matn
{ "text": "I would like a coffee, please." }

// Audio (base64)
{ "audio": "<base64>", "mime": "audio/mp4" }
```

- `text`: max 500 belgi
- `audio`: base64 satr, max ~4 MB (≈3 MB fayl). Qisqa yozing (~30 soniyagacha).
- `mime`: `audio/mp4` | `audio/m4a` | `audio/aac` | `audio/mpeg`

```json
// Response 200
{
  "data": {
    "id": 101,
    "transcript": "I would like a coffee, please.", // audio bo'lsa — AI tanigan matn
    "reply": "Sure! Hot or iced?",
    "emotion": "curious",
    "audio_url": "https://.../tts/def.mp3",
    "audio_ms": 1900
  }
}
```

`emotion` qiymatlari: `neutral`, `happy`, `encouraging`, `curious`, `empathetic`, `surprised`, `playful` — avatar/animatsiya uchun ishlating.

```ts
import * as FileSystem from 'expo-file-system';

const audio = await FileSystem.readAsStringAsync(uri, { encoding: FileSystem.EncodingType.Base64 });
const { data } = await api.post(`/conversations/${id}/turns`, { audio, mime: 'audio/mp4' });
// data.data.audio_url ni ijro eting
```

> Yozishni `m4a`/AAC formatda sozlang (iOS: `kAudioFormatMPEG4AAC`, Android: `AAC_ADTS`/`MPEG_4`).

### `POST /conversations/{id}/turns/stream` — gap yuborish, javob oqimda

`turns` bilan bir xil so'rov, javob `text/event-stream` bo'lib keladi. Qo'shimcha maydonlar:

- `clips: true` — ovoz 2 bo'lakkacha keladi (birinchi gap tayyor bo'lishi bilan).
- `opening: true` — `defer_opening` bilan boshlangan suhbatning birinchi gapi (`text`/`audio` kerak emas). Birinchi gap allaqachon bo'lsa, o'sha qayta yuboriladi.
- `client_id` — ilova bergan gap ID'si (`[A-Za-z0-9_-]`, 40 belgigacha). Aloqa uzilib, gap **o'sha `client_id` bilan** qayta yuborilsa, server AI'ga qayta murojaat qilmaydi: saqlangan javobni yana yuboradi va limitni ikkinchi marta hisoblamaydi. Shuning uchun aloqa uzilganda qayta yuborish xavfsiz.

Hodisalar tartibi:

| Hodisa | Ma'lumot | Izoh |
|---|---|---|
| `transcript` | `{text}` | Ovozli gapda: AI tanigan matn |
| `delta` | `{text}` | Javob so'zma-so'z (qayta yuborilganda kelmaydi) |
| `reply` | `{id: null, reply, emotion}` | Javob matni tayyor |
| `audio` | `{url, ms, text, last}` | Ovoz bo'lagi; `url: null` — bu bo'lak ovozlanmadi |
| `saved` | `{id}` | Gap saqlandi; `id` — ovozni qayta so'rash uchun |
| `done` | `{id}` | Hammasi tugadi |
| `error` | `{status}` | `503` — AI band (qayta urinish mumkin), `502` — javob olinmadi |

Boshqa gap hali tugamagan bo'lsa `409 turn_in_progress` (bir necha soniyadan keyin qayta urinish mumkin), limit tugagan bo'lsa `429 quota` qaytadi.

### `POST /conversations/{id}/turns/{turn}/speech` — ovozni qayta olish

Tutor gapining ovozi kelmay qolganda (bo'lak `url: null` bo'lgan yoki oqim uzilgan) ishlatiladi.

```json
// Request (ixtiyoriy): faqat gapning bir qismi, masalan eshitilgan bo'lakdan keyingi qoldiq
{ "text": "Hot or iced?" }

// Response 200
{ "data": { "url": "https://.../speech/abc.wav", "ms": 1900 } }
```

- `text` gapning ichidan olingan bo'lishi shart, aks holda `422 not_in_line`.
- Ovoz yaratilmasa `503 speech_unavailable` (`Retry-After` bilan).

### `GET /tutor-voices?language=ru` — tutor ovozlari

Sozlamalar → Tutor → Ovoz uchun. Ro'yxatni admin panel ("Tutor ovozlari") boshqaradi, tartib shu yerdagidek.

```json
// Response 200
{
  "data": [
    { "id": 1, "name": { "uz": "Iliq", "ru": "Тёплый", "en": "Warm" }, "gender": "female", "default": true },
    { "id": 4, "name": { "uz": "Do‘stona", "ru": "Дружелюбный", "en": "Friendly" }, "gender": "male", "default": false }
  ]
}
```

- `language` ixtiyoriy: yuborilmasa, profildagi o'rganilayotgan til. `default` — shu tilda ovoz tanlanmaganda ishlatiladigani (ko'pi bilan bittasi).
- Ilova saqlangan `id` ro'yxatda bo'lmasa (admin o'chirgan), uni "tanlanmagan" deb hisoblashi kerak.

### `POST /tutor-voices/{id}/sample` — ovozni eshitib ko'rish

```json
// Request (ixtiyoriy)
{ "language": "en" }

// Response 200
{ "data": { "url": "https://.../speech/abc.wav", "ms": 2600 } }
```

- Tutor o'sha tilda shu ovoz bilan o'zini tanishtiradi ("Hi! I'm James..."). Matn o'zgarmaydi, shuning uchun qayta eshitish keshdan keladi.
- O'chirilgan ovoz — `404`; ovoz yaratilmasa `503 speech_unavailable` (`Retry-After` bilan).

### `POST /conversations/{id}/hint`

```json
{ "data": { "hint": "Try saying: I'd like a latte, please." } }
```

### `POST /conversations/{id}/finish`

```json
{
  "data": {
    "conversation_id": 42,
    "speech": 78,
    "vocabulary": 70,
    "grammar": 65,
    "overall": 71,
    "mistakes": [
      { "said": "I want coffee", "correct": "I'd like a coffee", "note": "Use polite form" }
    ],
    "new_words": [
      { "word": "latte", "translation": "latte" }
    ]
  }
}
```

`new_words` ni "Lug'at"ga qo'shishni taklif qiling (7-bo'limga qarang).

### `GET /history` — suhbatlar tarixi

Kursor bo'yicha sahifalash (20 tadan). Javob Laravel `cursorPaginate` formatida: `data`, `links.next`, `meta.next_cursor`.

```json
{
  "data": [
    {
      "id": 42,
      "scenario_id": "cafe",
      "target_language": "ru",
      "state": "finished",          // "active" | "finished"
      "score": 71,
      "learner_turns": 6,
      "duration_sec": 240,
      "started_at": "2026-10-10T08:00:00+00:00",
      "ended_at": "2026-10-10T08:04:00+00:00"
    }
  ],
  "links": { "next": "https://.../history?cursor=..." },
  "meta": { "next_cursor": "..." }
}
```

Keyingi sahifa: `GET /history?cursor=<next_cursor>` (`useInfiniteQuery` bilan qulay).

### `GET /stats` — bosh ekran statistikasi

```json
{
  "data": {
    "daily_goal_minutes": 15,
    "today_minutes": 6,
    "today_xp": 40,
    "streak": 3,
    "xp": 120
  }
}
```

**XP qayerdan keladi:** lug'at va xatolar mashqi (to'g'ri javob + kombo + bonus) hamda tugatilgan suhbat. Suhbat uchun XP o'quvchi kamida 3 ta gap aytgan bo'lsa beriladi: `10 + overall × 2`, ya'ni 10–30 XP. `finish` ikki marta chaqirilsa ham XP faqat bir marta yoziladi.

### `GET /leaderboard?period=week&limit=20` — peshqadamlar jadvali

| Parametr | Qiymat | Default |
|---|---|---|
| `period` | `week` (dushanbadan beri yig'ilgan XP, o'quvchining vaqt zonasida) · `all` (umumiy XP) | `week` |
| `limit` | 1–50 | 20 |

```json
{
  "data": {
    "period": "week",
    "since": "2026-11-09",          // all uchun null
    "top": [
      { "rank": 1, "name": "Aziza K.", "avatar_url": "https://…", "xp": 3918, "is_me": false },
      { "rank": 2, "name": "Hamidullo R.", "avatar_url": null, "xp": 3634, "is_me": true }
    ],
    "me": { "rank": 2, "xp": 3634 } // XP 0 bo'lsa rank: null
  }
}
```

- Teng XP'da avval ro'yxatdan o'tgan o'quvchi yuqorida turadi, shuning uchun o'rin barqaror.
- Boshqa o'quvchilarning familiyasi ko'rsatilmaydi: faqat ism va familiyaning bosh harfi.
- Bloklangan, o'chirilgan va hali XP'si yo'q o'quvchilar ro'yxatga kirmaydi.
- `me` har doim qaytadi, o'quvchi `top` ichida bo'lmasa ham.

---

## 7. Tarjima va lug'at

### `POST /translate`
```json
// Request
{ "text": "Where is the station?", "language": "uz" }   // language: uz | ru | en yoki o'rgatiladigan til kodi
// Response
{ "data": { "translation": "Bekat qayerda?" } }
```

### `POST /vocabulary/lookup` — so'zni kontekstda tushuntirish
Matndagi so'zga bosilganda chaqiring. Natija 90 kun keshlanadi.
```json
// Request
{ "word": "running", "sentence": "He is running to the bus.", "language": "uz", "target_language": "en" }
// Response
{ "data": { "form": "run", "translation": "yugurmoq" } }
```
`form` — so'zning asosiy shakli (lemma). `language` — ma'no qaysi tilda tushuntirilsin (interfeys tili), `target_language` — so'z qaysi tilniki (yuborilmasa `default_language`). Ikkalasi bir xil bo'lsa (masalan, ingliz interfeysida ingliz so'zi), tarjima o'rniga shu tildagi qisqa izoh qaytadi.

### `POST /vocabulary/sync` — kartalarni sinxronlash (offline-first)

Kartalar telefonda lokal saqlanadi; server bilan **kursor asosida ikki tomonlama** sinxronlanadi.

```json
// Request
{
  "cursor": "2026-10-09T12:00:00+00:00",    // oxirgi sinxrondan olingan; birinchi marta null
  "changes": [
    {
      "word": "latte",
      "language": "en",                      // so'z qaysi tilniki; yuborilmasa default_language
      "translation": "latte",
      "example": "I'd like a latte.",
      "form": "latte",
      "stage": 0,                            // 0–6 (takrorlash bosqichi)
      "streak": 0,
      "lapses": 0,
      "due_at": "2026-10-11T08:00:00+00:00",
      "updated_at": "2026-10-10T08:05:00+00:00",
      "deleted": false
    }
  ]
}

// Response
{
  "data": {
    "cards": [ /* serverdagi o'zgargan kartalar, xuddi shu shaklda */ ],
    "cursor": "2026-10-10T08:06:00+00:00"   // keyingi so'rovda yuboring
  }
}
```

Qoidalar:
- `changes` — faqat oxirgi sinxrondan keyin **lokal o'zgargan** kartalar (max 500). Bo'lmasa `[]` yuboring (maydon majburiy).
- O'chirish: `deleted: true` (kartani butunlay olib tashlamang, avval sinxronlang).
- Karta `language` + `word` juftligi bilan aniqlanadi: bir xil yozilgan so'z ikki tilda — ikki karta. `changes` ichida takror bo'lsa bittasi olinadi.
- Barcha tillardagi kartalar sinxronlanadi (o'chirilgan tilniki ham); ilova joriy tilnikini ko'rsatadi.
- Qaytgan `cards` ni lokal DB'ga yozing, `cursor` ni saqlang.
- Ilova ochilganda, foreground'ga qaytganda va o'zgarishdan keyin (debounce bilan) sinxronlang.

### `POST /vocabulary/reviews` — takrorlash natijalari

Takrorlash sessiyasi tugagach yuboring (1–30 ta javob). XP beradi.

```json
// Request
{
  "answers": [
    { "word": "latte", "language": "en", "correct": true, "answered_at": "2026-10-10T08:10:00+00:00" }
  ]
}
// Response
{ "data": { "xp": 140 } }     // yangilangan umumiy XP
```

---

## 7.1. Bildirishnomalar (in-app inbox)

Server matnni **o'qilayotgan paytda** foydalanuvchi tilida yig'adi: kalit + parametrlar (tizim hodisalari) yoki admin yozgan uz/ru/en matn (e'lonlar) saqlanadi. Shuning uchun ilova tilini almashtirgach ro'yxatni qayta so'rash kifoya.

| `type` | `kind` | Qachon |
|---|---|---|
| `welcome` | `lesson` | Yangi akkaunt yaratilganda (bir marta) |
| `streak_milestone` | `achievement` | Seriya 3, 7, 14, 30, 50, 100… kunga yetganda |
| `streak_at_risk` | `streak` | Kecha mashq qilgan, bugun hali yo'q — mahalliy soat 19:00 da |
| `words_due` | `review` | ≥5 so'z takrorlash vaqti kelgan — mahalliy soat 19:00 da |
| `announcement` | admin tanlaydi | Admin panel → **Kontent → E'lonlar** dan yuborilganda |

Eslatma kuniga ko'pi bilan bitta; bugun mashq qilganlar va 14 kundan beri kirmaganlarga yuborilmaydi. 90 kundan eski yozuvlar avtomatik o'chiriladi (`model:prune`).

### `GET /notifications?limit=30&lang=uz&cursor=…`

`limit` 1–50 (standart 30). `lang` ixtiyoriy: berilmasa profildagi `ui_language`.

```json
{
  "data": [
    {
      "id": "9b2f…-uuid",
      "type": "streak_at_risk",
      "kind": "streak",                 // streak | review | achievement | lesson | pronunciation | news
      "title": "5 kunlik seriyangizni saqlang!",
      "body": "Bugun hali mashq qilmadingiz…",
      "action": { "target": "scenarios", "label": "Suhbatni boshlash" },   // yoki null
      "read": false,
      "created_at": "2026-11-03T14:00:00+00:00"
    }
  ],
  "links": { "next": null },
  "meta": { "next_cursor": null, "per_page": 30, "unread_count": 3 }
}
```

`action.target` — ekran nomi, yo'l emas: `scenarios | words | practice | pronunciation | translate`. Ilova bilmagan nomni e'tiborsiz qoldiradi.

### `GET /notifications/unread-count`

```json
{ "data": { "unread_count": 3 } }
```

### `POST /notifications/read` · `POST /notifications/delete`

```json
{ "ids": ["uuid", "uuid"] }                                   // 100 tagacha
{ "all": true, "before": "2026-11-03T14:00:00+00:00" }        // hammasi (before — ixtiyoriy)
// Response
{ "data": { "unread_count": 0 } }
```

Ikkalasi idempotent: o'qilgan, o'chirilgan yoki birovning id'si jim o'tkazib yuboriladi — offline navbatdan qayta yuborish xavfsiz. `before` — ro'yxat ko'rilgandan keyin kelgan yangi bildirishnoma "hammasini o'qish/o'chirish" bilan birga ketib qolmasligi uchun.

### `POST /me/push-token`

Qurilmani push olish uchun ro'yxatdan o'tkazadi. Ilova uni har kirishda va token yangilanganda chaqiradi; takror yuborish xavfsiz.

```json
{ "token": "ExponentPushToken[xxxx]", "platform": "android" }   // platform: android | ios
// Response: 204
```

Token shu sessiyaga bog'lanadi: `DELETE /auth/session` yoki hisob o'chirilsa, u ham o'chadi. Bitta qurilmaga boshqa odam kirsa, token unga o'tadi.

Push `welcome`, `streak_milestone` va `announcement` uchun yuboriladi. `streak_at_risk` va `words_due` faqat inboxga tushadi, chunki ilova bu eslatmalarni qurilmada o'zi rejalashtiradi.

Push `data`si:

```json
{ "source": "mentalk-push", "id": "uuid", "kind": "news", "action": "words" }   // action null bo'lishi mumkin
```

Android kanali: `inbox`. Ilova uni o'zi yaratadi.

> Server: cron'da `php artisan schedule:run` har daqiqa ishlashi va queue worker (`php artisan queue:work`) yoqilgan bo'lishi shart.

---

## 8. Xatolar va limitlar

| Status | `type` | Ma'nosi | Ilova harakati |
|---|---|---|---|
| 401 | — | Token yaroqsiz | Loginga qaytarish |
| 403 | — | Bu suhbat sizniki emas | Xato ko'rsatish |
| 404 | `scenario_unavailable` | Stsenariy yo'q, o'chirilgan yoki bu tilda hali yo'q | Bootstrap'ni yangilab, ro'yxatdan olib tashlash |
| 409 | `turn_in_progress` | Oldingi gap hali qayta ishlanmoqda | Kuting, takror yubormang |
| 422 | — | Validatsiya xatosi (`errors` maydonida) | Forma xatolarini ko'rsatish |
| 422 | `unsupported_language` | Profildagi til o'chirilgan | Til tanlash ekranini ochish |
| 429 | `quota` | Kunlik kvota tugadi | "Ertaga davom eting" ekrani |
| 429 | — | Tezlik limiti (`throttle`) | `Retry-After` dan keyin qayta urinish |
| 502 | `turn_failed` | AI javobni rad etdi | Qayta yuborishni taklif qilish |
| 503 | `unavailable` | AI vaqtincha ishlamayapti (`Retry-After: 5`) | 5 soniyadan keyin avtomatik qayta urinish |

**Kunlik kvotalar** (foydalanuvchi bo'yicha, kun yarim tunda yangilanadi):
- `turns` — **300** (suhbat boshlash + gaplar)
- `lookups` — **400** (tarjima + so'z qidirish)

```ts
function errorType(e: any): string | undefined {
  return e.response?.data?.type; // 'quota' | 'turn_in_progress' | 'unavailable' | 'turn_failed'
}
```

---

## 9. Tavsiya etilgan ekranlar va oqim

1. **Splash** → `GET /bootstrap` → versiyani tekshirish → token bormi?
2. **Login** → Google Sign-In → `POST /auth/google`
3. **Onboarding** (yangi foydalanuvchi: `level == null`) → `PUT /me`
4. **Home** → `GET /stats` + bootstrap `scenarios`
5. **Suhbat** → `POST /conversations` → audio ijro → mikrofon yozish → `POST .../turns` (takror) → `POST .../finish`
6. **Natija** → ball, xatolar, `new_words` → lug'atga qo'shish
7. **Lug'at** → lokal kartalar + `POST /vocabulary/sync`, takrorlash → `POST /vocabulary/reviews`
8. **Tarix** → `GET /history`
9. **Profil/Sozlamalar** → `GET/PUT /me`, logout, akkauntni o'chirish

---

## 10. Eslatmalar

- **Vaqt:** barcha sanalar ISO 8601. Kunlik maqsad va streak foydalanuvchining `timezone` bo'yicha hisoblanadi — profilda to'g'ri timezone o'rnating.
- **Audio kechikishi:** `turns` 3–10 soniya olishi mumkin — "o'ylayapti..." holatini ko'rsating, tugmani bloklang (409 oldini olish uchun).
- **Audio keshlash:** `audio_url` ni oldindan yuklab oling, qayta o'ynatish uchun keshda saqlang.
- **Xavfsizlik:** tokenni `AsyncStorage`da emas, Keychain/SecureStore'da saqlang.
- **Ruxsatlar:** Android `RECORD_AUDIO`, iOS `NSMicrophoneUsageDescription`.
- **Offline:** lug'at offline ishlaydi (sinxron keyin); suhbat va tarjima internet talab qiladi.

## Sertifikat

Sertifikat o'quvchining haqiqiy natijalaridan yaratiladi. Shartlar (`config/certificate.php`): daraja testi topshirilgan (`level_source: placement`) va o'rganilayotgan tilda kamida 5 ta baholangan suhbat (har biri kamida 3 ta javob bilan). Ballar oxirgi 10 ta suhbatning o'rtachasi. Har bir til va daraja uchun bitta sertifikat beriladi; ism va ballar berilgan paytdagidek saqlanadi.

| Metod | Yo'l | Tavsif |
| --- | --- | --- |
| `GET` | `/certificate` | Holat: `level`, `cefr`, `placed`, `conversations`, `required_conversations`, `scores`, `eligible`, `certificate` (oxirgi berilgani yoki `null`) |
| `POST` | `/certificate` | Joriy daraja uchun sertifikat beradi (`201`), avval berilgan bo'lsa o'shani qaytaradi (`200`). Shartlar bajarilmagan bo'lsa `422 certificate_not_earned` |
| `GET` | `/certificate/{serial}/document` | `{ "html": "..." }`. Faqat egasiga. Ilova shu HTML'dan telefonda PDF yaratadi |

Ochiq tekshiruv sahifasi (API emas, avtorizatsiyasiz): `GET /verify/{serial}`. QR kod shu manzilga olib boradi. Akkaunt o'chirilsa sahifa `404` qaytaradi.
