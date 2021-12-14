<?php

$telegram_ip_ranges = [
    ['lower' => '149.154.160.0', 'upper' => '149.154.175.255'], // literally 149.154.160.0/20
    ['lower' => '91.108.4.0',    'upper' => '91.108.7.255'],    // literally 91.108.4.0/22
];
$ip_dec = (float) sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
foreach ($telegram_ip_ranges as $telegram_ip_range) {
    // Make sure the IP is valid.
    $lower_dec = (float) sprintf("%u", ip2long($telegram_ip_range['lower']));
    $upper_dec = (float) sprintf("%u", ip2long($telegram_ip_range['upper']));
    if ($ip_dec <= $lower_dec and $ip_dec >= $upper_dec) die();
}

include 'Telegram.php';
include "config.php" ;

$bot = new Telegram( TOKEN );

if ( SPONSOR ){
    $KeyBoard = $bot->buildInlineKeyBoard([
        [
            $bot->buildInlineKeyboardButton( SPONSOR_NAME , SPONSOR_LINK )
        ]
    ]);
} else {
    $KeyBoard = "{}";
}

$update = $bot->getData();

if ( isset($update['message']) ) {
    $chat_id = $bot->ChatID();
    $message_id = $bot->MessageID();  
    $user_id = $bot->UserID();
    $pv_users = read_json_file($FileNames["pv_users"]);
    $baned_users = read_json_file($FileNames["baned_users"]);
    $files_id = read_json_file($FileNames["files_id"]);
    
    if ( !in_array($user_id,$pv_users) ){ array_push($pv_users,$user_id); write_json_file($pv_users,$FileNames["pv_users"]); }
    $text = $bot->Text();
    if ( $user_id == ADMIN and $text == "!mc" ){
        $bot->sendMessage([
            "chat_id" => $chat_id,
            "text" => "member count : " . count( $pv_users ),
            "reply_to_message_id" => $message_id,
            "reply_markup" => $KeyBoard
        ]);
        exit();
    }
    
    if ( !in_array( $user_id , $baned_users ) ){
        if ($text == "/start"){
            $bot->sendMessage([
                "chat_id" => $chat_id,
                "text" => $Sentences["start"],
                "reply_to_message_id" => $message_id,
                "reply_markup" => $KeyBoard,
				"parse_mode" => 'HTML'
            ]);
        }
        elseif ( startsWith( $text, "/start" ) ){
            $data = str_replace( "/start ", "", $text );
            if ( isset( $files_id[$data] ) ){
                if ( !isset( $files_id[$data][3] ) or !$files_id[$data][3] ){
                    $r1 = $bot->copyMessage([
                        "chat_id" => $chat_id,
                        "reply_to_message_id" => $message_id,
                        "from_chat_id" => $files_id[$data][0],
                        "message_id" => $files_id[$data][1],
                        "reply_markup" => $KeyBoard
                    ]);
                } else {
                    $r1 = $bot->forwardMessage([
                        "chat_id" => $chat_id , 
                        "from_chat_id" => $files_id[$data][0],
                        "message_id" => $files_id[$data][1],
                    ]);
                }
                if ( $r1['ok'] ) {
                    if ( $user_id != ADMIN ) { 
                        $files_id[$data][2] += 1;
                        write_json_file( $files_id , $FileNames["files_id"] );
                    }
                    $string = '';
                    $string .= "Number of Clicks : " . $files_id[$data][2] .PHP_EOL;
                    $string .= "Share link : t.me/".BOT_USERNAME."?start=$data" .PHP_EOL;
                    if ( isset( $r1["result"]["message_id"] ) ) {
                        $bot->sendMessage([ 
                            "chat_id" => $chat_id,
                            "text" => $string,
                            "reply_to_message_id" => $r1["result"]["message_id"],
                            "reply_markup" => $KeyBoard
                        ]);
                    }
                } else $bot->sendMessage([ "chat_id" => $chat_id, "text" => $Sentences["not_exist"] , "reply_to_message_id" => $message_id ,"reply_markup" => $KeyBoard ]);
            } else $bot->sendMessage([ "chat_id" => $chat_id, "text" => $Sentences["not_exist"] , "reply_to_message_id" => $message_id ,"reply_markup" => $KeyBoard ]);
        }
        elseif ( $text == '' ) {
            $file_id = random_id( $chat_id , $message_id );
            while ( in_array( $file_id , $files_id ) ) $file_id = random_id( $chat_id , $message_id );
            if ( isset( $update['message']['dice'] ) or isset( $update['message']['poll'] ) ) $forward = true ; else $forward = false;
            $files_id[$file_id] = [ $chat_id , $message_id , 0 , $forward ];
            write_json_file( $files_id , $FileNames["files_id"] );
            $bot->sendMessage([
                "chat_id" => $chat_id,
                "text" => 'Share link : '.'t.me/'.BOT_USERNAME.'?start='.$file_id,
                "reply_to_message_id" => $message_id,
                "reply_markup" => $KeyBoard
            ]);
            if ( $user_id != ADMIN ){
                if ( LOGING ){
                    $rf = $bot->forwardMessage([
                        "chat_id" => LOG_Chat , 
                        "from_chat_id" => $chat_id ,
                        "message_id" => $message_id
                    ]);
                    $AdminKeyBoard = $bot->buildInlineKeyBoard([
                        [$bot->buildInlineKeyboardButton( 'Delete ❌',   $url = '', $callback_data = "delete-$chat_id-$message_id" )],
                        [$bot->buildInlineKeyboardButton( 'Ban User 🔨', $url = '', $callback_data = "ban-$user_id" )]
                    ]);
                    $report_string = '';
                    $username = $bot->Username();
                    $first_name = $bot->FirstName();
                    $last_name = $bot->LastName();
                    $report_string .= "#= Upload By :" . PHP_EOL;
                    $report_string .= "|_ user_id : $user_id" . PHP_EOL;
if ($username != '')$report_string .= "|_ username : @$username" . PHP_EOL;
                    $report_string .= "|_ first_name : \"$first_name\"" . PHP_EOL;
                    $report_string .= "|_ last_name : \"$last_name\"" . PHP_EOL;
                    $report_string .= '#= Share link : '. 't.me/'.BOT_USERNAME.'?start='.$file_id;
                    $bot->sendMessage([
                        "chat_id" => LOG_Chat,
                        "text" => $report_string,
                        "reply_to_message_id" => $rf["result"]["message_id"],
                        "reply_markup" => $AdminKeyBoard
                    ]);
                }
            }
        }
    }
}
elseif ( isset( $update['callback_query'] ) ){
    if ( $update['callback_query']['from']['id'] == ADMIN ){
        $data = explode( "-", $update['callback_query']['data'] );
        $action = $data[0];
        if ( $action == 'delete' ) { 
            $bot->sendMessage([
                "chat_id" => $data[1],
                "text" => "Deleted By ADMIN .",
                "reply_to_message_id" => $data[2],
            ]);
            $DeleteKeyBoard = $bot->buildInlineKeyBoard([
                [$bot->buildInlineKeyboardButton( 'Deleted ✅' , $url = '' , $callback_data = "None")],
                $update['callback_query']['message']['reply_markup']['inline_keyboard'][1]
            ]);
            $rd = $bot->deleteMessage([ 'chat_id' => $data[1] , 'message_id' => $data[2] ]);
            if ( $rd['ok'] ) {
                $bot->editMessageText([
                    "chat_id" => $update['callback_query']['message']['chat']['id'],
                    "message_id" => $update['callback_query']['message']['message_id'],
                    'text' => $update['callback_query']['message']['text'],
                    "reply_markup" => $DeleteKeyBoard
                ]);
                $bot->answerCallbackQuery([
                    'callback_query_id' => $update['callback_query']['id'],
                    'text' => "deleted",
                    "show_alert" => true
                ]);
            } else {
                $bot->answerCallbackQuery([
                    'callback_query_id' => $update['callback_query']['id'],
                    'text' => "error_code : " . $rd['error_code'] . "\n" . "description : " . $rd['description'],
                    "show_alert" => true
                ]);
            }
        }
        elseif ( $action == 'ban' ){
            $user_id = $data[1];
            $baned_users = read_json_file($FileNames["baned_users"]);
            if ( !in_array( $user_id , $baned_users ) ) {
                array_push( $baned_users , $user_id );
                write_json_file( $baned_users , $FileNames["baned_users"] );
                $bot->sendMessage([
                    "chat_id" => $user_id,
                    "text" => $Sentences["banned"],
                ]);
                $BanKeyBoard = $bot->buildInlineKeyBoard([
                    $update['callback_query']['message']['reply_markup']['inline_keyboard'][0],
                    [$bot->buildInlineKeyboardButton( 'Unban User 🔨' , $url = '' , $callback_data = "unban-$user_id" )]
                ]);
                $bot->editMessageText([
                    "chat_id" => $update['callback_query']['message']['chat']['id'],
                    "message_id" => $update['callback_query']['message']['message_id'],
                    'text' => $update['callback_query']['message']['text'],
                    "reply_markup" => $BanKeyBoard
                ]);
                $bot->answerCallbackQuery([
                    'callback_query_id' => $update['callback_query']['id'],
                    'text' => "Banned $user_id",
                    "show_alert" => true
                ]);
            } else {
                $bot->answerCallbackQuery([
                    'callback_query_id' => $update['callback_query']['id'],
                    'text' => "User already banned",
                    "show_alert" => true
                ]);
            }
        }
        elseif ( $action == 'unban' ){
            $user_id = $data[1];
            $baned_users = read_json_file($FileNames["baned_users"]);
            if ( ( $key = array_search( $user_id , $baned_users ) ) !== false ) unset( $baned_users[ $key ] );
            write_json_file( $baned_users , $FileNames["baned_users"] );
            $bot->sendMessage([
                "chat_id" => $user_id,
                "text" => $Sentences["unbanned"],
            ]);
            $UnBanKeyBoard = $bot->buildInlineKeyBoard([
                $update['callback_query']['message']['reply_markup']['inline_keyboard'][0],
                [$bot->buildInlineKeyboardButton( 'ban User 🔨' , $url = '' , $callback_data = "ban-$user_id" )]
            ]);
            $bot->editMessageText([
                "chat_id" => $update['callback_query']['message']['chat']['id'],
                "message_id" => $update['callback_query']['message']['message_id'],
                'text' => $update['callback_query']['message']['text'],
                "reply_markup" => $UnBanKeyBoard
            ]);
            $bot->answerCallbackQuery([
                'callback_query_id' => $update['callback_query']['id'],
                'text' => "UnBanned $user_id",
                "show_alert" => true
            ]);
        }
    } else {
        $bot->answerCallbackQuery([
            'callback_query_id' => $update['callback_query']['id'],
            'text' => $Sentences['user_bot_bot_admin'],
            "show_alert" => true
        ]);
    }
}
function random_id( int $user_id , int $message_id ):int {
    return mt_rand( $user_id, time() * $message_id ) * mt_rand( 1, $message_id );
}
function startsWith ($string, $startString):bool {
    return (substr($string, 0, strlen($startString)) === $startString);
}
function write_json_file(array $contents,string $file_name = "file-json" ,bool $compress = false) {
    if ( !$compress ) {
        file_put_contents($file_name, json_encode($contents ,  JSON_PRETTY_PRINT));
    } else {
        file_put_contents($file_name, gzcompress(json_encode($contents , JSON_PRETTY_PRINT ) , 9));
    }
}
function read_json_file(string $file_name = "file-json" , bool $compress = false):array {
    if (!file_exists($file_name) ) file_put_contents($file_name,"[]");
    if ( !$compress ) {
        return json_decode(file_get_contents($file_name), true);
    } else {
        return json_decode(gzuncompress(file_get_contents($file_name)), true);
    }
}