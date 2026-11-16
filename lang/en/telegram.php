<?php

declare(strict_types=1);

// The sign-in bot (@mentalk_auth_bot) and the "back to the app" page. Bot texts are Telegram HTML.
return [
    'command_start' => 'Start the bot',
    'short_description' => 'Fast, secure sign-in to the MenTalk app with Telegram.',
    'description' => "MenTalk — learn to speak Russian and English with confidence, with AI.\n\nThis bot signs you in to the MenTalk app without a password. Tap “Continue with Telegram” in the app, then confirm here with one tap.",

    'welcome' => "👋 <b>Hi, :name!</b>\n\nThis is the official sign-in bot of the <b>MenTalk</b> app.\n\nTo sign in, open MenTalk and tap <b>“Continue with Telegram”</b>. Then confirm here with one tap.",
    'help' => 'To sign in, tap <b>“Continue with Telegram”</b> in the MenTalk app.',

    'prompt' => "🔐 <b>Sign in to MenTalk</b>\n\nSomeone asked to sign in to the app with your Telegram account.\n\n📱 Device: <b>:device</b>\n⏱ This request is valid for :minutes min\n\nIf this is you, tap <b>“Confirm”</b>.\n\n⚠️ <i>If you did not make this request, do not tap anything. MenTalk never asks you to confirm a sign-in from a link someone sent you.</i>",
    'approve' => '✅ Confirm',
    'decline' => '✖️ Cancel',

    'approved' => "✅ <b>Sign-in confirmed</b>\n\nGo back to the MenTalk app — you are signed in. Enjoy your practice! 🎉",
    'approved_toast' => 'Done! Go back to the app.',
    'declined' => "✖️ <b>Sign-in cancelled</b>\n\nNobody signed in to your account. If this was you, try again in the app.",
    'declined_toast' => 'Cancelled',
    'expired' => "⌛ <b>This request has expired</b>\n\nGo back to MenTalk and tap <b>“Continue with Telegram”</b> again.",
    'expired_toast' => 'This request has expired. Try again in the app.',
    'taken' => "🔒 <b>This request was opened by another Telegram account</b>\n\nTap “Continue with Telegram” in the app again.",
    'blocked' => "⛔ <b>Your account is blocked</b>\n\nIf you have questions, contact support: :support",
    'blocked_toast' => 'Account blocked',
    'return_button' => '📲 Back to MenTalk',

    'page_title' => 'Back to MenTalk',
    'page_heading' => 'Sign-in confirmed',
    'page_text' => 'Opening the MenTalk app. If it does not open, tap the button below.',
    'page_button' => 'Open MenTalk',
    'page_hint' => 'You can also open the app yourself — you are already signed in.',
];
