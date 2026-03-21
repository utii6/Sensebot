#برمجه @JJJ22J 
#غير مسموح بالبيع اخلي مسؤليتي ع اي شخص يبيعه
#من الصفر برمجتي 
#تابع لقناة @SeroBots
<?php
ob_start();
$token = "6238340112:AAEl9pNeqoq0A6TsahuhLZYeO-cWmnQCJKQ"; 
define("API_KEY",$token);
echo file_get_contents("https://api.telegram.org/bot" . API_KEY . "/setwebhook?url=" . $_SERVER['SERVER_NAME'] . "" . $_SERVER['SCRIPT_NAME']);
function bot($method,$datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
$ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}
 
 




$update = json_decode(file_get_contents('php://input'));
$message= $update->message;
$text = $message->text;
$chat_id= $message->chat->id;
$name = $message->from->first_name;
$user = $message->from->username;
$message_id = $update->message->message_id;
$from_id = $update->message->from->id;
$message = $update->message;
$chat_id = $message->chat->id;

mkdir("makeorder");
mkdir("message");
$bot_f = file_get_contents("makeorder/$from_id.txt");
$msg_idd = file_get_contents("message/$from_id.txt");


if(isset($update->callback_query)){

$up = $update->callback_query;
$chat_id = $up->message->chat->id;
$from_id = $up->from->id;
$user = $up->from->username;
$name = $up->from->first_name;
$message_id = $up->message->message_id;
$data = $up->data;
}
$admin=5581457665;
$UserB = bot('getme',['bot'])->result->username;
$BotUser ="$UserB";

if($text == "/start") {
bot('sendmessage',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
"text"=>"*
- اهلا بك عزيزي ❲ $name ❳ في بوت رشق مشاهدات تلكرام
- يمكنك رشق مشاهدات ( 10k ) لگل منشوراتك من خلال البوت مجانا
*",
'parse_mode'=>"markdown",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"بدء رشق جديد 😂✅", 'callback_data'=>'new']],
]
])
]);
}



if($data == "backk"){
	

$vv=bot('editmessagetext',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
'text'=>"*
- اهلا بك عزيزي ❲ $name ❳ في بوت رشق مشاهدات تلكرام
- يمكنك رشق مشاهدات ( 10k ) لگل منشوراتك من خلال البوت مجانا
*",
'parse_mode'=>"markdown",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"بدء رشق جديد 😂✅", 'callback_data'=>'new']],
]
])
])->result->message_id; 
file_put_contents("makeorder/$from_id.txt",null);

}

if($data == "new"){
	

$vv=bot('editmessagetext',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
'text'=>"
*ارسل يوزر القناة الأن ✔*
- مع @ او بدون @
",
'parse_mode'=>"markdown",
'disable_web_page_preview'=>'true',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"• رجوع •", 'callback_data'=>'backk']],
]
])
])->result->message_id; 
file_put_contents("makeorder/$from_id.txt","StartNew");
file_put_contents("message/2$from_id.txt",$vv);
}





if($text != "/start" and $bot_f=="StartNew") {

	file_put_contents("makeorder/$from_id.txt",null);
	
	bot('deletemessage',[
'chat_id'=>$chat_id,
'message_id'=>file_get_contents("message/2$from_id.txt"),
]);
$s=bot('sendmessage',[
'chat_id'=>$chat_id,
'text'=>"*
- تم ارسال طلب الرشق ✔
- ننتضر الاستجابه 😂🔥
*",
'parse_mode'=>"markdown",

])->result->message_id; 
file_put_contents("message/$from_id.txt",$s);
for($i=25;$i<30;$i++){
$vvsend = file_get_contents("http://sero2link.ml/N/vg.php?user=". str_replace('@',null,$text));
}

bot('deletemessage',[
'chat_id'=>$chat_id,
'message_id'=>file_get_contents("message/$from_id.txt"),
]);

bot('sendmessage',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
"text"=>"*
 تم ارسال 10k مشاهده لكل منشورات قناتك بنجاح
*

[تعليمات البوت ✅](https://t.me/$BotUser?start=qassim)

للقناة : @".str_replace('@',null,$text)."

",
'parse_mode'=>"markdown",

]);
bot('sendmessage',[
'chat_id'=>$admin,
'message_id'=>$message_id,
"text"=>"*
*طلب جديد ✅*

للقناة : @".str_replace('@',null,$text)."

*",
'parse_mode'=>"markdown",

]);

}

if($text == "/start qassim") {
	bot('sendmessage',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
"text"=>"*
تعليمات البوت •
*
1-لاتعيد الرشق اكثر من مره واحده ؛ 🛡
2-سيوصل الرشق بعد ساعه ام نص ساعه بعد الطلب ؛ ✔

 @E2E12 شكرا لاستخدامكم البوت الخاص بنا ❤
",
'parse_mode'=>"markdown",

]);
}
