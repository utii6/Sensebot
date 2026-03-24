<?php
ob_start();

// --- الإعدادات الأساسية ---
$token = "6238340112:AAEl9pNeqoq0A6TsahuhLZYeO-cWmnQCJKQ"; 
define("API_KEY", $token);

// إعدادات الاشتراك الإجباري (يوزر قناتك)
$channel = "@KKeK2"; 

// بيانات موقع الرشق (DarkFollow)
$API_URL = "https://darkfollow.shop/api/v2";
$API_KEY_SITE = "efToDQz2mOcIK42Damp8u549cRCDhKykM40xKXIiZ3bxcd5TGYvVzW3M3KdZ";
$SERVICE_ID = "1856";

// رابط قاعدة بيانات Koyeb
$db_conn = "host=ep-dawn-credit-agsq9mbt.c-2.eu-central-1.pg.koyeb.app port=5432 dbname=koyebdb user=koyeb-adm password=npg_HI5s4bcWvzre sslmode=require";
$conn = pg_connect($db_conn);

// إنشاء الجدول إذا لم يكن موجوداً
pg_query($conn, "CREATE TABLE IF NOT EXISTS bot_users (user_id BIGINT PRIMARY KEY, last_request TIMESTAMP, step VARCHAR(50))");

function bot($method, $datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    return json_decode(curl_exec($ch));
}

// دالة فحص الاشتراك
function is_joined($user_id, $channel){
    $res = bot('getChatMember', ['chat_id' => $channel, 'user_id' => $user_id]);
    $st = $res->result->status;
    return ($st == 'member' || $st == 'creator' || $st == 'administrator');
}

$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$text = $message->text;
$chat_id = $message->chat->id;
$name = $message->from->first_name;
$from_id = $message->from->id;

if(isset($update->callback_query)){
    $up = $update->callback_query;
    $chat_id = $up->message->chat->id;
    $from_id = $up->from->id;
    $message_id = $up->message->message_id;
    $data = $up->data;
}

$admin = 5581457665;

// جلب بيانات المستخدم من القاعدة
$u_res = pg_query($conn, "SELECT * FROM bot_users WHERE user_id = $from_id");
$user_data = pg_fetch_assoc($u_res);

if($text == "/start") {
    pg_query($conn, "INSERT INTO bot_users (user_id, step) VALUES ($from_id, 'none') ON CONFLICT (user_id) DO UPDATE SET step = 'none'");
    bot('sendmessage',[
        'chat_id'=>$chat_id,
        "text"=>"*- اهلا بك عزيزي ❲ $name ❳ في بوت رشق مشاهدات تلكرام\n- يمكنك رشق مشاهدات ( 10k ) لگل منشوراتك من خلال البوت مجانا*",
        'parse_mode'=>"markdown",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[[['text'=>"بدء رشق جديد 😂✅", 'callback_data'=>'new']]]
        ])
    ]);
}

if($data == "backk"){
    pg_query($conn, "UPDATE bot_users SET step = 'none' WHERE user_id = $from_id");
    bot('editmessagetext',[
        'chat_id'=>$chat_id,
        'message_id'=>$message_id,
        'text'=>"*- اهلا بك عزيزي ❲ $name ❳ في بوت رشق مشاهدات تلكرام\n- يمكنك رشق مشاهدات ( 10k ) لگل منشوراتك من خلال البوت مجانا*",
        'parse_mode'=>"markdown",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[[['text'=>"بدء رشق جديد 😂✅", 'callback_data'=>'new']]]
        ])
    ]);
}

if($data == "new"){
    // 1. فحص الاشتراك الإجباري
    if(!is_joined($from_id, $channel)){
        bot('sendmessage', [
            'chat_id' => $chat_id,
            'text' => "❌ حبيبي اشترك، وأرسل /start:\n\n$channel",
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "اضغط هنا✅", 'url' => "https://t.me/".str_replace('@','',$channel)]]]
            ])
        ]);
        return;
    }

    // 2. فحص وقت الانتظار (ساعتين = 7200 ثانية)
    if($user_data['last_request']){
        $diff = time() - strtotime($user_data['last_request']);
        if($diff < 7200){
            $rem = 7200 - $diff;
            $h = floor($rem/3600); $m = floor(($rem%3600)/60);
            bot('answerCallbackQuery', ['callback_query_id'=>$update->callback_query->id, 'text'=>"😂⏳ يحلو متبقي  $h ساعة و $m ، دقيقة للطلب بعد.", 'show_alert'=>true]);
            return;
        }
    }

    pg_query($conn, "UPDATE bot_users SET step = 'StartNew' WHERE user_id = $from_id");
    bot('editmessagetext',[
        'chat_id'=>$chat_id,
        'message_id'=>$message_id,
        'text'=>"*ارسل يوزر القناة الأن ✔*\n- مع @ او بدون @",
        'parse_mode'=>"markdown",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[[['text'=>"• رجوع •", 'callback_data'=>'backk']]]
        ])
    ]);
}

if($text != "/start" and $user_data['step'] == "StartNew") {
    // تحديث الوقت والحالة فوراً
    pg_query($conn, "UPDATE bot_users SET step = 'none', last_request = NOW() WHERE user_id = $from_id");
    
    // إرسال الطلب للموقع (تم حذف رسالة "جاري الإرسال")
    $clean_text = str_replace('@', '', $text);
    file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=$SERVICE_ID&link=$clean_text&quantity=560");

    bot('sendmessage',[
        'chat_id'=>$chat_id,
        "text"=>"* تم ارسال 10k مشاهده لكل منشورات قناتك بنجاح\n\nللقناة : @$clean_text\n\nيمكنك طلب المزيد  ✅*",
        'parse_mode'=>"markdown",
    ]);

    bot('sendmessage',[
        'chat_id'=>$admin,
        "text"=>"*طلب جديد 😂✅*\nللقناة : @$clean_text",
        'parse_mode'=>"markdown",
    ]);
}

if($text == "/start qassim") {
    bot('sendmessage',[
        'chat_id'=>$chat_id,
        "text"=>"*تعليمات البوت •\n\n1-لاتعيد الرشق اكثر من مره واحده ؛ 🛡\n2-سيوصل الرشق بعد ساعه ام نص ساعه بعد الطلب ؛ ✔\n\n @E2E12 شكرا لاستخدامكم البوت الخاص بنا ❤*",
        'parse_mode'=>"markdown",
    ]);
}
?>
