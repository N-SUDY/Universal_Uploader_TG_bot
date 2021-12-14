<?php

const TOKEN = "0000000000:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // api bot token, can get from t.me/BotFather
const BOT_USERNAME = "Universal_Uploader_Bot"; # bot username with out @

const ADMIN = 118200234; # bot ban|unban|mc admin

const LOGING = true ;
# You can set up an admin private or a channel to send logs
# const LOG_Chat = ADMIN;
const LOG_Chat = -1001000000000;

const SPONSOR = true;
const SPONSOR_NAME = 'Create By MCCCLXXXIV';
const SPONSOR_LINK = 'https://t.me/MCCCLXXXIV';

$Sentences = [
	"start" => "Hi im Universal Uploader .\nSend your anything you want to be stored ( photo , video , poll , sticker , voice etc... ) and robot will reply it with its share link .\n\n".'<a href="https://github.com/parsapoorsh/Universal_Uploader_TG_bot">Source Code</a>',
    "not_exist" => "message not exist or deleted by bot admin ." ,
    "user_bot_bot_admin" => "You are not bot ADMIN .",
    "banned" => 'You are Banned By ADMIN .',
    "unbanned" => "You are Unbanned By ADMIN .",
];

$FileNames = [
    "pv_users" => 'PV_Users.json',
    "files_id" => 'files_id.json',
    'baned_users' => 'baned_users.json'
];