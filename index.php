<?php
ob_start();

// --- الإعدادات الأساسية ---
$token = "6238340112:AAEl9pNeqoq0A6TsahuhLZYeO-cWmnQCJKQ";
define("API_KEY", $token);

$channel = "@KKeK2"; 

$API_URL = "https://smmlox.com/api/v2";
$API_KEY_SITE = "c5ccca3664a4118b3c7ef4a87e018c39";
$SERVICE_ID = "9905"; 

$db_conn = "host=ep-dawn-credit-agsq9mbt.c-2.eu-central-1.pg.koyeb.app port=5432 dbname=koyebdb user=koyeb-adm password=npg_HI5s4bcWvzre sslmode=require";
$conn = pg_connect($db_conn);

pg_query($conn, "CREATE TABLE IF NOT EXISTS bot_users (user_id BIGINT PRIMARY KEY, last_request TIMESTAMP, step VARCHAR(50), request_count INT DEFAULT 0)");

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

$admin = 5581457665;

$res_count = pg_query($conn, "SELECT SUM(request_count) as total FROM bot_users");
$row_count = pg_fetch_assoc($res_count);
$actual_requests = $row_count['total'] ?? 0;
$total_orders = 17368 + $actual_requests;

if(isset($from_id)){
    $u_res = pg_query($conn, "SELECT * FROM bot_users WHERE user_id = $from_id");
    $user_data = $u_res ? pg_fetch_assoc($u_res) : null;
}

// --- تعليمات البوت المباشرة (نقلناها للأعلى لضمان الاستجابة) ---
if($text == "/start qassim") {
    bot('sendMessage',['chat_id'=>$chat_id, 'text'=>"*تعليمات البوت •\n\n1- لاتعيد الرشق أكثر من مرة؛\n2- الرشق يكتمل خلال ساعة تقريباً.\n\nتواصل: @E2E12*", 'parse_mode'=>"Markdown"]);
    exit; // إنهاء التنفيذ هنا لعدم تداخل الأوامر
}

// --- رسالة الترحيب والرجوع ---
if(preg_match('/^\/start/', $text) || $data == "backk") {
    pg_query($conn, "INSERT INTO bot_users (user_id, step) VALUES ($from_id, 'none') ON CONFLICT (user_id) DO UPDATE SET step = 'none'");

    $msg_welcome = "*- أهلاً بك $name في بوت الرشق المجاني ✅*\n\n" . 
                   "• يمكنك زيادة مشاهدات وتفاعلات منشوراتك مجاناً.\n" .
                   "• يرجى مراجعة [تعليمات البوت](https://t.me/GE_Pbot?start=qassim) قبل البدء.";

    $keyboard = json_encode([
        'inline_keyboard' => [
            [['text' => "مشاهدات تلي 😂✅", 'callback_data' => "new"], ['text' => "تفاعلات تلي ✨", 'callback_data' => "service_2"]],
            [['text' => "الطلبات المكتملة: $total_orders 📥", 'callback_data' => "stats"]]
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

if($data == "new" || $data == "service_2"){
    if(!is_joined($from_id, $channel)){
        bot('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ *اشترك أولاً ثم أرسل* /start :\n$channel", 'parse_mode'=>"Markdown"]);
        return;
    }

    if($user_data && $user_data['last_request']){
        $diff = time() - strtotime($user_data['last_request']);
        if($diff < 1800){
            $rem = 1800 - $diff; $m = floor($rem/60);
            bot('answerCallbackQuery', ['callback_query_id'=>$callback->id, 'text'=>"⏳ حبيبي باقي $m دقيقة لطلبك القادم", 'show_alert'=>true]);
            return;
        }
    }

    $step = ($data == "new") ? "StartNew" : "Step_Service_2";
    pg_query($conn, "UPDATE bot_users SET step = '$step' WHERE user_id = $from_id");

    bot('editMessageText',[
        'chat_id'=>$chat_id,
        'message_id'=>$message_id,
        'text'=>"*✔ أرسل رابط المنشور الآن (مثال: https://t.me/qd3qd/6)*",
        'parse_mode'=>"Markdown",
        'reply_markup'=>json_encode(['inline_keyboard'=>[[['text'=>"• رجوع •",'callback_data'=>"backk"]]]])
    ]);
}

// --- تنفيذ الطلبات ---
if($text && !preg_match('/^\/start/', $text) && $user_data && $user_data['step'] != "none") {
    
    $clean_text = str_replace('@', '', $text);
    $msg = "";

    if($user_data['step'] == "StartNew") {
        file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=$SERVICE_ID&link=$clean_text&quantity=560");
        $msg = "*تم ارسال 10k مشاهدة بنجاح ✅*";
    } 
    elseif($user_data['step'] == "Step_Service_2") {
        file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=6014&link=$clean_text&quantity=11");
        $msg = "*تمَ رشـق التفاعلات بنجاح ✅*";
    }

    if($msg != ""){
        pg_query($conn, "UPDATE bot_users SET step = 'none', last_request = NOW(), request_count = COALESCE(request_count, 0) + 1 WHERE user_id = $from_id");

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
