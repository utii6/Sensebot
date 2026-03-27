<?php
ob_start();

// --- الإعدادات الأساسية ---
$token = "6238340112:AAEl9pNeqoq0A6TsahuhLZYeO-cWmnQCJKQ"; 
define("API_KEY", $token);

$channel = "@KKeK2"; 

$API_URL = "https://darkfollow.shop/api/v2";
$API_KEY_SITE = "efToDQz2mOcIK42Damp8u549cRCDhKykM40xKXIiZ3bxcd5TGYvVzW3M3KdZ";
$SERVICE_ID = "1856";

$db_conn = "host=ep-dawn-credit-agsq9mbt.c-2.eu-central-1.pg.koyeb.app port=5432 dbname=koyebdb user=koyeb-adm password=npg_HI5s4bcWvzre sslmode=require";
$conn = pg_connect($db_conn);

pg_query($conn, "CREATE TABLE IF NOT EXISTS bot_users (user_id BIGINT PRIMARY KEY, last_request TIMESTAMP, step VARCHAR(50))");

function bot($method, $datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    return json_decode(curl_exec($ch), true); // ✅ مصفوفة
}

// تصحيح is_joined
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

// جلب بيانات المستخدم
if(isset($from_id)){
    $u_res = pg_query($conn, "SELECT * FROM bot_users WHERE user_id = $from_id");
    $user_data = $u_res ? pg_fetch_assoc($u_res) : null;
}

if($text == "/start") {
    pg_query($conn, "INSERT INTO bot_users (user_id, step) VALUES ($from_id, 'none') ON CONFLICT (user_id) DO UPDATE SET step = 'none'");

    bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>"*- اهلا بك عزيزي $name في بوت رشق مشاهدات تلكرام\n- يمكنك رشق مشاهدات ( 10k ) لگل منشوراتك من خلال البوت مجانا*",
        'parse_mode'=>"Markdown",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[
                [
                    ['text'=>"بدء رشق جديد 😂✅",'callback_data'=>"new"]
                ]
            ]
        ], JSON_UNESCAPED_UNICODE)
    ]);
}

if($data == "backk"){
    pg_query($conn, "UPDATE bot_users SET step = 'none' WHERE user_id = $from_id");

    bot('editMessageText',[
        'chat_id'=>$chat_id,
        'message_id'=>$message_id,
        'text'=>"*- اهلا بك عزيزي $name في بوت رشق مشاهدات تلكرام\n- يمكنك رشق مشاهدات ( 10k ) لگل منشوراتك من خلال البوت مجانا*",
        'parse_mode'=>"Markdown",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[
                [
                    ['text'=>"بدء رشق جديد 😂✅",'callback_data'=>"new"]
                ]
            ]
        ], JSON_UNESCAPED_UNICODE)
    ]);
}

if($data == "new"){

    if(!is_joined($from_id, $channel)){
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ *اشترك حبيبي ودز* /start :\n$channel",
            'parse_mode'=>"Markdown",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text'=>"مَـدار🪐",'url'=>"https://t.me/".str_replace('@','',$channel)]
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE)
        ]);
        return;
    }

    $res = pg_query($conn, "SELECT * FROM bot_users WHERE user_id = $from_id");
    $user_data = $res ? pg_fetch_assoc($res) : null;

    if($user_data && $user_data['last_request']){
        $diff = time() - strtotime($user_data['last_request']);
        if($diff < 1800){
            $rem = 1800 - $diff;
            $m = floor($rem/60);

            bot('answerCallbackQuery', [
                'callback_query_id'=>$callback['id'],
                'text'=>"😑⏳ حبيبي باقي $m دقيقة",
                'show_alert'=>true
            ]);
            return;
        }
    }

    pg_query($conn, "UPDATE bot_users SET step = 'StartNew' WHERE user_id = $from_id");

    bot('editMessageText',[
        'chat_id'=>$chat_id,
        'message_id'=>$message_id,
        'text'=>"*✔ دز رابط منشورك بالشكل:*\nhttps://t.me/qd3qd/6",
        'parse_mode'=>"Markdown",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[
                [
                    ['text'=>"• رجوع •",'callback_data'=>"backk"]
                ]
            ]
        ], JSON_UNESCAPED_UNICODE)
    ]);
}

if($text != "/start" && $user_data && $user_data['step'] == "StartNew") {
    pg_query($conn, "UPDATE bot_users SET step = 'none', last_request = NOW() WHERE user_id = $from_id");
    
    $clean_text = str_replace('@', '', $text);
    file_get_contents("$API_URL?key=$API_KEY_SITE&action=add&service=$SERVICE_ID&link=$clean_text&quantity=560");

    bot('sendMessage',[
        'chat_id'=>$chat_id,
        "text"=>"*تم ارسال 10k مشاهدة بنجاح\n\nالقناة: $clean_text*",
        'parse_mode'=>"Markdown",
    ]);

    bot('sendMessage',[
        'chat_id'=>$admin,
        "text"=>"*طلب جديد 😂✅*\nالقناة: $clean_text",
        'parse_mode'=>"Markdown",
    ]);
}

if($text == "/start qassim") {
    bot('sendMessage',[
        'chat_id'=>$chat_id,
        "text"=>"*تعليمات البوت •\n\n1-لاتعيد الرشق اكثر من مره ؛\n2-الرشق يوصل خلال ساعه تقريباً\n\n@E2E12*",
        'parse_mode'=>"Markdown",
    ]);
}
?>
