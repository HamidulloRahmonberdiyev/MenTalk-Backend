<?php

declare(strict_types=1);

// The sign-in bot (@mentalk_auth_bot) and the "back to the app" page. Bot texts are Telegram HTML.
return [
    'command_start' => 'Botni ishga tushirish',
    'short_description' => 'MenTalk ilovasiga Telegram orqali tez va xavfsiz kirish.',
    'description' => "MenTalk — AI bilan rus va ingliz tilida erkin gapirishni o‘rganing.\n\nBu bot MenTalk ilovasiga parolsiz, xavfsiz kirish uchun. Ilovada «Telegram orqali kirish» tugmasini bosing — bu yerda bir bosishda tasdiqlaysiz.",

    'welcome' => "👋 <b>Assalomu alaykum, :name!</b>\n\nBu — <b>MenTalk</b> ilovasining rasmiy kirish boti.\n\nKirish uchun MenTalk ilovasini oching va <b>«Telegram orqali kirish»</b> tugmasini bosing. So‘ng shu yerda bir bosishda tasdiqlaysiz.",
    'help' => 'Kirish uchun MenTalk ilovasidagi <b>«Telegram orqali kirish»</b> tugmasini bosing.',

    'prompt' => "🔐 <b>MenTalk’ga kirish</b>\n\nTelegram hisobingiz orqali ilovaga kirish so‘raldi.\n\n📱 Qurilma: <b>:device</b>\n⏱ So‘rov :minutes daqiqa amal qiladi\n\nBu siz bo‘lsangiz, <b>«Tasdiqlash»</b> tugmasini bosing.\n\n⚠️ <i>So‘rovni siz yubormagan bo‘lsangiz, hech narsani bosmang. MenTalk hech qachon sizdan begona havola orqali tasdiqlashni so‘ramaydi.</i>",
    'approve' => '✅ Tasdiqlash',
    'decline' => '✖️ Bekor qilish',

    'approved' => "✅ <b>Kirish tasdiqlandi</b>\n\nMenTalk ilovasiga qayting — hisobingiz ochildi. Yoqimli mashg‘ulotlar! 🎉",
    'approved_toast' => 'Tayyor! Ilovaga qayting.',
    'declined' => "✖️ <b>Kirish bekor qilindi</b>\n\nHisobingizga hech kim kirmadi. Agar bu siz bo‘lsangiz, ilovada qaytadan urinib ko‘ring.",
    'declined_toast' => 'Bekor qilindi',
    'expired' => "⌛ <b>So‘rov muddati tugagan</b>\n\nMenTalk ilovasiga qayting va <b>«Telegram orqali kirish»</b> tugmasini qaytadan bosing.",
    'expired_toast' => 'So‘rov muddati tugagan. Ilovada qaytadan urinib ko‘ring.',
    'taken' => "🔒 <b>Bu so‘rov boshqa Telegram hisobida ochilgan</b>\n\nIlovada «Telegram orqali kirish» tugmasini qaytadan bosing.",
    'blocked' => "⛔ <b>Hisobingiz bloklangan</b>\n\nSavollar bo‘lsa, yordam xizmatiga yozing: :support",
    'blocked_toast' => 'Hisob bloklangan',
    'return_button' => '📲 MenTalk’ga qaytish',

    'page_title' => 'MenTalk’ga qaytish',
    'page_heading' => 'Kirish tasdiqlandi',
    'page_text' => 'MenTalk ilovasi ochilmoqda. Ochilmasa, quyidagi tugmani bosing.',
    'page_button' => 'MenTalk’ni ochish',
    'page_hint' => 'Ilovani o‘zingiz ochsangiz ham bo‘ladi — hisobingiz allaqachon ochilgan.',
];
