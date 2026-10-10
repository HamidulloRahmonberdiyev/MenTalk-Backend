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
      "subtitle": "Order a coffee"
    }
  ],
  "levels": ["beginner", "elementary", "intermediate", "advanced"],
  "goals": ["travel", "work", "study", "relocation", "family", "fun"],
  "features": { "translator": true, "vocabulary_sync": true, "hints": true },
  "min_app_version": "1.0.0"
}
```

**Nima qilish kerak:**
- `min_app_version` > o'rnatilgan versiya bo'lsa — "Ilovani yangilang" ekranini ko'rsating.
- `features` orqali funksiyalarni yoqing/o'chiring (masalan, `hints: false` bo'lsa maslahat tugmasini yashiring).
- `scenarios` — bosh ekrandagi suhbat mavzulari.

---

## 4. Autentifikatsiya

### `POST /auth/google` (limit: 10 so'rov/daqiqa, IP bo'yicha)

Google'dan olingan `idToken` ni yuboring.

```json
// Request
{ "id_token": "<google idToken>", "device": "Pixel 8 / Android 15" }

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

### `DELETE /auth/session` — chiqish (logout)
`204 No Content`. Keyin tokenni qurilmadan o'chiring.

### `User` obyekti

```json
{
  "id": 1,
  "name": "Ali",
  "email": "ali@gmail.com",
  "gender": "male",              // "male" | "female" | null
  "country": "UZ",               // ISO-2 | null
  "birth_date": "2000-05-20",
  "level": "beginner",           // beginner|elementary|intermediate|advanced | null
  "goals": ["travel", "work"],
  "ui_language": "uz",           // "uz" | "ru" | "en"
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

**`PUT /me` maydonlari:**

| Maydon | Qoida |
|---|---|
| `name` | string, max 120 |
| `gender` | `male` / `female` / `null` |
| `country` | 2 harfli kod / `null` |
| `birth_date` | sana, bugundan oldin / `null` |
| `level` | `beginner`/`elementary`/`intermediate`/`advanced` / `null` |
| `goals` | massiv, max 6 ta, har biri string (max 24) |
| `ui_language` | `uz` / `ru` / `en` |
| `timezone` | IANA (`Asia/Tashkent`) |
| `daily_goal_minutes` | 1–240 |

Onboarding tugagach shu endpoint bilan `level`, `goals`, `daily_goal_minutes`, `timezone` ni yuboring.

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
{ "scenario_id": "cafe" }

// Response 201
{
  "data": {
    "conversation_id": 42,
    "id": 100,
    "transcript": null,
    "reply": "Hi! What would you like to drink?",
    "emotion": "happy",
    "audio_url": "https://.../tts/abc.mp3",
    "audio_ms": 2400
  }
}
```

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

---

## 7. Tarjima va lug'at

### `POST /translate`
```json
// Request
{ "text": "Where is the station?", "language": "uz" }   // language: uz | ru | en
// Response
{ "data": { "translation": "Bekat qayerda?" } }
```

### `POST /vocabulary/lookup` — so'zni kontekstda tushuntirish
Matndagi so'zga bosilganda chaqiring. Natija 90 kun keshlanadi.
```json
// Request
{ "word": "running", "sentence": "He is running to the bus.", "language": "uz" }
// Response
{ "data": { "form": "run", "translation": "yugurmoq" } }
```
`form` — so'zning asosiy shakli (lemma).

### `POST /vocabulary/sync` — kartalarni sinxronlash (offline-first)

Kartalar telefonda lokal saqlanadi; server bilan **kursor asosida ikki tomonlama** sinxronlanadi.

```json
// Request
{
  "cursor": "2026-10-09T12:00:00+00:00",    // oxirgi sinxrondan olingan; birinchi marta null
  "changes": [
    {
      "word": "latte",
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
- Bir so'z (`word`) bitta kartadir; `changes` ichida takror bo'lsa bittasi olinadi.
- Qaytgan `cards` ni lokal DB'ga yozing, `cursor` ni saqlang.
- Ilova ochilganda, foreground'ga qaytganda va o'zgarishdan keyin (debounce bilan) sinxronlang.

### `POST /vocabulary/reviews` — takrorlash natijalari

Takrorlash sessiyasi tugagach yuboring (1–30 ta javob). XP beradi.

```json
// Request
{
  "answers": [
    { "word": "latte", "correct": true, "answered_at": "2026-10-10T08:10:00+00:00" }
  ]
}
// Response
{ "data": { "xp": 140 } }     // yangilangan umumiy XP
```

---

## 8. Xatolar va limitlar

| Status | `type` | Ma'nosi | Ilova harakati |
|---|---|---|---|
| 401 | — | Token yaroqsiz | Loginga qaytarish |
| 403 | — | Bu suhbat sizniki emas | Xato ko'rsatish |
| 409 | `turn_in_progress` | Oldingi gap hali qayta ishlanmoqda | Kuting, takror yubormang |
| 422 | — | Validatsiya xatosi (`errors` maydonida) | Forma xatolarini ko'rsatish |
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
