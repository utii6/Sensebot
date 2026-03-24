<?php
ob_start();

// --- الإعدادات الأساسية ---
$token = "6238340112:AAEl9pNeqoq0A6TsahuhLZYeO-cWmnQCJKQ"; 
define("API_KEY", $token);

// بيانات موقع الرشق الجديدة
$API_URL = "https://darkfollow.shop/api/v2";
$API_KEY_SITE = "efToDQz2mOcIK42Damp8u549cRCDhKykM40xKXIiZ3bxcd5TGYvVzW3M3KdZ";
$SERVICE_ID = "1856";

// رابط قاعدة بيانات Koyeb
$db_url = "Postgres://koyeb-adm:npg_HI5s4bcWvzre@ep-dawn-credit-agsq9mbt.c-2.eu-central-1.pg.koyeb.app/koyebdb";

// الاتصال بقاعدة البيانات
$conn = pg_connect($db_url);

// إنشاء الجداول إذا لم تكن موجودة (تلقائياً)
pg_query($conn, "CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT PRIMARY KEY,
    last_request TIMESTAMP,
    step VARCHAR(50)
)");

function bot($method, $datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    return json_decode($res);
}

$update = json_decode(file_get_contents('php://input'));
if($update->message){
    $chat_id = $update->message->chat->id;
    $from_id = $update->message->from->id;
    $text = $update->message->text;
    $name = $update->message->from->first_name;
} elseif($update->callback_query){
    $chat_id = $update->callback_query->message->chat->id;
    $from_id = $update->callback_query->from->id;
    $data = $update->callback_query->data;
    $message_id = $update->callback_query->message->message_id;
}

$admin = 5581457665;

// جلب بيانات المستخدم من القاعدة
$res = pg_query($conn, "SELECT * FROM users WHERE user_id = $from_id");
$user_data = pg_fetch_assoc($res);

if($text == "/start") {
    pg_query($conn, "INSERT INTO users (user_id, step) VALUES ($from_id, 'none') ON CONFLICT (user_id) DO UPDATE SET step = 'none'");
    bot('sendmessage', [
        'chat_id' => $chat_id,
        'text' => "*- اهلا بك عزيزي ❲ $name ❳ في بوت رشق مشاهدات تلكرام\n- يمكنك رشق مشاهدات مجاناً كل ساعتين ✅*",
        'parse_mode' => "markdown",
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "بدء رشق جديد 🚀", 'callback_data' => 'new']]]])
    ]);
}

if($data == "new"){
    // فحص الوقت (ساعتين = 7200 ثانية)
    if($user_data['last_request']){
        $last_time = strtotime($user_data['last_request']);
        $diff = time() - $last_time;
        if($diff < 7200){
            $remain = 7200 - $diff;
            $hours = floor($remain / 3600);
            $minutes = floor(($remain % 3600) / 60);
            bot('answerCallbackQuery', [
                'callback_query_id' => $update->callback_query->id,
                'text' => "⏳ متبقي $hours ساعة و $minutes دقيقة للطلب مجدداً.",
                'show_alert' => true
            ]);
            return;
        }
    }
    
    pg_query($conn, "UPDATE users SET step = 'waiting_link' WHERE user_id = $from_id");
    bot('editmessagetext', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "*ارسل يوزر القناة أو رابط المنشور الأن ✔*",
        'parse_mode' => "markdown",
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "• رجوع •", 'callback_data' => 'start_back']]]])
    ]);
}

if($text && $user_data['step'] == 'waiting_link' && $text != "/start"){
    pg_query($conn, "UPDATE users SET step = 'none', last_request = NOW() WHERE user_id = $from_id");
    
    bot('sendmessage', ['chat_id' => $chat_id, 'text' => "⏳ جاري إرسال الطلب للموقع..."]);

    // إرسال الطلب لـ API الموقع الجديد
    $api_res = file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=$SERVICE_ID&link=$text&quantity=1000");
    $res_json = json_decode($api_res);

    bot('sendmessage', [
        'chat_id' => $chat_id,
        'text' => "✅ تم إرسال الطلب بنجاح!\n🔗 الرابط: $text\n⏰ يمكنك الطلب مرة أخرى بعد ساعتين.",
        'parse_mode' => "markdown"
    ]);

    // إشعار للأدمن
    bot('sendmessage', [
        'chat_id' => $admin,
        'text' => "🔔 طلب جديد:\nالمستخدم: $from_id\nالرابط: $text"
    ]);
}
?>
