<?php
session_start();

if(isset($_POST['ajaxsend']) && $_POST['ajaxsend']==true){
    if(!isset($_SESSION['username'])) {
        die("Error: Not logged in");
    }
    
    $message = trim($_POST['chat']);
    if(empty($message)) {
        die("Error: Empty message");
    }
    
    if(strlen($message) > 1000000000) {
        die("Error: Message too long");
    }
    
    $chat = fopen("chatdata.txt", "a");
    if(flock($chat, LOCK_EX)) {
        $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $timestamp = time();
        $data="<div class='message' data-timestamp='{$timestamp}' data-username='{$username}' data-message='{$message}'><b>{$username}:</b> <span class='message-content'>" . nl2br($message) . "</span> <button class='copy-btn' onclick='copyMessage(this)'>📋</button></div>\n";
        fwrite($chat,$data);
        flock($chat, LOCK_UN);
    }
    fclose($chat);

    readAndOutputChat();
    
} else if(isset($_POST['ajaxget']) && $_POST['ajaxget']==true){
    readAndOutputChat();
    
} else if(isset($_POST['ajaxclear']) && $_POST['ajaxclear']==true){
    if(!isset($_SESSION['username'])) {
        die("Error: Not logged in");
    }
    
    $chat = fopen("chatdata.txt", "w");
    if(flock($chat, LOCK_EX)) {
        $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
        $data="<div class='message'><b>{$username}</b> cleared chat<br></div>\n";
        fwrite($chat,$data);
        flock($chat, LOCK_UN);
    }
    fclose($chat);
}

function readAndOutputChat() {
    if(file_exists("chatdata.txt") && filesize("chatdata.txt") > 0) {
        $chat = fopen("chatdata.txt", "r");
        if(flock($chat, LOCK_SH)) {
            echo fread($chat, filesize("chatdata.txt"));
            flock($chat, LOCK_UN);
        }
        fclose($chat);
    } else {
        echo "<div class='message'><i>Chat is empty. Start the conversation!</i></div>";
    }
}
?>
