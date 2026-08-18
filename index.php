<?php
ob_start();

// --- قراءة الإعدادات ومتغيرات البيئة (Render Environment Variables) ---
$token = getenv('BOT_TOKEN') ?: "6238340112:AAEl9pNeqoq0A6TsahuhLZYeO-cWmnQCJKQ";
define("API_KEY", $token);

$channel = getenv('CHANNEL_USERNAME') ?: "@KKeK2"; 

$API_URL = "https://kd1s.com/api/v2";
$API_KEY_SITE = getenv('SMM_API_KEY') ?: "c5ccca3664a4118b3c7ef4a87e018c39";
$SERVICE_ID = "17893"; 

$admin = getenv('ADMIN_ID') ? (int)getenv('ADMIN_ID') : 5581457665;

// رابط لعبة XO (Mini App على Render)
$xo_game_url = getenv('XO_GAME_URL') ?: "https://xo-game-app.onrender.com"; 

// --- الاتصال بقاعدة بيانات Supabase عبر متغيرات البيئة ---
$database_url = getenv('DATABASE_URL');
$conn = $database_url ? @pg_connect($database_url) : false;

if (!$conn) {
    error_log("خطأ في الاتصال بقاعدة بيانات Supabase");
} else {
    // إنشاء الجدول وتحديث الأعمدة تلقائياً
    pg_query($conn, "CREATE TABLE IF NOT EXISTS bot_users (
        user_id BIGINT PRIMARY KEY,
        step VARCHAR(50) DEFAULT 'none',
        last_request TIMESTAMP,
        request_count INT DEFAULT 0,
        referred_by BIGINT DEFAULT NULL,
        referrals_count INT DEFAULT 0
    )");
    pg_query($conn, "ALTER TABLE bot_users ADD COLUMN IF NOT EXISTS request_count INT DEFAULT 0");
    pg_query($conn, "ALTER TABLE bot_users ADD COLUMN IF NOT EXISTS referred_by BIGINT DEFAULT NULL");
    pg_query($conn, "ALTER TABLE bot_users ADD COLUMN IF NOT EXISTS referrals_count INT DEFAULT 0");
}

// --- الدوال الأساسية ---
function bot($method, $datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    return json_decode(curl_exec($ch), true);
}

function is_joined($user_id, $channel){
    $res = bot('getChatMember', ['chat_id'=>$channel, 'user_id'=>$user_id]);
    if(!$res || !$res['ok']) return false;
    $st = $res['result']['status'];
    return ($st == 'member' || $st == 'creator' || $st == 'administrator');
}

// دالة التحقق من صحة رابط منشور تليجرام
function is_valid_telegram_url($url) {
    return preg_match('/^(https?:\/\/)?(www\.)?(t\.me|telegram\.me)\/([a-zA-Z0-9_]{5,})\/(\d+)/i', trim($url));
}

// --- استلام بيانات الـ Webhook من تليجرام ---
$update = json_decode(file_get_contents('php://input'));
$message = $update->message ?? null;
$callback = $update->callback_query ?? null;
$text = $message->text ?? null;
$chat_id = $message->chat->id ?? null;
$name = $message->from->first_name ?? '';
$from_id = $message->from->id ?? null;

if($callback){
    $chat_id = $callback->message->chat->id ?? null;
    $from_id = $callback->from->id ?? null;
    $message_id = $callback->message->message_id ?? null;
    $data = $callback->data ?? null;
    $name = $callback->from->first_name ?? '';
}

// معرف البوت لاستخدامه في روابط الإحالة
$bot_username = "VVJJbot"; 

// حساب الطلبات والمستخدمين من قاعدة البيانات
$total_orders = 17368;
$total_users_count = 0;
if ($conn) {
    $res_count = pg_query($conn, "SELECT SUM(request_count) as total, COUNT(user_id) as u_total FROM bot_users");
    if ($res_count) {
        $row_count = pg_fetch_assoc($res_count);
        $actual_requests = $row_count['total'] ?? 0;
        $total_orders += $actual_requests;
        $total_users_count = $row_count['u_total'] ?? 0;
    }
}

$user_data = null;
if(isset($from_id) && $conn){
    $u_res = pg_query($conn, "SELECT * FROM bot_users WHERE user_id = $from_id");
    $user_data = $u_res ? pg_fetch_assoc($u_res) : null;
}

// --- تعليمات البوت المباشرة ---
if($text == "/start qassim") {
    $instructions_text = "📖 *دليل واستخدام البوت والشروط •*\n\n" .
                         "🔹 *طريقة العمل:*\n" .
                         "1️⃣ اختر الخدمة المطلوبة من القائمة الرئيسية (مشاهدات أو تفاعلات).\n" .
                         "2️⃣ قم بنسخ رابط منشور تليجرام وإرساله للبوت بشكل صحيح.\n" .
                         "3️⃣ سيقوم البوت برفع طلبك تلقائياً وبدء المعالجة.\n\n" .
                         "⚠️ *شروط التعليمات والاستخدام:*\n" .
                         "• يُمنع تكرار طلب الرشق لنفس الرابط أكثر من مرة أثناء التنفيذ.\n" .
                         "• يستغرق مكتمل الرشق عادةً من 15 دقيقة إلى ساعة كحد أقصى.\n" .
                         "• تأكد أن القناة والمنشور عام وليس خاصاً لضمان وصول الخدمة.\n" .
                         "• يوجد مهلة انتظار (30 دقيقة) بين كل طلب وآخر، ويمكنك إلغاؤها بدعوة صديق عبر رابطك الخاص.";

    $instructions_keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "الدعم الفني", 'url' => "https://t.me/E2E12", 'style' => "success", 'icon_custom_emoji_id' => "5319161050128459957"]

            ],
            [
                ['text' => "رجوع", 'callback_data' => "backk", 'style' => "danger", 'icon_custom_emoji_id' => "5352637154809879587"]

            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

    bot('sendMessage', [
        'chat_id' => $chat_id, 
        'text' => $instructions_text, 
        'parse_mode' => "Markdown",
        'reply_markup' => $instructions_keyboard,
        'disable_web_page_preview' => true
    ]);
    exit;
}


// --- لوحة التحكم للأدمن (/admin والإذاعة) ---
if($from_id == $admin) {
    if($text == "/admin") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "*📊 لوحة تحكم الأدمن*\n\n• إجمالي المستخدمين: `$total_users_count`\n• إجمالي الطلبات: `$total_orders`\n\nلإرسال إذاعة عامة، استخدم الأمر:\n`/bc النص المراد إرساله`",
            'parse_mode' => "Markdown"
        ]);
        exit;
    }
    
    if(strpos($text, "/bc ") === 0) {
        $bc_msg = substr($text, 4);
        if($conn) {
            $users_q = pg_query($conn, "SELECT user_id FROM bot_users");
            $success = 0;
            while($u = pg_fetch_assoc($users_q)) {
                $r = bot('sendMessage', ['chat_id' => $u['user_id'], 'text' => $bc_msg, 'parse_mode' => "Markdown"]);
                if($r && $r['ok']) $success++;
            }
            bot('sendMessage', ['chat_id' => $admin, 'text' => "✅ تمت الإذاعة بنجاح إلى `$success` مستخدم.", 'parse_mode' => "Markdown"]);
        }
        exit;
    }
}

// --- رسالة الترحيب واللوحة الرئيسية ونظام الإحالة ---
if(preg_match('/^\/start/', $text) || $data == "backk") {
    
    // معالجة رابط الإحالة عند التسجيل الجديد
    $referrer_id = null;
    if(preg_match('/^\/start (\d+)$/', $text, $matches)) {
        $referrer_id = (int)$matches[1];
    }

    if ($conn && $from_id) {
        if(!$user_data) {
            // مستخدم جديد
            if($referrer_id && $referrer_id != $from_id) {
                pg_query($conn, "INSERT INTO bot_users (user_id, step, referred_by) VALUES ($from_id, 'none', $referrer_id) ON CONFLICT (user_id) DO NOTHING");
                
                // مكافأة المُحيل: زيادة عدد الإحالات وتصفير المهلة
                pg_query($conn, "UPDATE bot_users SET referrals_count = COALESCE(referrals_count, 0) + 1, last_request = NULL WHERE user_id = $referrer_id");
                
                // إشعار المُحيل
                bot('sendMessage', [
                    'chat_id' => $referrer_id,
                    'text' => "🎉 *قام شخص جديد بالدخول عبر رابط الدعوة الخاص بك!*\n⚡ *تمت إزالة وقت الانتظار! يمكنك الطلب الآن بدون انتظار.*",
                    'parse_mode' => "Markdown"
                ]);
            } else {
                pg_query($conn, "INSERT INTO bot_users (user_id, step) VALUES ($from_id, 'none') ON CONFLICT (user_id) DO UPDATE SET step = 'none'");
            }
        } else {
            pg_query($conn, "UPDATE bot_users SET step = 'none' WHERE user_id = $from_id");
        }
    }

    $msg_welcome = "*- أهلاً بك $name في بوت الرشق المجاني ✅*\n\n" . 
                   "• يمكنك زيادة مشاهدات وتفاعلات منشوراتك مجاناً.\n" .
                   "• يمكنك أيضاً اللعب والاستمتاع بلعبة XO داخل البوت 🎮.\n" .
                   "• يرجى مراجعة [تعليمات البوت](https://t.me/VVJJbot?start=qassim) قبل البدء.";

    // لوحة الأزرار الملونة والأنشطة الحديثة
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                [
                    'text' => "العب لعبة XO!", 
                    'web_app' => ['url' => $xo_game_url], 
                    'style' => "primary",
                    'icon_custom_emoji_id' => "5843973184314937720" // إيموجي الألعاب
                ]
            ],
            [
                [
                    'text' => "مشاهدات تليجرام", 
                    'callback_data' => "new", 
                    'style' => "success",
                    'icon_custom_emoji_id' => "5402160988181009033" // إيموجي المشاهدات
                ], 
                [
                    'text' => "تفاعلات تليجرام", 
                    'callback_data' => "service_2", 
                    'style' => "success",
                    'icon_custom_emoji_id' => "5303438381743618017" // إيموجي التفاعلات
                ]
            ],
            [
                [
                    'text' => "رابط الدعوة الخاص بك", 
                    'callback_data' => "ref_link", 
                    'style' => "primary",
                    'icon_custom_emoji_id' => "5271604874419647061" // إيموجي الرابط
                ]
            ],
            [
                [
                    'text' => "الطلبات المكتملة: $total_orders ", 
                    'callback_data' => "stats",
                    'icon_custom_emoji_id' => "5206607081334906820" // إيموجي الإحصائيات
                ]
            ]
        ]

    ], JSON_UNESCAPED_UNICODE);

    if(strpos($text, "/start") !== false) {
        bot('sendMessage', ['chat_id' => $chat_id, 'text' => $msg_welcome, 'parse_mode' => "Markdown", 'reply_markup' => $keyboard, 'disable_web_page_preview' => true]);
    } else {
        bot('editMessageText', ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $msg_welcome, 'parse_mode' => "Markdown", 'reply_markup' => $keyboard, 'disable_web_page_preview' => true]);
    }
}

// --- معالجة الضغط على الأزرار ---
if($data == "stats"){
    bot('answerCallbackQuery', ['callback_query_id' => $callback->id, 'text' => "📊 إجمالي الطلبات المكتملة: $total_orders طلب", 'show_alert' => true]);
}

if($data == "ref_link"){
    $my_ref_url = "https://t.me/$bot_username?start=$from_id";
    $ref_count = $user_data['referrals_count'] ?? 0;
    
    $my_ref_url = "https://t.me/$bot_username?start=$from_id";
    $ref_count = $user_data['referrals_count'] ?? 0;
    
    $share_text = urlencode("🎁 اشترك في هذا البوت المجاني لزيادة مشاهدات وتفاعلات تليجرام بسرعة وسهولة!");
    $share_url = "https://t.me/share/url?url=" . urlencode($my_ref_url) . "&text=" . $share_text;
    
    $ref_msg = "<tg-emoji emoji-id=\"5395568509012301111\">🔗</tg-emoji> <b>رابط الدعوة الخاص بك:</b>\n\n" .
           "<a href=\"$my_ref_url\">$my_ref_url</a>\n\n" .
           "👥 <b>عدد دعواتك :</b> <code>$ref_count</code>\n\n" .
           "💡 <b>الميزة:</b> عند مشاركة هذا الرابط مع صديق جديد، سيتم إلغاء وقت الانتظار (30 دقيقة) فوراً لتتمكن من الطلب مجدداً!";
               
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $ref_msg,
        'parse_mode' => "HTML",
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => " مشاركة الرابط", 
                        'url' => $share_url, 
                        'style' => "success",
                        'icon_custom_emoji_id' => "5271604874419647061"
                    ]
                ],
                [
                    [
                        'text' => "🔙 رجوع", 
                        'callback_data' => "backk", 
                        'style' => "danger",
                        'icon_custom_emoji_id' => "5352759161945867747"
                    ]
                ]
            ]
        ], JSON_UNESCAPED_UNICODE)
    ]);
}

if($data == "new" || $data == "service_2"){
    if(!is_joined($from_id, $channel)){
        bot('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ *اشترك أولاً بقناة البوت ثم أرسل* /start :\n$channel", 'parse_mode'=>"Markdown"]);
        return;
    }

    if($user_data && !empty($user_data['last_request'])){
        $diff = time() - strtotime($user_data['last_request']);
        if($diff < 1800){
            $rem = 1800 - $diff; 
            $m = floor($rem/60);
            bot('answerCallbackQuery', ['callback_query_id'=>$callback->id, 'text'=>"⏳ باقي $m دقيقة لطلبك القادم\n💡 يمكنك إزالة الانتظار بدعوة صديق عبر رابطك!", 'show_alert'=>true]);
            return;
        }
    }

    $step = ($data == "new") ? "StartNew" : "Step_Service_2";
    if ($conn) {
        pg_query($conn, "UPDATE bot_users SET step = '$step' WHERE user_id = $from_id");
    }

    bot('editMessageText',[
        'chat_id'=>$chat_id,
        'message_id'=>$message_id,
        'text'=>"✔ *أرسل رابط المنشور الآن (مثال: https://t.me/qd3qd/6)*",
        'parse_mode'=>"Markdown",
        'reply_markup'=>json_encode(['inline_keyboard'=>[[['text'=>"🔙 رجوع",'callback_data'=>"backk", 'style' => "danger"]]]])
    ]);
}

// --- تنفيذ واستلام الطلبات مع التحقق من الرابط ---
if($text && !preg_match('/^\/start/', $text) && $user_data && $user_data['step'] != "none") {
    
    $clean_text = trim(str_replace('@', '', $text));

    // التحقق المباشر من صحة رابط تليجرام
    if(!is_valid_telegram_url($clean_text)) {
        bot('sendMessage', [
            'chat_id' => $chat_id, 
            'text' => "❌ *الرابط الذي أرسلته غير صالـح!*\nيرجى إرسال رابط منشور تليجرام صحيح بالشكل التالي:\n`https://t.me/qd3qd/6`", 
            'parse_mode' => "Markdown"
        ]);
        return;
    }

    $msg = "";

    if($user_data['step'] == "StartNew") {
        file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=$SERVICE_ID&link=$clean_text&quantity=560");
        $msg = "*تم إرسال 10k مشاهدة بنجاح ✅*";
    } 
    elseif($user_data['step'] == "Step_Service_2") {
        file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=6014&link=$clean_text&quantity=11");
        $msg = "*تم رشـق التفاعلات بنجاح ✅*";
    }

    if($msg != ""){
        if ($conn) {
            pg_query($conn, "UPDATE bot_users SET step = 'none', last_request = NOW(), request_count = COALESCE(request_count, 0) + 1 WHERE user_id = $from_id");
        }

        bot('sendMessage',['chat_id'=>$chat_id, "text"=>$msg, 'parse_mode'=>"Markdown"]);

        $user_name = $message->from->first_name ?? "بدون اسم";
        $user_username = isset($message->from->username) ? "@".$message->from->username : "لا يوجد معرف";
        $user_id_link = "[".$user_name."](tg://user?id=".$from_id.")";

        $admin_msg = "*طلب جديد من مستخدم ✅*\n\n" .
                     "• الاسم: $user_id_link\n" .
                     "• المعرف: $user_username\n" .
                     "• الآيدي: `$from_id` \n\n" .
                     "• النوع: ".$user_data['step']."\n" .
                     "• الرابط: $clean_text";

        bot('sendMessage',['chat_id' => $admin, 'text' => $admin_msg, 'parse_mode' => "Markdown", 'disable_web_page_preview' => true]);
    }
}
?>
