<?php

declare(strict_types=1);

// The sign-in bot (@mentalk_auth_bot) and the "back to the app" page. Bot texts are Telegram HTML.
return [
    'command_start' => 'Botni ishga tushirish',
    'short_description' => 'MenTalk ilovasiga Telegram orqali tez va xavfsiz kirish.',
    'description' => "MenTalk — AI bilan rus va ingliz tilida erkin gapirishni o‘rganing.\n\nBu bot MenTalk ilovasiga parolsiz, xavfsiz kirish uchun. Ilovada «Telegram orqali kirish» tugmasini bosing — bu yerda telefon raqamingizni yuborib, kirishni tasdiqlaysiz.",

    'welcome' => "👋 <b>Assalomu alaykum, :name!</b>\n\nBu — <b>MenTalk</b> ilovasining rasmiy kirish boti.\n\nKirish uchun MenTalk ilovasini oching va <b>«Telegram orqali kirish»</b> tugmasini bosing. So‘ng shu yerda telefon raqamingizni yuborib, kirishni tasdiqlaysiz.",
    'help' => 'Kirish uchun MenTalk ilovasidagi <b>«Telegram orqali kirish»</b> tugmasini bosing.',

    // :button is the label of the "share phone number" button (share_phone).
    'prompt' => "🔐 <b>MenTalk’ga kirish</b>\n\nTelegram hisobingiz orqali ilovaga kirish so‘raldi.\n\n📱 Qurilma: <b>:device</b>\n⏱ So‘rov :minutes daqiqa amal qiladi\n\nBu siz bo‘lsangiz, pastdagi <b>«:button»</b> tugmasini bosing. Raqamingiz MenTalk hisobingizga biriktiriladi.\n\n⚠️ <i>So‘rovni siz yubormagan bo‘lsangiz, hech narsani bosmang. MenTalk hech qachon sizdan begona havola orqali kirishni so‘ramaydi.</i>",
    'share_phone' => '📱 Raqamni yuborish',
    'decline' => '✖️ Bekor qilish',
    'phone_placeholder' => 'Pastdagi tugmani bosing',
    'phone_hint' => '👇 Raqamni qo‘lda yozish shart emas — pastdagi <b>«:button»</b> tugmasini bosing, Telegram uni o‘zi yuboradi.',
    'foreign_phone' => "⚠️ <b>Faqat o‘z raqamingizni yuboring</b>\n\nPastdagi <b>«:button»</b> tugmasini bosing — Telegram raqamingizni o‘zi yuboradi.",
    'phone_saved' => '📱 Raqamingiz qabul qilindi.',

    'approved' => "✅ <b>Kirish tasdiqlandi</b>\n\nMenTalk ilovasiga qayting — hisobingiz ochildi. Yoqimli mashg‘ulotlar! 🎉",
    'declined' => "✖️ <b>Kirish bekor qilindi</b>\n\nHisobingizga hech kim kirmadi. Agar bu siz bo‘lsangiz, ilovada qaytadan urinib ko‘ring.",
    'expired' => "⌛ <b>So‘rov muddati tugagan</b>\n\nMenTalk ilovasiga qayting va <b>«Telegram orqali kirish»</b> tugmasini qaytadan bosing.",
    'taken' => "🔒 <b>Bu so‘rov boshqa Telegram hisobida ochilgan</b>\n\nIlovada «Telegram orqali kirish» tugmasini qaytadan bosing.",
    'blocked' => "⛔ <b>Hisobingiz bloklangan</b>\n\nSavollar bo‘lsa, yordam xizmatiga yozing: :support",
    'return_button' => '📲 MenTalk’ga qaytish',

    'page_title' => 'MenTalk’ga qaytish',
    'page_heading' => 'Kirish tasdiqlandi',
    'page_text' => 'MenTalk ilovasi ochilmoqda. Ochilmasa, quyidagi tugmani bosing.',
    'page_button' => 'MenTalk’ni ochish',
    'page_hint' => 'Ilovani o‘zingiz ochsangiz ham bo‘ladi — hisobingiz allaqachon ochilgan.',
];
