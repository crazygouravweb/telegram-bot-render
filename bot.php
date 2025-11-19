<?php
// Telegram Bot Configuration
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '8043376276:AAH3-IgK2sWb0trwWvg4RlrslTbxW8obcI0');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

class TelegramBot {
    
    public function apiRequest($method, $parameters = []) {
        $url = API_URL . $method;
        
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }
        
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
    
    public function sendMessage($chat_id, $text, $reply_markup = null) {
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = $reply_markup;
        }
        
        return $this->apiRequest('sendMessage', $data);
    }
    
    public function setWebhook($url) {
        $result = $this->apiRequest('setWebhook', ['url' => $url]);
        return $result;
    }
    
    public function deleteWebhook() {
        $result = $this->apiRequest('deleteWebhook');
        return $result;
    }
    
    public function processMessage($message) {
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $first_name = $message['chat']['first_name'] ?? 'User';
        
        // Log the message
        error_log("Received message from {$first_name}: {$text}");
        
        // Handle different commands
        switch ($text) {
            case '/start':
                $welcome_message = "👋 Hello <b>{$first_name}</b>!\n\n";
                $welcome_message .= "I'm your Telegram bot hosted on Render!\n\n";
                $welcome_message .= "Available commands:\n";
                $welcome_message .= "/start - Show this welcome message\n";
                $welcome_message .= "/help - Get help\n";
                $welcome_message .= "/time - Get current server time\n";
                $welcome_message .= "/chatid - Get your chat ID\n";
                $welcome_message .= "Or just send me any message!";
                
                $this->sendMessage($chat_id, $welcome_message);
                break;
                
            case '/help':
                $help_message = "🤖 <b>Bot Help</b>\n\n";
                $help_message .= "I'm a simple PHP bot demonstrating how to create and host a Telegram bot on Render.\n\n";
                $help_message .= "<b>Commands:</b>\n";
                $help_message .= "/start - Welcome message\n";
                $help_message .= "/help - This help message\n";
                $help_message .= "/time - Current server time\n";
                $help_message .= "/chatid - Get your chat ID\n\n";
                $help_message .= "Feel free to modify me to add more features!";
                
                $this->sendMessage($chat_id, $help_message);
                break;
                
            case '/time':
                $server_time = date('Y-m-d H:i:s');
                $time_message = "🕒 <b>Server Time</b>\n";
                $time_message .= "Current time on Render server:\n";
                $time_message .= "<code>{$server_time} UTC</code>";
                
                $this->sendMessage($chat_id, $time_message);
                break;
                
            case '/chatid':
                $chatid_message = "🆔 <b>Your Chat ID</b>\n";
                $chatid_message .= "Your chat ID is: <code>{$chat_id}</code>\n\n";
                $chatid_message .= "This is useful for bot development!";
                
                $this->sendMessage($chat_id, $chatid_message);
                break;
                
            default:
                if (!empty($text)) {
                    $response = "You said: <i>{$text}</i>\n\n";
                    $response .= "Try /help to see available commands.";
                    $this->sendMessage($chat_id, $response);
                }
                break;
        }
    }
    
    public function handleUpdate() {
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);
        
        if (isset($update['message'])) {
            $this->processMessage($update['message']);
        }
        
        return "OK";
    }
}

// Initialize and run the bot
$bot = new TelegramBot();

// Handle webhook calls (for production)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot->handleUpdate();
} else {
    // For testing via browser
    echo "🤖 Telegram Bot is running!<br>";
    echo "This bot is hosted on Render and ready to receive messages.<br><br>";
    
    // Webhook management
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'set_webhook':
                $webhook_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
                $result = $bot->setWebhook($webhook_url);
                echo "<h3>Webhook Set</h3>";
                echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
                break;
                
            case 'delete_webhook':
                $result = $bot->deleteWebhook();
                echo "<h3>Webhook Deleted</h3>";
                echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
                break;
                
            case 'info':
                $result = $bot->apiRequest('getMe');
                echo "<h3>Bot Info</h3>";
                echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
                break;
        }
    } else {
        echo "<h3>Webhook Management:</h3>";
        echo "<a href='?action=set_webhook'>Set Webhook</a> | ";
        echo "<a href='?action=delete_webhook'>Delete Webhook</a> | ";
        echo "<a href='?action=info'>Bot Info</a>";
    }
}
?>
