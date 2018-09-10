<?php

/*

KEYBOARD TEMPLATE FOR FUTURE

$keyboard = [
           "one_time" => false,
           "buttons" => [[

               ["action" => [
                 "type" => "text",
                 "payload" => "{\"button\": \"1\"}",
                 "label" => "first button",
                ],
                "color" => "default",
                ],
               ],

               [["action" => [
                 "type" => "text",
                 "payload" => "{\"button\": \"2\"}",
                 "label" => "second button",
                ],
                "color" => "primary",
                ],
                ]

               ],
         ];  

*/

ini_set('date.timezone', 'Europe/Samara');

if (!isset($_REQUEST) && !isset($_POST['function'])) {
    return;
}

// load config

require 'config.php';

$config = new config();

//Строка для подтверждения адреса сервера из настроек Callback API
$confirmationToken = $config->vk_bot['confirmation'];
//Ключ доступа сообщества
$token = $config->vk_bot['token'];
// Secret key
$secretKey = $config->vk_bot['secret'];

global $db_host, $db_name, $db_username, $db_password, $user_id;

$db_host = $config->db['host'];
$db_name = $config->db['table'];
$db_username = $config->db['user'];
$db_password = $config->db['password'];

// small "API" for sending messages from interface

if ((isset($_GET['send'])) && isset($_GET['groupsCount'])){
     
     $ids = array();
     
    // получаем подписчиков
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Unable to connect, donut:" . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Unable to picky-pick database, swetie:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    
    // checking token for security

    $date = [date("Y-m-d H:i:s"), date("Y-m-d H:i:s", strtotime("-1 day"))];

    $sql = "SELECT * FROM vk_bot_cp_tokens WHERE token='" . $_POST['token'] . "' AND time < '{$date[0]}' AND time > '{$date[1]}';";

    $results = @mysql_query($sql)
        or die(mysql_error());

    $empty = true;


    while($data = mysql_fetch_array($results)){

        $empty = false;

    }

    if ($empty){
        
        die("@token_error"); // die if token wrong

    }
    
    // Делаем SQL
	$sql = "SELECT * FROM vk_bot_users WHERE (status='subscribed' OR status='subscribing')";

    if ($_GET['groupsCount'] > 0 && $_GET['groupsCount'] != "all"){
    
        $sql .= " AND ((";
        
        for ($g = 0; $g < $_GET['groupsCount']; $g++){

            $sql .= "studgroup='{$_GET['group-' . $g]}'";

            if ($g != $_GET['groupsCount'] - 1){
                
                $sql .= " OR ";
                
            }
            
        }
        
        $sql .= ")";
    
    }
    
    if($_GET['teachers'] == "true"){
        
        $sql .= " OR (teacher != 'NULL')";
        
    }
    
    if ($_GET['groupsCount'] != 'all')    
        $sql .= ")";
    
    if ($_GET['groupsCount'] == "zaoch"){
        $sql = "SELECT * FROM vk_bot_users WHERE (status='subscribed' OR status='subscribing') AND studgroup LIKE 'З%'";
    }
    
    if ($_GET['groupsCount'] == 'och'){
        $sql = "SELECT * FROM vk_bot_users WHERE (status='subscribed' OR status='subscribing') studgroup NOT LIKE 'З%'";
    }
    
//    $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='156152406'";
    
//    echo $sql;
    
    $results = mysql_query($sql)
        or die(mysql_error());
     
    $i = 0;
     
	while($data = mysql_fetch_array($results)){
        $ids[$i] = $data["VK_ID"];
        $i++;
    }

     for($i = 0; $i < count($ids); $i++){
     
     //затем с помощью users.get получаем данные об авторе
        $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$ids[$i]}&v=5.0&access_token=" . $token));
        //и извлекаем из ответа его имя
        $userInfo = $userInfo->response[0];
        
     if (isset($_POST['text']) && $_POST['text'] != ""){
         $message = str_replace(
             array( // what need to replace
                        "@USERNAME",
                        "@LASTNAME",
                      ), 
             array( // what place instead
                        $userInfo->first_name,
                        $userInfo->last_name,
                       ), $_POST['text']);
     }else{
        $message = "Привет, " . $userInfo->first_name . "! Новое расписание!";
     }
     //С помощью messages.send и токена сообщества отправляем ответное сообщение
        $request_params = array(
            'message' => "$message",
            'user_id' => $ids[$i],
            'access_token' => $token,
            'read_state' => 1,
            'v' => '5.0'
        );
        $get_params = http_build_query($request_params);

        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
     }
    echo 'ok';
    
}

if (isset($_GET['getInfo']) && isset($_POST['id']) && isset($_POST['token'])){
    
    $paramsArray = array(
        'token' => $_POST['token'],
    ); 
     // преобразуем массив в URL-кодированную строку
    $vars = http_build_query($paramsArray);
    // создаем параметры контекста
    $options = array(
        'http' => array(  
                    'method'  => 'POST',  // метод передачи данных
                    'header'  => 'Content-type: application/x-www-form-urlencoded',  // заголовок 
                    'content' => $vars,  // переменные
                )  
    );  
    $context  = stream_context_create($options);  // создаём контекст потока
    $result = file_get_contents('https://bot.zhrt.ru/functions.php?function=auth', false, $context); //отправляем запрос
    
    if ($result != 'true'){
        echo 'fail';
        return false;
    }
    
    $userInfo = file_get_contents("https://api.vk.com/method/users.get?user_ids={$_POST['id']}&fields=photo_400_orig&v=5.0&access_token=" . $token);
    
    echo $userInfo;
    
    return;
    
}

if (isset($_REQUEST) && !isset($_POST['function'])){    
    
//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));
// проверяем secretKey
if (strcmp($data->secret, $secretKey) !== 0 && strcmp($data->type, 'confirmation') !== 0)
    return;
//Проверяем, что находится в поле "type"
switch ($data->type) {
    //Если это уведомление для подтверждения адреса сервера...
    case 'confirmation':
        //...отправляем строку для подтверждения адреса
        echo $confirmationToken;
        break;
    //Если это уведомление о новом сообщении...
    case 'message_new':
        $chat_id = "false";
        
        if ($data->object->peer_id > 2000000000){
            
            $chat_id = $data->object->peer_id - 2000000000;
            
        }
        
        //...получаем id его автора
        $userId = $data->object->from_id;
        //затем с помощью users.get получаем данные об авторе
        $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$userId}&v=5.0&access_token=" . $token));
//        print_r($userInfo);
        
        //и извлекаем из ответа его имя
        $user_name = $userInfo->response[0]->first_name;
        
        // если впервые пишет
        global $db_host, $db_name, $db_username, $db_password, $user_id;

        $user_id = $userId;

        $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
        // Подключение к DB
        mysql_select_db($db_name, $connect_to_db)
        or die("Не могу выбрать базу данных:" . mysql_error());
        mysql_query("SET NAMES utf8");
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_known_users WHERE VK_ID='" . $userId . "';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
     
            $unknown_user = true;
        
            while($_data = mysql_fetch_array($results)){
            
            $unknown_user = false;
                
            }        
        
        // Читаем сообщение и записываем
        $message = $_message = str_replace(array("?", "!", ".", ","), "", mb_strtolower($data->object->text));
        
        if ($unknown_user){
            
        sendMessages($userId, getFirstMessage($user_name));
        
        sendAnalytics("firstUseMessage", $userId, $_message, $chat_id);
            
        $sql = "INSERT INTO vk_bot_known_users (VK_ID) VALUES ('" . $userId . "');";
        
        mysql_query($sql)
            or die(mysql_error());       
            
        }        
        // Анализируем и составляем ответ
		$attachment = $data->object->attachments[0]->type;
        
        $fwd_messages = $data->object->fwd_messages;
        
        
		if ($attachment){
            
            $answer = getAnswer("", "", $userId, $user_name, $attachment, $chat_id);

            }else if ($fwd_messages){

                $fwd = are_ownFwdMsg($fwd_messages, $userId);

                if (!$fwd){

                    $answer = getAnswer("", "", $userId, $user_name, "fwd_messages", $chat_id);

                }else if ($fwd == -1){

                    $fwd_messages = json_decode(json_encode($fwd_messages), true);

                    $message = $_message = str_replace(array("?", "!", ".", ","), "", mb_strtolower($fwd_messages[0]['text']));

                    $answer = getAnswer($message, $_message, $userId, $user_name, false, $chat_id);

                }else{

                    $fwd_messages = json_decode(json_encode($fwd_messages), true);

                    for ($i = 0; $i < $fwd; $i++){

                        $fwd_messages = $fwd_messages[0]['fwd_messages'];

                    }

                    $message = $_message = str_replace(array("?", "!", ".", ",", ")", "("), "", mb_strtolower($fwd_messages[0]['text']));

                    $answer = getAnswer($message, $_message, $userId, $user_name, false, $chat_id);

                }
        
        }else{
            
            $answer = getAnswer($message, $_message, $userId, $user_name, false, $chat_id);
            
		}
        
        
//         $keyboard = [
//           "one_time" => false,
//           "buttons" => [[
//
//               ["action" => [
//                 "type" => "text",
//                 "payload" => "{\"button\": \"1\"}",
//                 "label" => "first button",
//                ],
//                "color" => "default",
//                ],
//               ],
//
//               [["action" => [
//                 "type" => "text",
//                 "payload" => "{\"button\": \"2\"}",
//                 "label" => "second button",
//                ],
//                "color" => "primary",
//                ],
//                ]
//
//               ],
//         ];        

        if ($answer == "" || $answer == null)
            $answer = getAnswers($user_name)['default'][rand(0, count(getAnswers($user_name)))];
                
        //С помощью messages.send и токена сообщества отправляем ответное сообщение
        $request_params = array(
//            'message' => "{$user_name}, это неизвестная команда.",
            'message' => "{$answer}",
            'user_id' => $data->object->from_id,
            'random_id' => mt_rand(15, 200000),
            'access_token' => $token,
            'read_state' => 1,
//            'keyboard' => json_encode($keyboard),
            'v' => '5.84'
        );
        
        if ($chat_id != "false"){
            
            $request_params['chat_id'] = $chat_id;
            unset($request_params['user_id']);
               
        }
        
        $get_params = http_build_query($request_params);
                
        if ($answer != "@@@NOT_FOR_BOT_ERR")
            $result = file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
        
        if ($chat_id == "false")
        saveToLog($userId, $chat_id, $message, $answer, $result);
        
        //Возвращаем "ok" серверу Callback API
//        header("HTTP/1.1 200 OK");
        echo('ok');
        break;
        
    default:
        echo ('ok');
        header("HTTP/1.1 200 OK");
        break;
    }
    
}


function are_ownFwdMsg($fwd, $userId){
    
    $count = 0;
    
    $fwd = json_decode(json_encode($fwd), true);
        
    if ($fwd[0]['from_id'] == $userId && !$fwd[0]['fwd_messages']){
        
        return -1;
        
    }
    
    while ($fwd[0]['fwd_messages']){
        
        if ($fwd[0]['from_id'] != $userId){
            return false;
        }
        
        $fwd = $fwd[0]['fwd_messages'];
        
        $count++;
    }
    
//        echo $count . ' - '; print_r($fwd);
    
    if ($fwd[0]['from_id'] == $userId){
        return $count;
    }
    
    return false;
    
}

$analytics_sent = false;

function sendAnalytics($type, $VK_ID, $message, $is_chat){
    
    if ($VK_ID != '156152406'){ // dev id
    
        global $analytics_sent;

        if (!$analytics_sent){

            $sql = "INSERT INTO vk_bot_analytics (type, VK_ID, message, chat_id) VALUES ('" . $type . "', '" . $VK_ID . "', '" . $message . "', '" . $chat_id . "')";

            global $db_host, $db_name, $db_username, $db_password;

            $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) 
                or die("Не могу подключиться:" . mysql_error());
            // Подключение к DB
            mysql_select_db($db_name, $connect_to_db)
            or die("Не могу выбрать базу данных:" . mysql_error());
            mysql_query("SET NAMES utf8");

            mysql_query($sql)
                        or die(mysql_error());

           $analytics_sent = true;

        }
    }
    
}

function saveToLog($VK_ID, $chat_id, $message, $answer, $result){
    

            $sql = "INSERT INTO vk_bot_log (VK_ID, chat_id, message, answer, result) VALUES ('" . $VK_ID . "', '" . $chat_id . "', '" . $message . "', '" . $answer . "', '" . $result . "')";

            global $db_host, $db_name, $db_username, $db_password;

            $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) 
                or die("Не могу подключиться:" . mysql_error());
            // Подключение к DB
            mysql_select_db($db_name, $connect_to_db)
            or die("Не могу выбрать базу данных:" . mysql_error());
            mysql_query("SET NAMES utf8");

            mysql_query($sql)
                        or die(mysql_error());
    
}

function getWords(){
    
    $array = ["insult" => ["дурак",
                           "придурок",
                           "дебил",
                           "дегенерат",
                           "лох",
                           "лошара",
                           "идиот",
                           "соси",
                           "чмошник",
                           "паскуда",
                           "тварь",
                           "пидор",
                           "хуйло",
                           "ублюдок",
                           "еблан",
                           "хуй",
                           "нахуй",
                           "заебца",
                           "долбоеб",
                           "выблядок",
                           "падла",
                           "мразь",
                           "урод",
                           "щенок",
                           "шкура",
                           "чмо",
                           "пидр",
                           "тупой",
                           "глупый",
                           "сдохни",
                           "хуйло",
                          ],
              "badlang" => ["блять",
                           "сука",
                           "пидор",
                           "ебанный",
                           "ебаный",
                           "ёбанный",
                           "ёбаный",
                           "ебал",
                           "хуй",
                           "нахуй",
                           "похуй",
                           "пизда",
                           "уебу",
                           "выебу",
                           "заебал",
                           "жопа",
                           "пиздатый",
                           "пиздатая",
                           "пиздатое",
                           "пиздато",
                           "ёбу",
                           "ебу",
                           "ёбнулся",
                           "ебать",
                           "ебало",
                           "пох",
                           "уёбак",
                           "уёбок",
                           "отъебись",
                          ],
			  "yes" => ["да",
                           "ага",
                           "ясно",
                           "понятно",
                           "угу",
                           "окей",
                           "окау",
                           "окасик",
                           "ок",
                           "окай",
                           "агась",
                           "дыа",
                          ],
			  "no" => ["нет",
                           "не",
                           "неть",
                           "неа",
                          ],
              "hello" => ["привет",
                          "прив",
                          "приф",
                          "здрасте",
                          "дарова",
                          "здравствуй",
                          "здравствуйте",
                          "здарова",
                          "хай",
                          "ку",
                          "приветик",
                          "приветствую",
                             ],
              "goodbye" => ["пока",
                            "покеда",
                            "досвидания",
                            "досвиданья",
                            "прощай",
                            "бб",
                            "пака",
                            ],
			  "help" => ["помоги",
                            "помощь",
                            "помощ",
                            "памаги",
                            "помостч",
                            "!помощь",
                            "/помощь",
                            "/help",
                            "!help",
                            "help",
                            "команды",
                            "cправка",
                            "cправку",
                            "комманды",
                            "каманды",
                            "!команды",
                            ],
              "thanks" => ["спасибо",
                            "благодарю",
                            "спс",
                            "спасиб",
                            "спасип",
                            "посиба",
                            "пасиб",
                            "пасиба",
                            "пасипа",
                            "молодец",
                            "молдцом",
                            "красава",
                            "умничка",
                            "умница",
                            "красавчик",
                            "отлично",
                            "хорошо",
                             ],
              "groups" => getGroupsArray(),
              "subscribe" => ["подпиши",
                              "подписаться",
                              "запиши",
                              "подпишите",
                              "запишите",
                              "уведомления",
                             ],
              "unsubscribe" => ["отпиши",
                                "отписаться",
                             ],
              "chat" => [
                        "беседа",
                        "беседу",
                        "беседы",
                        "беседку",
                        "чат",
                        ],
			  "changeGroup" => ["смени",
                                "перезапиши",
                                "обнови",
                             ],
              "schedule" => ["расписание",
                             "расписсание",
                             "расписания",
                             "расписсания",
                             "пары",
                             "пара",
                             "пар",
                             "изменения",
                             "изменение",
                             "tomorrow" => ["завтра",
                                            "зафтра",
                                           ],
                             "yesterday" => ["вчера",
                                             "вчира",
                                            ],
							 "today" => ["сегодня",
                                             "сигодня",
                                             "сиводня",
                                             "сёдня",
                                            ],
                             "DAtomorrow" => [
                                             "послезавтра",
                                             ],
                             "monday" => ["понедельник"],
                             "friday" => ["пятница"],
                             "exams" => [
                                        "экзаменов",
                                        "экзамены",
                                        "экзамен",
                                        "экзаменав",
                                        "сессия",
                                        "сессии",
                                        ],
                            ],
              "bells" => [
                           "звонки",
                           "звонков",
                           "званки",
                           "званков",
                            ],
              
              "1sept" => [
                            "линейка",
                            ],
              
              "now" => [
                        "щас",
                        "сейчас",
                        "счас",
                        "щейщас",
                        "сийчас",
                        ],
              "teachers" => [
                            "subscribe" => [
                                             "учитель",
                                             "препод",
                                             "преподаватель",
                                            ],
                            "look" => [
                                        "препода",
                                        "преподавателя",
                                        "учителя",
                                      ],
                            "names" => getTeachersArray(),
                            ],
             ];
    
    return $array;
    
}

function getAnswers($userName){
    
    $array = ["insult" => ["Зачем ты обзываешься?",
                            "Какое плохое слово...",
                            "Мне не приятно это слышать...",
                            "А что, если я обижусь?",
                            "Меня это расстроило. Честно.",
                            "Не следует оскорблять меня. Впрочем, лучше вообще никого не оскорблять и жить в мире.",
                            "Мир, мир, мир. Я не собираюсь с тобой ссориться. И даже обижаться не буду. Я же бот.",
                            "Ну да, обзывай бота. Он же не знает оскорблений. Хотя знаю! Ты энергозависимый! Хм... Что есть, то есть.",
                            "Обзывать бота, который не может обозвать в ответ. Не очень то уж и благородно.",
                            "А я бы никогда тебя так не назвал!",
                            ],
                "badlang" => ["Фу! И ты этим ртом ешь?",
                            "Какое плохое слово...",
                            "Мне не приятно это слышать...",
                            "А что, если я все так будут говорить?",
                            "Меня это расстроило. Честно.",
                            "А какие хорошие слова ты знаешь? Я вот, например, знаю слова \"расписание\", \"звонки\" и \"подпиши\". Я же бот, сообщающий расписание, хах.",
                            "Эти твои плохие словечки - не круто, чтобы ты там себе не выдумывал.",
                            "Социализация - важный процесс. Ты вносишь деструктив в свою социализированность, подобными словами.",
                            "Был бы кто-то, кому можно было бы пожаловаться на твоё поведение... Сразу бы эти словечки отставил.",
                            "Пополняй свой лексикон более полезными словами, а эти лучше позабыть.",
                            "Истинный джентельмен не использует бранные слова в обычном диалоге. Ты истинный джентельмен?",
                            ],
                "hello" => ["Привет, {$userName}",
                            "И тебе привет, {$userName}",
                            "Здравствуй, {$userName}!",
                            "Доброе время суток, {$userName}!",
                            "Хорошая погода, {$userName}, верно?",
                            "Сегодня прекрасный день, не так ли, {$userName}?",
                            "И тебе хорошего дня. Хотя, в общем-то, каждый день, когда ты можешь принести кому-то пользу, хорош. Ты согласен с этим, {$userName}?",
                            "Не забываешь старину бота? Хе-хе... Здравствуй, {$userName}.",
                            "Рад тебя видеть, {$userName}. Чем могу помочь сегодня?",
                            "Приветик, {$userName}.",
                            "Здравствуй, приятель.",
                            ],
				"yes" => ["Ага.",
                            "Я тоже так думаю.",
                            "Угу.",
                            "Да",
                            "Ясно",
                            "Понятно",
                            "Точно?",
                            "Уверен?",
                            "Нельзя быть в чём-то уверенным на сто процентов. Развивай критическое мышление. Может нет?",
                            "Может нет?",
                            "Кто знает, может быть и нет.",
                            "Почему бы и нет?",
                            "А почему да?",
                            ],
				"no" => ["Почему нет?",
                            "Не говори нет, когда можно сказать \"да\"",
                            "А может да?",
                            "Может быть.",
                            "Кто знает... Может и нет.",
                            "Вполне возможно, что да.",
                            "Точно? Я бот, я не знаю. Но подумай, может да?",
                            "Я предпочитаю говорить да. Хотя может и нет.",
                            "Нет - это слово из трёх букв. А да - из двух. Сам решай, что лучше.",
                            "Хочешь нет - пусть будет нет.",
                            "Кто знает, может быть не нет?",
                            "Не нет.",
                            "Да.",
						 ],
				"changeGroup" => ["Если хочешь сменить группу, напиши что-нибудь вроде \"Смени мне группу на Д2ДО1\"",
                            "Я не знаю что.",
                            "Я могу сменить группу, на которую ты подписан, но ты должен написать и её тоже. Например: \"Обнови мне группу. Д2Т1\"",
                            ],
				"photo" => ["Я не понимаю, что изображено здесь, прости.",
                            "Ты ведь помнишь, что я бот? Если да, то зачем скидываешь мне картинки?",
                            "Картинка. А что на ней? Я не знаю.",
                            "Может, когда-нибудь пойму, что на этой картинке, но пока что - положу её в свой архив.",
                            "Не умею смотреть картинки... Надеюсь, когда-нибудь научат.",
                            ],
				"video" => ["Не могу посмотреть видео - плохой интернет. Шучу. Просто не умею.",
                            "Не хочу смотреть это видео - вдруг там скример? Шучу, просто нету возможности.",
                            "Так, запишу в блокнотик: научится смотреть видеозаписи.",
                            "Ну уж нет. Не буду смотреть. И не проси.",
                            "Я не умею смотреть видеозаписи. Я бот, помнишь?",
                            ],
				"audio" => ["Ооо! Сейчас послушаю... Ах да, я же бот.",
                            "Не могу послушать, извини.",
                            "Уже слышал. Не понравилось.",
                            "Да, классная песня. Наверное.",
                            "Напомню программисту, чтобы написал мне сознание.",
                            ],
				"link" => ["Я не буду переходить по этой ссылке, извини.",
                            "Услышал звук забивания свиньи. Испугался. Пора менять антивирус. По ссылке не перейду.",
                            "Не захожу по сомнительным ссылкам и тебе не советую.",
                            "Нет, спасибо.",
                            "Не перейду.",
                            "Никогда не буду переходить по ссылкам из Интернета. Так безопаснее.",
                            ],
				"wall" => ["Без малейшего понятия, о чём этот пост.",
                            "Не могу посмотреть. Ты должен понимать причины.",
                            "Это всё чтобы набрать классы! Ой... т.е. лайки. Чёрт, спалился.",
                            "Стены... Странное название для системы микроблогов. Не могу посмореть, в любом случае.",
                            "Интересно, Павел вернёт стенку? В любом случае, пока что не могу посмотреть о чём эта запись",
                            ],
				"sticker" => ["Классный стикер! Как такой же получить?",
                            "У меня что-то стикер не прогрузился...",
                            "И что этот стикер означает?",
                            "Это платный стикер или нет?",
                            "Кто-то слишком злоупотребляет стикерами?",
                            ],
				"doc" => ["Я не умею смотреть документы. Я их даже скачивать не умею...",
                            "Не загружается. Наверное, у меня файрвол не даёт скачать :(",
                            "Не умею с этим обращаться, прости",
                            "И снова напоминаю, что я бот. Я не умею просматривать документы.",
                            "Что это? Меня не учили, как с этим обращаться.",
                            ],
                "voice" => [
                            "Прости, не умею эти голосовые сообщения понимать. Может, когда-нибудь, Алиса меня научит.",
                            "Когда-нибудь Сири меня научит их понимать",
                            "Кортана всё ещё не может понимать русский голос. Я не настолько круче неё, чтобы начать понимать это раньше неё.",
                            ],
				"fwd_messages" => ["А человек, чьи сообщения ты пересылаешь, вкурсе об этом? Если нет, то это плохо - так нельзя.",
                            "Я не читаю чужие переписки, извини",
                            "У каждого человека есть право на тайну личной переписки. Интересно, а может ли это относится ко мне?",
                            "Без понятия, о чём идёт речь в этих сообщениях. Да и мне не особо интересно.",
                            "Ну, вот спасибо. Надеюсь, это не тайна. Я плохо храню тайны. Вернее, у меня просто нет такого навыка.",
                            ],
                "goodbye" => ["Пока, {$userName}!",
                            "До скорой встречи, {$userName}",
                            "До свидания!",
                            "Удачного дня!",
                            "Ещё спишемся! Я тут всегда!",
                            "Ну ты иди, а я тут посижу. Пиши, если что :)",
                            ],
                "thanks" => ["Всегда пожалуйста, {$userName}!",
                            "Ваш покорный слуга всегда здесь :)",
                            "Всегда пожалуйста :) Я создан, чтобы помогать!",
                            "Уууххх! Так приятно слышать такие слова :)",
                            "Всё для Вас, {$userName} :)",
                             ],
				"subscribed" => ["Вы успешно подписаны на уведомления о расписании! Чтобы отписаться, просто напишите мне что-то вроде \"Отпиши, пожалуйста!\".",
                            "{$userName}, теперь ты подписан на уведомления о расписании! Если захочешь отписаться - просто попроси :)",
                            "Подписал тебя, {$userName}. Теперь будешь получать сообщения от меня, сразу как расписание будет появляться. Кроме того, теперь можешь просто писать мне ключевое слово \"пары\"",
                             ],
                "schedule" => [
                        "new" => [
                            "Прошу просить и жаловать! Новое расписание!",  
                            "Новое расписание в студию!",  
                            "Братишки, я Вам новое расписание принёс!",  
                            "Новое расписание для Вас!",  
                            "А вот и оно! С пылу, с жару! Новое расписание!",  
                        ],
                            ],
                "default" => ["Я не понимаю тебя...",
                              "Я не знаю о чём ты... Попробуй переформулировать",
                              "Я бот. Я не понимаю что это означает... Никто несовершенен",
                              "Намекнул бы мне кто-нибудь о значении твоих слов...",
                              "Не знаю, как это понимать",
                              "Ну вот. Ещё одна вещь, которую мне не понять.",
                              "Мне тут не разобраться... Может что-то попроще?",
                              "О чём ты, {$userName}?",
                              "Ты же помнишь, что общаешься с ботом, {$userName}?",
                              "Может, попросишь что-нибудь из того, что я умею? Кстати, если спросишь помощи, то я с удовольствием расскажу.",
                              "Знаешь в чём различие робота и человека? Я не умею мыслить.",
                              "Ах, если бы боты могли закатывать глаза... Но нет. Так что давай перейдём к следующей теме.",
                              "Я всеми нейронами напрягаюсь, но не могу понять смысла этих слов",
                              "Когда-нибудь я пойму это. Надеюсь.",
                              "Скажу своему программисту, чтобы научил меня понимать эти слова, но до тех пор - прости, не понимаю",
                              "Не могу уловить смысл твоих слов, извини...",
                             ]
               ];
        
    return $array;
    
}

function tryParseDate($message, $src){
    
    $months = [
            "01" => [
                "январь",
                "января",
            ],
            "02" => [
                "февраль",
                "февраля",
            ],
            "03" => [
                "март",
                "марта",
            ],
            "04" => [
                "апрель",
                "апреля",
            ],
            "05" => [
                "май",
                "мая",
            ],
            "06" => [
                "июнь",
                "июня",
            ],
            "07" => [
                "июль",
                "июля",
            ],
            "08" => [
                "август",
                "августа",
            ],
            "09" => [
                "сентября",
                "синтября",
                "синтрября",
                "сентябрь",
            ],
            "10" => [
              "октябрь",  
              "октября",  
            ],
            "11" => [
              "ноябрь",  
              "ноября",  
            ],
            "12" => [
                "декабрь",
                "декабря",
            ],
        ];
    
    $day = NULL; $month = NULL;
        
    foreach ($months as $m){ // for each month of months. WTF I just commented...
        
        foreach ($m as $_m){ // for each variable of month
        
            if (in_array($_m, $message))
                $month = array_search($m, $months);

        }
    }
    
    $fmt = new NumberFormatter('ru_RU', NumberFormatter::DECIMAL);
    
    foreach ($message as $m){
        
        $result = numfmt_parse($fmt, $m, NumberFormatter::TYPE_INT32);
        
        if ($result != false && $result > 0 && $result < 32){
            
            if ($result < 10)
                $result = 0 . $result;
            
            $day = $result;
            break;
            
        }        
    }
    
    if (isset($day) && isset($month)){
        // shows how function works;
//        echo date("Y") . '-' . $month . '-' . $day; 
        return date("Y") . '-' . $month . '-' . $day;
    }
    
    return $src;
    
}

function getAnswer($message, $_message, $userId, $userName, $is_attachment){
    
    global $db_host, $db_name, $db_username, $db_password, $chat_id;
    
    $Answers = getAnswers($userName);
    
    $Words = getWords();
    
//    print_r($Words["teachers"]);
    
    $message = explode(" ", $message, 50);
    
    $is_to_bot = false;
        
    if ($chat_id != "false"){
        
        foreach ($message as $m){

            if (mb_strtolower($m) == "бот" || strpos($m, "club155349353") || strpos($m, "public167772805")){
                $is_to_bot = true;
                break;
            }

        }
        
    }
    
    if (!$is_to_bot && $chat_id != "false")
        return "@@@NOT_FOR_BOT_ERR";
    
    $scheduleDate = "today";
	
	if ($is_attachment){
		
		switch ($is_attachment){
                
			case "photo":
                sendAnalytics("photoMessage", $userId, '@photo', $chat_id);
				return $Answers["photo"][rand(0, count($Answers["photo"]))];
				break;
			case "video":
                sendAnalytics("videoMessage", $userId, '@video', $chat_id);
				return $Answers["video"][rand(0, count($Answers["video"]))];
				break;
			case "audio":
                sendAnalytics("audioMessage", $userId, '@audio', $chat_id);
				return $Answers["audio"][rand(0, count($Answers["audio"]))];
				break;
			case "doc":
                sendAnalytics("docMessage", $userId, '@doc', $chat_id);
				return $Answers["doc"][rand(0, count($Answers["doc"]))];
				break;
			case "sticker":
                sendAnalytics("stickerMessage", $userId, '@sticker', $chat_id);
				return $Answers["sticker"][rand(0, count($Answers["sticker"]))];
				break;
			case "fwd_messages":
                sendAnalytics("fwd_messagesMessage", $userId, '@fwd_messages', $chat_id);
				return $Answers["fwd_messages"][rand(0, count($Answers["fwd_messages"]))];
				break;
            case "link":
                sendAnalytics("linkMessage", $userId, '@link', $chat_id);
                return $Answers["link"][rand(0, count($Answers["link"]))];
            case "wall":
                sendAnalytics("wallMessage", $userId, '@wall', $chat_id);
                return $Answers["wall"][rand(0, count($Answers["wall"]))];
            case "audio_message":
                sendAnalytics("voiceMessage", $userId, '@wall', $chat_id);
                return $Answers["voiceMessage"][rand(0, count($Answers["voice"]))];
			default:
                sendAnalytics("unknownAttach", $userId, '@attachment', $chat_id);
				return $is_attachment;
				break;
                
		}
		
	}else{
	
    
    $marker = 0;
		
	$stopSearching = false;
    
    while ($marker < count($message) && !$answerType){
        
        $feedbackSearch = explode(":", $_message);
        
		if ($_message == "забудь меня полностью"){
			$answerType = "forgetUser";
            sendAnalytics("forgetFunction", $userId, $_message, $chat_id);
            
        }else if ($feedbackSearch[0] == "отправь разработчику"){
            
            $answerType = "feedback";
            sendAnalytics("feedbackMessage", $userId, $_message, $chat_id);
			
		}else if (in_array($message[$marker], $Words["insult"])){
            $answerType = "insult";	
            sendAnalytics("insultMessage", $userId, $_message, $chat_id);	
        
//        }else if (in_array($message[$marker], $Words["1sept"]) && ((date("n") < 10) && (date("n") > 6))){
//            $answerType = "1sept";	
//            sendAnalytics("1septMessage", $userId, $_message);	
            
		}else if (in_array($message[$marker], $Words["badlang"])){
            $answerType = "badlang";	
            sendAnalytics("badlangMessage", $userId, $_message, $chat_id);
        
        }else if (in_array($message[$marker], $Words["bells"])){
            $answerType = "bells";	
            sendAnalytics("bellsMessage", $userId, $_message, $chat_id);
            
		}else if (in_array($message[$marker], $Words["schedule"]["tomorrow"])){
            $answerType = "schedule";
            $scheduleDate = "tomorrow";
            sendAnalytics("scheduleTomorrow", $userId, $_message, $chat_id);
        
        }else if (in_array($message[$marker], $Words["schedule"]["DAtomorrow"])){
            $answerType = "schedule";
            $scheduleDate = "DAtomorrow";
            $group = is_group($message, $Words["groups"]);
            sendAnalytics("scheduleDATomorrow", $userId, $_message, $chat_id);
            
		}else if (in_array($message[$marker], $Words["schedule"]["yesterday"])){
            $answerType = "schedule";
            $scheduleDate = "yesterday";
            sendAnalytics("scheduleYesterday", $userId, $_message, $chat_id);
            
		}else if (in_array($message[$marker], $Words["schedule"]["today"])){
            $answerType = "schedule";
            $scheduleDate = "today";
            sendAnalytics("scheduleToday", $userId, $_message, $chat_id);
            
		}else if (in_array($message[$marker], $Words["schedule"]["monday"])){
            $answerType = "schedule";
            $scheduleDate = "monday";
            sendAnalytics("scheduleMonday", $userId, $_message, $chat_id);
            
		}else if (in_array($message[$marker], $Words["schedule"]["friday"])){
            $answerType = "schedule";
            $scheduleDate = "friday";
            sendAnalytics("scheduleFriday", $userId, $_message, $chat_id);
            
        }else if (in_array($message[$marker], $Words["groups"])){
            $answerType = "group";
            $group = $message[$marker];
			$stopSearching = true;
            sendAnalytics("scheduleByGroup", $userId, $_message, $chat_id);
        
        }else if (in_array($message[$marker], $Words["teachers"]["names"]["lastnames"])){
            $answerType = "teacher";
            $teacher = $message[$marker];
			$stopSearching = true;
            sendAnalytics("scheduleByTeacher", $userId, $_message, $chat_id);
            
         }else if (in_array($message[$marker], $Words["schedule"]["exams"])){
                            
                            $group = false;
                            
                            for ($_i = 0; $_i < count($message); $_i++){
                            
                                if (in_array($message[$_i], $Words["groups"])){

                                    $group = $message[$_i];

                                }else if (in_array($message[$_i], $Words["teachers"]["names"]["lastnames"])){

                                    $teacher = $message[$_i];

                                }else if (in_array($message[$_i], $Words["teachers"]["names"]["r_case_lastnames"])){
                                
                                    for ($z = 0; $z < count($Words["teachers"]["names"]["r_case_lastnames"]); $z++){

                                        if ($Words["teachers"]["names"]["r_case_lastnames"][$z] == $message[$_i]){

                                            $teacher = $Words["teachers"]["names"]["lastnames"][$z];

                                        }

                                    }
                                    
                                }
                                
                                
                            }
                            
                            if (!$group && !$teacher){
                                
                                $group = subscribe("getGroup", $userId, "empty");
                                
                                if (!$group){
                                    
                                 $teacher = explode(" ", subscribe("getTeacher", $userId, "empty"));
                                 $teacher = mb_strtolower($teacher[0]);
                                    
                                }
                            }
                            
                            if ($group){
                                
                                sendAnalytics("scheduleExams", $userId, $_message, $chat_id);
                                return getExams($group, false);
                                
                                }else if ($teacher){
                                
                                sendAnalytics("scheduleExams", $userId, $_message, $chat_id);
                                return getExams(false, $teacher);

                                }else{

                                return 'Я не знаю Вашу группу. Вы даже не указали её в сообщении. И Вы просите стать меня Вашим другом?';
                                
                            }
        
        }else if (!$stopSearching && in_array($message[$marker], $Words["schedule"])){
            
            for ($_i = 0; $_i < count($message); $_i++){
            
                    if ((in_array($message[$_i], $Words["teachers"]["look"])) || 
                        (in_array($message[$_i], $Words["teachers"]["names"]["lastnames"])) ||
                        (in_array($message[$_i], $Words["teachers"]["names"]["r_case_lastnames"])))
                        
                    {
            
                        $answerType = "teacher_look";
                        $stopSearching = true;
                        sendAnalytics("scheduleByTeacher", $userId, $_message, $chat_id);
                
                    }
                
                    if (in_array($message[$_i], $Words["bells"]) || in_array($message[$_i], $Words["now"])){
                        
                        sendAnalytics("bellsMessage", $userId, $_message, $chat_id);
                        $answerType = "bells";
                        $stopSearching = true;
                        sendAnalytics("scheduleBells", $userId, $_message, $chat_id);
                    }
                                
                }
                
                if (!$answerType){
                    
                $answerType = "schedule";

                $scheduleDate = "today";

                for($i = 0; $i < count($message); $i++){
                    
                        if (in_array($message[$i], $Words["schedule"]["tomorrow"])){
                            $scheduleDate = "tomorrow";
                            sendAnalytics("scheduleTomorrow", $userId, $_message, $chat_id);
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["DAtomorrow"])){
                            $scheduleDate = "DAtomorrow";
                            sendAnalytics("scheduleDATomorrow", $userId, $_message, $chat_id);
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["yesterday"])){
                            $scheduleDate = "yesterday";
                            sendAnalytics("scheduleYesterday", $userId, $_message, $chat_id);
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["today"])){
                            $scheduleDate = "today";
                            sendAnalytics("scheduleToday", $userId, $_message, $chat_id);
                            $wordToday = true;
                            break;
                        }else if (in_array($message[$i], array("понедельник"))){
                            $scheduleDate = "monday";
                            sendAnalytics("scheduleMonday", $userId, $_message, $chat_id);
                        }else if (in_array($message[$i], array("пятница", "пятницу"))){
                            $scheduleDate = "friday";
                            sendAnalytics("scheduleFriday", $userId, $_message, $chat_id);
                        }else if (in_array($message[$i], $Words["schedule"]["exams"])){
                                                        
                            $group = false;
                            
                            for ($_i = 0; $_i < count($message); $_i++){
                            
                                if (in_array($message[$_i], $Words["groups"])){

                                    $group = $message[$_i];

                                }else if (in_array($message[$_i], $Words["teachers"]["names"]["lastnames"])){

                                    $teacher = $Words["teachers"]["names"]["r_case"][getTeacher($teacher, $Words["teachers"]["names"]["fullnames"], false)];

                                }  
                                
                            }
                            
                            if (!$group && !$teacher){
                                
                                $group = subscribe("getGroup", $userId, "empty");
                                
                                if (!group){
                                    

                                    
                                    
                                }
                                
                            }
                            
                            if ($group){
                                
                                sendAnalytics("scheduleExams", $userId, $_message, $chat_id);
                                return getExams($group, false);
                                
                            }else if ($teacher){
                            
                                sendAnalytics("scheduleExams", $userId, $_message, $chat_id);
                                return getExams(false, $teacher);
                                
                                }else{
                                
                                return 'Я не знаю Вашу группу. Вы даже не указали её в сообщении. И Вы просите стать меня Вашим другом?';
                                
                            }
                        }

                        if (in_array($message[$i], $Words["groups"])){

                            $answerType = "group";
                            $group = $message[$i];
                               
                            
//                            print_r($Answers);     
                            sendAnalytics("scheduleByGroup", $userId, $_message, $chat_id);

                        }

                }
                    
                $scheduleDate = tryParseDate($message, $scheduleDate);
                    
                sendAnalytics("schedule", $userId, $_message, $chat_id);

                if (($scheduleDate == "today" && !$wordToday) && (checkSchedule("tomorrow", null) && date("G") > 14)){

                    if (date("N") > 4 && file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . date("Y-m-d", strtotime("+1 day"))) == "false"){
                        
                        $scheduleDate = "monday";
                        
                    }else {
                    
                        $scheduleDate = "tomorrow";    
                        
                    }
                }
            }
		            
        }else if (in_array($message[$marker], $Words["hello"])){
            $answerType = "hello";
            sendAnalytics("helloMessage", $userId, $_message, $chat_id);
            
		}else if (in_array($message[$marker], $Words["teachers"]["look"])){
            $answerType = "teacher_look";
            sendAnalytics("scheduleByTeacher", $userId, $_message, $chat_id);
		
		}else if (in_array($message[$marker], $Words["help"])){
            $answerType = "help";
            sendAnalytics("helpMessage", $userId, $_message, $chat_id);
            
        }else if (in_array($message[$marker], $Words["goodbye"])){
            $answerType = "goodbye"; 
            sendAnalytics("goodbyeMessage", $userId, $_message, $chat_id);
            
        }else if (in_array($message[$marker], $Words["thanks"])){
            $answerType = "thanks";
            sendAnalytics("thanksMessage", $userId, $_message, $chat_id);
            
        }else if (in_array($message[$marker], $Words["subscribe"])){
            $answerType = "subscribe";
			
			for($i = 0; $i < count($message); $i++){
				
                if (in_array($message[$i], $Words["groups"])){
                    $group = $message[$i];
                    $i = count($message);
                    sendAnalytics("subscribeGroup", $userId, $_message, $chat_id);
                    }

                if (in_array($message[$i], $Words["teachers"]["names"]["lastnames"])){
                    $teacher = $message[$i];
                    $i = count($message);            
                    sendAnalytics("subscribeTeacher", $userId, $_message, $chat_id);

                }
                
                if (in_array($message[$i], $Words["chat"]) && $chat_id != "false") {
                    $answerType = "chatSubscribe";
                    sendAnalytics("chatSubscribe", $userId, $_message, $chat_id);
                }
                
            }
            
        }else if (in_array($message[$marker], $Words["unsubscribe"])){
            
            if ($chat_id != "false"){
            
                for ($i = 0; $i < count($message); $i++){
                    
                    if(in_array($message[$i], $Words["chat"])){

                        $answerType = "chatUnsubscribe";
                        sendAnalytics("chatUnsubscribe", $userId, $_message, $chat_id);

                    }else{

                        $answerType = "unsubscribe";
                        sendAnalytics("unsubscribe", $userId, $_message, $chat_id);

                    }
                    
                }

            }else{
                $answerType = "unsubscribe";
                sendAnalytics("unsubscribe", $userId, $_message, $chat_id);

            }
            
//            
//        }else if (in_array($message[$marker], $Words["changeGroup"])){
//            $answerType = "changeGroup";
			
        }else if (in_array($message[$marker], $Words["yes"])){
            $answerType = "yes";
            sendAnalytics("yesMessage", $userId, $_message, $chat_id);
		
		}else if (in_array($message[$marker], $Words["no"])){
            $answerType = "no";
            sendAnalytics("noMessage", $userId, $_message, $chat_id);
		
		}
        
        $marker++;
    }
    
    switch ($answerType){
        case "insult":
            return $Answers["insult"][rand(0, count($Answers["insult"]))];
        break;
        
        case "badlang":
            return $Answers["badlang"][rand(0, count($Answers["badlang"]))];
        break;
//        
//        case "1sept":
//            return "&#128276; Линейка пройдёт 03.09.2018 г. у корпуса \"А\", на улице Мира, 22. Начало в &#8986; 8:30!";
//        break;
			
        case "hello":
            return $Answers["hello"][rand(0, count($Answers["hello"]))];
        break;
			
        case "goodbye":
            return $Answers["goodbye"][rand(0, count($Answers["goodbye"]))];
        break;
			
        case "yes":
            return $Answers["yes"][rand(0, count($Answers["yes"]))];
        break;
			
        case "no":
            return $Answers["no"][rand(0, count($Answers["no"]))];
        break;
			
        case "thanks":
            return $Answers["thanks"][rand(0, count($Answers["thanks"]))];
        break;
			
        case "forgetUser":
            return subscribe("fullDelete", $userId, "");
        break;
		
		case "help":
            sendMessages($userId, getHelpMessage($userName));
            return "Читай внимательно!";
        break;
            
        case "bells":
            
            return getBells();
            
        break;
            
        case "teacher_look":
        case "teacher":
            
            $teacher = false;
                                            
                for($i = 0; $i < count($message); $i++){
                
                    if (in_array($message[$i], $Words["schedule"]["tomorrow"])){
                            $scheduleDate = "tomorrow";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["DAtomorrow"])){
                            $scheduleDate = "DAtomorrow";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["yesterday"])){
                            $scheduleDate = "yesterday";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["today"])){
                            $scheduleDate = "today";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], array("понедельник"))){
                            $scheduleDate = "monday";
                            $choosedDay = true;
                        }else if (in_array($message[$i], array("пятница", "пятницу"))){
                            $scheduleDate = "friday";
                            $choosedDay = true;
                        }
                    
                    if (in_array($message[$i], $Words["teachers"]["names"]["lastnames"])) {
                        
                        $teacher = $Words["teachers"]["names"]["fullnames"][getTeacher($message[$i], $Words["teachers"]["names"]["fullnames"], false)];
                        $teacher_rcase = $Words["teachers"]["names"]["r_case"][getTeacher($message[$i], $Words["teachers"]["names"]["fullnames"], true)];
                                                
                    }else 
                    
                    if (in_array($message[$i], $Words["teachers"]["names"]["r_case_lastnames"])) {
                        
                        for ($z = 0; $z < count($Words["teachers"]["names"]["r_case_lastnames"]); $z++){
                            
                            if ($Words["teachers"]["names"]["r_case_lastnames"][$z] == $message[$i]){
                                
                                $teacher = $Words["teachers"]["names"]["fullnames"][$z];
                                $teacher_rcase = $Words["teachers"]["names"]["r_case"][$z];
                                
                            }
                            
                        }
                        
                    }
                             
                }
            
                
                    if (!$choosedDay && date("G") > 17){
                        
                        $scheduleDate = 'tomorrow';
                        
                    }
            
                    if (!$teacher){
                        
                        return 'Вы не указали фамилию';
                        
                    }
                    
                    return getScheduleTeacher($scheduleDate, $teacher, $teacher_rcase);
            
            break;
			
        case "schedule":
          
        if (!$group && !$teacher){
                            
            for ($_i = 0; $_i < count($message); $_i++){

                if (in_array($message[$_i], $Words["groups"])){

                    $group = $message[$_i];

                }else if (in_array($message[$_i], $Words["teachers"]["names"]["lastnames"])){

                    $teacher = $Words["teachers"]["names"]["fullnames"][getTeacher($message[$_i], $Words["teachers"]["names"]["lastnames"], false)];

                }else if (in_array($message[$_i], $Words["teachers"]["names"]["r_case_lastnames"])){

                    for ($z = 0; $z < count($Words["teachers"]["names"]["r_case_lastnames"]); $z++){

                        if ($Words["teachers"]["names"]["r_case_lastnames"][$z] == $message[$_i]){

                            $teacher = $Words["teachers"]["names"]["lastnames"][$z];

                        }
                    }
                }   
            }
        }
            
        if (!$group){
            
            $group = subscribe("getGroup", $userId, "empty");
                        
        }
            
        if (!$teacher){
            
            $teacher = subscribe("getTeacher", $userId, "empty");
        
        }
            
        if ($group || $teacher){
            
            if ($group){
                
                return getSchedule($group, $scheduleDate, false);
            
            }else if ($teacher){
                
                $rcase = explode(" ", $teacher); 
                $rcase = mb_strtolower($rcase[0]);
                
                $rcase = $Words["teachers"]["names"]["r_case"][getTeacher($rcase, $Words["teachers"]["names"]["fullnames"], true)];
                
                for($i = 0; $i < count($message); $i++){
                
                    if (in_array($message[$i], $Words["schedule"]["tomorrow"])){
                            $scheduleDate = "tomorrow";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["DAtomorrow"])){
                            $scheduleDate = "DAtomorrow";
                            $choosedDay = true;
                            break;
                    }else if (in_array($message[$i], $Words["schedule"]["yesterday"])){
                            $scheduleDate = "yesterday";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["today"])){
                            $scheduleDate = "today";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], array("понедельник"))){
                            $scheduleDate = "monday";
                            $choosedDay = true;
                        }else if (in_array($message[$i], array("пятница", "пятницу"))){
                            $scheduleDate = "friday";
                            $choosedDay = true;
                        }
                    
                    }
                
                    if (!$choosedDay && date("G") > 14){

                    $scheduleDate = 'tomorrow';

                    }
                
                    return getScheduleTeacher($scheduleDate, $teacher, $rcase);

            }
        }else{
			sendAnalytics("scheduleFail", $userId, $_message, $chat_id);
            return "Укажите группу в сообщении, либо попросите меня записать в базу Вашу фамилию, если Вы преподаватель, либо причастность к какой-либо группе, если Вы студент. Например: \"Запиши меня в Д1Т2\" или \"Запиши меня. Я преподаватель Иванов\"";
			
        }
            
            
        break;
			
        case "subscribe":
            if (subscribe("check", $userId, "сладкий хлеб") && !(subscribe("checkSubscribing", $userId, "Богато мух у нас тут"))){
                if (subscribe("checkGroup", $userId, "Сладкий Хлеб") || subscribe("checkTeacher", $userId, "Сладкий хлеб")){
                    if (subscribe("checkUnsubscribed", $userId, "Сладкий Хлеб")){
                        return subscribe("subscribe", $userId, "");
                        
                    }else{
                        
                    return "Вы уже подписаны!";
                    
                    }
                    
                }else{
                    
                    return "У Вас не указана группа... Напишите мне её? Если Вы преподаватель, просто напишите Вашу фамилию. (примеры: Д1Т1; Иванов)";
                
                }
                
            }else{
				
				if ($group || $teacher){
                    
                    if ($group){
                    
					   return subscribe("subscribeWithGroup", $userId, $group);
                        
                    }else
                        
                    if ($teacher){
                        
                        $teacher = $Words["teachers"]["names"]["fullnames"][getTeacher($teacher, $Words["teachers"]["names"]["fullnames"], false)];
                        return subscribe("subscribeWithTeacher", $userId, $teacher);
                        
                    }                    
                    
                    
				}else{
					return subscribe("subscribeWithoutGroup", $userId, "сладкий хлеб");
            	}
			}
            
        break;
            
        case 'chatSubscribe':            
            
            $group = null;
            
            foreach ($message as $m){
                if (in_array($m, $Words['groups']))
                    $group = mb_strtoupper($m);
            }
            
//            if (is_chatadmin($userId, $chat_id))
                return chatSubscribe($chat_id, 1, $group);
			break;
        case 'chatUnsubscribe':
            
            $group = null;
            
            foreach ($message as $m){
                if (in_array($m, $Words['groups']))
                    $group = mb_strtoupper($m);
            }
            
//            if(is_chatadmin($userId, $chat_id))
                return chatSubscribe($chat_id, 0, $group);
            break;
        case "changeGroup":
            return $Answers["changeGroup"][rand(0, count($Answers["changeGroup"]))];
        break;
			
        case "group":
            if (subscribe("checkSubscribing", $userId, "Сладкий хлеб")){
                
                return subscribe("subscribeAddGroup", $userId, $group);
                
            }else{
			
				$i = 0;
				while ($i < count($message) && !$changeGroup){
					if(in_array($message[$i], $Words["changeGroup"])){
						$changeGroup = true;
						return subscribe("changeGroup", $userId, $group);
					}
					$i++;
				}
                
                $scheduleDate = "today";
                $choosedDay = false;
                
                for($i = 0; $i < count($message); $i++){
                
                    if (in_array($message[$i], $Words["schedule"]["tomorrow"])){
                            $scheduleDate = "tomorrow";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["DAtomorrow"])){
                            $scheduleDate = "DAtomorrow";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["yesterday"])){
                            $scheduleDate = "yesterday";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], $Words["schedule"]["today"])){
                            $scheduleDate = "today";
                            $choosedDay = true;
                            break;
                        }else if (in_array($message[$i], array("понедельник"))){
                            $scheduleDate = "monday";
                            $choosedDay = true;
                        }else if (in_array($message[$i], array("пятница", "пятницу"))){
                            $scheduleDate = "friday";
                            $choosedDay = true;
                        }
                    
                }
                
                if (($scheduleDate == "today" && !$choosedDay) && (checkSchedule("tomorrow", null))){
                                        
                    if (date("N") > 4 && file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . date("Y-m-d", strtotime("+1 day"))) == "false"){
                        
                        $scheduleDate = "monday";
                        
                    }else{
                        
                        if (date("G") > 14)
                        $scheduleDate = "tomorrow";    
                        
                    }
                }
                
                    if (!$choosedDay && date("G") > 14 && $scheduleDate != "monday"){
                        
                        $scheduleDate = 'tomorrow';
                        
                    }
                
                
                    return getSchedule($group, $scheduleDate, false);
                
            };
        break;
        
        case "teacher":
            if (subscribe("checkSubscribing", $userId, "Сладкий хлеб")){
                
                $teacher = $Words["teachers"]["names"]["fullnames"][getTeacher($teacher, $Words["teachers"]["names"]["fullnames"], false)];
                
                return subscribe("subscribeAddTeacher", $userId, $teacher);
                
            }else{
			
				$i = 0;
				while ($i < count($message) && !$changeGroup){
					if(in_array($message[$i], $Words["changeGroup"])){
						$changeGroup = true;
                        
                        $teacher = $Words["teachers"]["names"]["fullnames"][getTeacher($teacher, $Words["teachers"]["names"]["fullnames"], false)];
                        
						return subscribe("changeTeacher", $userId, $teacher);
					}
					$i++;
				};
            }
        break;
            
        case "unsubscribe":
            if (subscribe("check", $userId, "сладкий хлеб")){
                return subscribe("unsubscribe", $userId, "сладкий хлеб");
            }else{
                return "Вы итак не подписаны! Как же я могу Вас отписать, если Вы ещё не подписаны?";
            }
        break;
            
        case "feedback":
            
            $sql = "INSERT INTO vk_bot_feedback (message, VK_ID) VALUES ('" . $feedbackSearch[1] . "', '" . $userId . "');";
            
            $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
            // Подключение к DB
            mysql_select_db($db_name, $connect_to_db)
            or die("Не могу выбрать базу данных:" . mysql_error());
            mysql_query("SET NAMES utf8");
            
            mysql_query($sql)
                or die(mysql_error());
			
			//С помощью messages.send и токена сообщества отправляем разработчику сообщение
            
            global $token;
            
        	$_request_params = array(
            'message' => "Сообщение от {$userName} [[id{$userId}|{$userId}]]: " . $feedbackSearch[1],
            'user_id' => 156152406,
            'access_token' => $token,
            'v' => '5.0'
									);
			
			$_get_params = http_build_query($_request_params);
			file_get_contents('https://api.vk.com/method/messages.send?' . $_get_params);
			
            return "Я передам это разработчику.";
            break;
			
        default:
            addUnknownWord($_message);
            sendAnalytics("unknownMessage", $userId, $_message, $chat_id);
            return $Answers["default"][rand(0, count($Answers["default"]))];;
        break;
    }
  }
    
    global $token;

    $_request_params = array(
    'message' => "Сообщение от {$userName} [[id{$userId}|{$userId}]] ввело меня в ступор:  " . $_message,
    'user_id' => 156152406,
    'access_token' => $token,
    'v' => '5.0'
    );

    $_get_params = http_build_query($_request_params);
    file_get_contents('https://api.vk.com/method/messages.send?' . $_get_params);
    
    return $Answers["default"][rand(0, count($Answers["default"]))];
    
}

function is_chatadmin($userId, $chat_id){
    
    global $token;
    
    $chatInfo = json_decode(file_get_contents("https://api.vk.com/method/messages.getChat?chat_id={$chat_id}&v=5.84&access_token=" . $token));
    
//    print_r($chatInfo);
    
    if ($userId == $chatInfo->response[0]->admin_id)
        return true;
    
    return false;
    
}

function is_group($message, $groups){
    
    for ($i = 0; $i < count($message); $i++){
    
        if (in_array($message[$i], $groups)){

            return $message[$i];

        }
        
    }
    
    return false;
    
}

function chatSubscribe($chat_id, $state, $group){
    
    global $db_host, $db_username, $db_password, $db_name;
    
    $already_in_db = false;
    
    if ($state == 0)
        $state = "false";
    
    if ($state == 1)
        $state = "true";
    
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
    
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    $sql = "SELECT chat_id FROM vk_bot_chats WHERE chat_id = '{$chat_id}'";
    
    $results = mysql_query($sql)
                or die(mysql_error());

    while($data = mysql_fetch_array($results)){
        $already_in_db = true;
    }
    
    if ($already_in_db)
        $sql = "UPDATE vk_bot_chats SET status = '{$state}', studgroup = '{$group}' WHERE chat_id = '{$chat_id}'";
    
    if (!$already_in_db)
        $sql = "INSERT INTO vk_bot_chats (chat_id, status, studgroup) VALUES 
        ('{$chat_id}', '{$state}', '{$group}')";
    
    mysql_query($sql)
        or die(mysql_error());
    
    if ($state == "false")
    return 'Я успешно отписал Вашу беседу от уведомлений.';
    
    if ($state == "true")
    return 'Я успешно подписал Вашу беседу на уведомления.';
    
}

function getExams($group, $teacher){
    
    if (!$teacher){

        $response = file_get_contents("https://schedule.zhrt.ru/api.php?group=" . $group . "&exam=1");

        $response = json_decode($response, true);

        if ($response['type'] == 'exams'){

            $message = "Экзамены группы " . mb_strtoupper($group) . ": \n";

            $point = "&#128280;";

            for ($i = 0; $i < $response['count']; $i++){

                $type = $response['exam' . $i]['type'];

                $response['exam' . $i]['date'] = date('d.m.Y', strtotime($response['exam' . $i]['date']));

                switch ($type){

                    case 'consult':
                        $type = 'Консультация';
                        break;
                    case 'exam':
                        $type = 'Экзамен';
                        break;

                    default:
                        break;

                }

                $message .= "\n{$point}" . $type . " / " . $response['exam' . $i]['name'] . " / " . $response['exam' . $i]['teacher'];
                $message .= "\n" . $response['exam' . $i]['cabinet'] . " / " . $response['exam' . $i]['date'] . " / " . $response['exam' . $i]['time'];
                $message .= "\n";
            }


        }else{

            $message = "У меня нету расписания экзаменов для группы {$group}, извини. ";

        }
    
    }else{
        
        $teachers = getTeachersArray();
        
        $response = file_get_contents("https://schedule.zhrt.ru/api.php?teacher=" . $teacher . "&exam=1");

        $response = json_decode($response, true);
                
        $teacher = $teachers["r_case"][getTeacher(ucfirst($teacher), $teachers["fullnames"], true)];

        if ($response['type'] == 'exams'){

            $message = "Экзамены у " . $teacher . ": \n";

            $point = "&#128280;";

            for ($i = 0; $i < $response['count']; $i++){

                $type = $response['exam' . $i]['type'];

                $response['exam' . $i]['date'] = date('d.m.Y', strtotime($response['exam' . $i]['date']));

                switch ($type){

                    case 'consult':
                        $type = 'Консультация';
                        break;
                    case 'exam':
                        $type = 'Экзамен';
                        break;

                    default:
                        break;

                }

                $message .= "\n{$point}" . $type . " / " . $response['exam' . $i]['studgroup'] . " / " . $response['exam' . $i]['name'];
                $message .= "\n" . $response['exam' . $i]['cabinet'] . " / " . $response['exam' . $i]['date'] . " / " . $response['exam' . $i]['teacher'] . " / " . $response['exam' . $i]['time'];
                $message .= "\n";
            }


        }else{

            $message = "У меня нету расписания экзаменов для {$teacher}, извини. ";

        }
        
    }
    return $message;
    
}

function getBells(){
    
    $dateStr = date("H:i");
    $date = date("Hi");
    
    $bells = [
        "first" => [830, 1005],
        "second" => [1015, 1150],
        "break" => [1150, 1230],
        "third" => [1230, 1405],
        "fourth" => [1415, 1550],
        "fifth" => [1600, 1735],
        
    ];
    
    $str = ["&#128280;", "&#128280;", "&#128280;", "&#128280;", "&#128280;", ];
    
    $pointer = "&#10071;";
    
    if ($date < $bells["first"][0] || $date > $bells["fifth"][1]){
        
        $dayend = true;
        
    }
    
    if ($date >= $bells["first"][0] && $date <= $bells["first"][1]){
        
        $str[0] = $pointer;
        $curr = 1;
        
    }
    
    if ($date >= $bells["second"][0] && $date <= $bells["second"][1]){
        
        $str[1] = $pointer;
        $curr = 2;
    }
    
    if ($date >= $bells["break"][0] && $date <= $bells["break"][1]){
        
        $break = true;
        
    }
    
    if ($date >= $bells["third"][0] && $date <= $bells["third"][1]){
        
        $str[2] = $pointer;
        $curr = 3;
    }
    
    if ($date >= $bells["fourth"][0] && $date <= $bells["fourth"][1]){
        
        $str[3] = $pointer;
        $curr = 4;
    }
    
    if ($date >= $bells["fifth"][0] && $date <= $bells["fifth"][1]){
        
        $str[4] = $pointer;
        $curr = 5;
    }
    
    if ($break){
        
        $message = 'Сейчас &#8986;' . $dateStr . ', а значит должна идти большая перемена (с 11:50 до 12:30):';
        
    }else if ($dayend){
        
        $message = 'Сейчас &#8986;' . $dateStr . ', а значит, что сейчас не должно быть пар.';
        
    }else if (!$curr){
        
        $message = 'Сейчас &#8986;' . $dateStr . ', а значит, что сейчас, должно быть, перемена.';
        
    }else if (date("N") == 6 || date("N") == 7){
        
        $message = 'Сейчас &#8986;' . $dateStr . ', но сегодня выходные, а значит сейчас не должно быть пар.'; 
        $str = ["&#128280;", "&#128280;", "&#128280;", "&#128280;", "&#128280;", ];
    
    }else{
            
        $message = 'Сейчас &#8986;' . $dateStr . ', а значит должна идти ' . $curr . '-я пара:';
        
    }
    
    $message .= "\n";
    $message .= "\n" . $str[0] . " 1 пара - 08:30 - 09:15 / 09:20 - 10:05";
    $message .= "\n" . $str[1] . " 2 пара - 10:15 - 11:00 / 11:05 - 11:50";
    $message .= "\n" . $str[2] . " 3 пара - 12:30 - 13:15 / 13:20 - 14:05";
    $message .= "\n" . $str[3] . " 4 пара - 14:15 - 15:00 / 15:05 - 15:50";
    $message .= "\n" . $str[4] . " 5 пара - 16:00 - 16:45 / 16:50 - 17:35";
    
    return $message;    
}

function getHelpMessage($username){
    
    $hello = '';
    
    if ($is_hello){
        
        $hello = '';
        
    }
    
    $splitter = '&#10071;';
    
    $message = ['Привет, ' . $username . '! Я бот Жигулёвского Государственного Колледжа! Я нахожусь в процессе бета-тестирования. Я создан, чтобы у тебя всегда была возможность спросить у меня изменения в расписании!
    Я стараюсь самообучаться, поэтому ты можешь пробовать общаться со мной более-менее свободно, но у меня, как и у Вас, людей, иногда саморазвитие выходит не очень хорошо, поэтому вот основные функции, которыми я обладаю:'
                 ,

    '' . $splitter . '1. Подписка на уведомления об изменениях - как только я узнаю изменения в расписании, я могу написать тебе об этом, если ты заранее попросишь. Сделать это очень легко, достаточно написать мне что-то вроде: 
    "Подпиши меня на уведомления!", а затем, сказать мне свою группу. Впрочем, тебе ничего не мешает сразу написать мне "Подпиши меня на уведомления Д4ПО1!" (заменить на название своей группы). В таком случае, я сразу всё пойму.
    ' . $splitter . '2. Расписание на вчера/сегодня/завтра/послезавтра - я могу быстро сказать тебе расписание на вчерашний, сегодняшний, либо завтрашний день. 
    Для этого нужно всего лишь написать что-то вроде "Пары вчера", либо "Расписание", либо "Какие завтра пары, приятель?". Ну, если не хотите, то можно и без "приятеля".'
                 , 
    '' . $splitter . '3. Расписание для учителей - все Ваши пары на вчера/сегодня/завтра(см. 2 пункт) с помощью команды в духе "пары у Иванова"
    ' . $splitter . '4. Подписка на уведомления для учителей - как только я узнаю изменения в расписании, я могу написать Вам об этом, если Вы заранее попросите. Сделать это очень легко, достаточно написать мне что-то вроде: 
    "Подпиши меня на уведомления!", а затем, сказать мне свою фамилию. Впрочем, Вам ничего не мешает сразу написать мне "Подпиши меня - Иванов!" (заменить на свою фамилию). В таком случае, я сразу всё пойму.',
                
    '' . $splitter . '5. Сменить группу, либо фамилию, если Вы ошиблись с вводом можно просто написав что-то в духе "Смени группу Д1Т1", либо "Обнови фамилию - Иванов"
    ' . $splitter . '6. Узнать какая сейчас пара, а также получить расписание звонков, можно с помощью одной из вариаций команды "расписание звонков", "какая сейчас пара" и прочими.',
    
    '' . $splitter . '7. Отправить разработчику отзыв/пожелание/предложение - с помощью команды "Отправь разработчику". Пример: "Отправь разработчику: я хочу новую функцию, которая бы готовила яичницу.',
                
    'Пока что, это всё, что я могу, однако, помни, что чем больше ты со мной общаешься, тем умнее я становлюсь!
    Надеюсь, я смогу быть тебе полезен, ' . $username . '! :)',
                ];
    
    return $message;
    
}

function getFirstMessage($name){
    
    $splitter = '&#10071;';
    
    $message = [
            "&#11088; Привет, {$name}! Я бот ЖГК. Я буду сообщать тебе расписание и уведомлять о его изменении!",
            "&#9989; Чтобы, помочь начать мне это делать, напиши, пожалуйста мне свою группу. А именно что-то вроде \"Подпиши меня на группу Д1Т1\", или, если ты преподаватель, напиши вместо группы свою фамилию!",
            "&#10067; Если хочешь прочитать более подробную справку, напиши мне ключевое слово \"помощь\"!",
        ];
    
    return $message;
    
}

function sendMessages($id, $messages){
	        
        for($_i = 0; $_i < count($messages); $_i++){
        //С помощью messages.send и токена сообщества отправляем ответное сообщение
        
        global $token;
            
        $request_params = array(
            'message' => $messages[$_i],
            'user_id' => $id,
            'access_token' => $token,
            'v' => '5.0'
        );
			
        $get_params = http_build_query($request_params);
			
        file_get_contents('https://api.vk.com/method/messages.send?' . $get_params); 
			
        }
	
}

function subscribe($type, $id, $group){
    
    global $db_host, $db_name, $db_username, $db_password;
    
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    switch ($type){
            
        case "check":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $empty = true;
     
            while($data = mysql_fetch_array($results)){
                $empty = false;
            }

            if ($empty){ return false; }else if (!$empty){ return true; }
            
        break;
            
        case "checkGroup":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND studgroup != 'NULL';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $empty = true;
     
            while($data = mysql_fetch_array($results)){
                $empty = false;
            }

            if ($empty){ return false; }else if (!$empty){ return true; }
        break;
            
        case "checkTeacher":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND teacher != 'NULL';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $empty = true;
     
            while($data = mysql_fetch_array($results)){
                $empty = false;
            }

            if ($empty){ return false; }else if (!$empty){ return true; }
        break;
        
        case "checkSubscribed":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND status='subscribed';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $empty = true;
     
            while($data = mysql_fetch_array($results)){
                $empty = false;
            }

            if ($empty){ return false; }else if (!$empty){ return true; }
        break;
            
        case "checkSubscribing":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND status='subscribing';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $empty = true;
     
            while($data = mysql_fetch_array($results)){
                $empty = false;
            }

            if ($empty){ return false; }else if (!$empty){ return true; }
        break;
            
        case "checkUnsubscribed":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND status='unsubscribed';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $empty = true;
     
            while($data = mysql_fetch_array($results)){
                $empty = false;
            }

            if ($empty){ return false; }else if (!$empty){ return true; }
        break;
            
            
        case "getGroup":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND studgroup!='NULL';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $group = false;
     
            while($data = mysql_fetch_array($results)){
                $group = $data["studgroup"];
            }

            if ($group){ return $group; }else if (!$empty){ return false; }
        break;  
        
        case "getChatGroup":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_chats WHERE chat_id='" . $id . "' AND studgroup!='NULL';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $group = false;
     
            while($data = mysql_fetch_array($results)){
                $group = $data["studgroup"];
            }

            if ($group){ return $group; }else if (!$empty){ return false; }
        break;  
            
        case "getTeacher":
            // Делаем SQL
	        $sql = "SELECT * FROM vk_bot_users WHERE VK_ID='" . $id . "' AND teacher!='NULL';";
            $results = mysql_query($sql)
                or die(mysql_error());
     
            $teacher = false;
     
            while($data = mysql_fetch_array($results)){
                $teacher = $data["teacher"];
            }

            if ($teacher){ return $teacher; }else if (!$empty){ return false; }
        break;
            
        case "subscribeWithoutGroup":
            $sql = "INSERT INTO vk_bot_users (VK_ID, status) VALUES ('" . $id . "', 'subscribing')";
            mysql_query($sql)
                or die(mysql_error());
            return "Я записал Вас в нашу базу, но чтобы начать получать уведомления, мне нужно знать Вашу группу или фамилию, если Вы преподаватель. Пожалуйста, уточните. Например, \"Дружище, запиши, что я из Д4Т1\", либо \"Я - преподаватель Иванов\"";
        break;
            
        case "subscribeWithGroup":
            $sql = "INSERT INTO vk_bot_users (VK_ID, studgroup, status) VALUES ('" . $id . "', '" . mb_strtoupper($group) . "', 'subscribed')";
            mysql_query($sql)
                or die(mysql_error());
            return "Я записал Вас в нашу базу. Теперь Вы будете получать расписание для группы " . mb_strtoupper($group) . ". Теперь, вместо упоминания группы, можно писать просто \"пары\". Если захотите отписаться - дайте знать.";
        break;
            
        case "subscribeWithTeacher":
            $sql = "INSERT INTO vk_bot_users (VK_ID, teacher, status) VALUES ('" . $id . "', '" . $group . "', 'subscribed')";
            mysql_query($sql)
                or die(mysql_error());
            return "Я записал Вас в нашу базу. Теперь Вы будете получать расписание преподавателя  " . $group . ". Теперь, вместо упоминания фамилии, можно писать просто \"пары\". Если захотите отписаться - дайте знать.";
        break;
			
        case "changeGroup":
            $sql = "UPDATE vk_bot_users SET studgroup='" . mb_strtoupper($group) . "' WHERE VK_ID='" . $id . "'";
            mysql_query($sql)
                or die(mysql_error());
            return "Я обновил информацию о Вас: группа " . mb_strtoupper($group);
        break;
            
        case "changeTeacher":
            $sql = "UPDATE vk_bot_users SET teacher='" . $group . "' WHERE VK_ID='" . $id . "'";
            mysql_query($sql)
                or die(mysql_error());
            return "Я обновил информацию о Вас, " . $group;
        break;
			
        case "subscribeAddGroup":
            $sql = "UPDATE vk_bot_users SET studgroup='" . mb_strtoupper($group) . "', status='subscribed' WHERE VK_ID='" . $id . "'";
            mysql_query($sql)
                or die(mysql_error());
            return "Я обновил информацию о Вашей подписке. Теперь Вы будете получать расписание выбранной группы.";
        break;	
            
        case "subscribeAddTeacher":
            $sql = "UPDATE vk_bot_users SET teacher='" . $group . "', status='subscribed' WHERE VK_ID='" . $id . "'";
            mysql_query($sql)
                or die(mysql_error());
            return "Я обновил информацию о Вашей подписке. Теперь Вы будете получать расписание для выбранного преподавателя.";
        break;
			
        case "subscribe":
            $sql = "UPDATE vk_bot_users SET status='subscribed' WHERE VK_ID='" . $id . "'";
            mysql_query($sql)
                or die(mysql_error());
            return "Вы успешно подписаны на уведомления о расписании! Чтобы отписаться, просто напишите мне что-то вроде \"Отпиши, пожалуйста!\".";
        break;  
			
        case "unsubscribe":
            $sql = "UPDATE vk_bot_users SET status='unsubscribed' WHERE VK_ID='" . $id . "'";
            mysql_query($sql)
                or die(mysql_error());
            return "Вы успешно отписаны от уведомлений о расписании! Чтобы снова подписаться, просто напишите мне что-то вроде \"Подпиши меня снова, приятель!\". Надеюсь, я могу Вас так называть :)";
        break;
			
        case "fullDelete":
            $sql = "DELETE FROM vk_bot_users WHERE VK_ID='" . $id . "';";
            mysql_query($sql)
                or die(mysql_error());
            
            $sql = "DELETE FROM vk_bot_known_users WHERE VK_ID='" . $id . "';";
            mysql_query($sql)
                or die(mysql_error());
            return "Ой... Забыл, а кто Вы?";
        break;
            
            
    }
    
}

function addUnknownWord($word){
    
    $sql = "INSERT INTO vk_bot_unknown_words (word) VALUES ('" . $word . "')";
    
    global $db_host, $db_name, $db_username, $db_password;
    
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) 
        or die("Не могу подключиться:" . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    mysql_query($sql)
                or die(mysql_error());
}

function getGroupsArray(){
    
    $groups = array();
    
    global $db_host, $db_name, $db_username, $db_password;
    
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) 
        or die("Не могу подключиться:" . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    // Делаем SQL
	        $sql = "SELECT * FROM studygroups";
            $results = @mysql_query($sql)
                or die(mysql_error());
     
            $i = 0;
    
            while($data = mysql_fetch_array($results)){
                $groups[$i] = mb_strtolower($data['name']);
                $i++;
            }
    
            return $groups;
}

function getTeachersArray(){
    
    $teachers = [
                "lastnames" => [''],
                "initials" => [''],
                "fullnames" => [''],
                "r_case" => [''],
                "r_case_lastnames" => [''],
                ];
    
    
    global $db_host, $db_name, $db_username, $db_password;
    
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) 
        or die("Не могу подключиться:" . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    // Делаем SQL
	        $sql = "SELECT * FROM teachers";
            $results = @mysql_query($sql)
                or die(mysql_error());
     
            $i = 0;
    
            while($data = mysql_fetch_array($results)){
                
                $exploded = explode(" ", mb_strtolower($data['name']));
                $exploded_rcase = explode(" ", mb_strtolower($data['r_case']));
                
                $teachers["lastnames"][$i] = $exploded[0];
                $teachers["initials"][$i] = $exploded[1];
                $teachers["fullnames"][$i] = $data['name'];
                $teachers["r_case"][$i] = $data['r_case'];
                $teachers["r_case_lastnames"][$i] = $exploded_rcase[0];
                $i++;
            }
    
            return $teachers;
}

function getTeacher($name, $fullnames, $r_case){
    
    for ($i = 0; $i < count($fullnames); $i++){
        
        if (!(strpos(mb_strtolower($fullnames[$i]), $name) === false)){
            
            if ($r_case){
             
            return $i;
                
            }else{
            
            return $i;
                
            }
            
        }
        
    }
    
//    return $name;
    
}

function printsc($lesson, $teacher, $cabinet){
    if ($lesson == ""){
        return "(пусто)";
    }else{
        return $lesson . " / " . $teacher . " / " . $cabinet;
    }
}

function getHolidays(){
    
    $holidays = [
			'2018-04-30',
			'2018-05-01',
			'2018-05-02',
			'2018-05-09',
			'2018-06-11',
			'2018-06-12',
			];
    
    return $holidays;
    
}

function getHolidaysNames(){
    
    
    $holidaysNames = [
            '2018-05-09' => 'День Победы',
            '2018-06-11' => 'День России',
            '2018-06-12' => 'День России',
            ];
    
    return $holidaysNames;
    
}

function checkSchedule($date, $group){
        
    switch ($date){
            
        case 'tomorrow':
            $date = date("Y-m-d", strtotime("+1 day"));
            break;
        case 'today':
            $date = date("Y-m-d");
            break;
        case 'yesterday':
            $date = date("Y-m-d", strtotime("-1 day"));
            break;
            
            
    }
    
    if ($date == "tomorrow" && date("N", strtotime("+1 day")) > 5){
        
        $date = "monday";
        
    }
    
//    echo $group;
    
    if ($group != null){
                
        $answer = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&group=" . mb_strtoupper($group) . "&date=" . urlencode($date));
        
    }else{
        
        $answer = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . urlencode($date));
        
    }
    
//    echo "https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&group=" . mb_strtoupper($group) . "&date=" . urlencode($date);
        
    if ($answer == "true" || $answer == "exams" || $answer == "holidays"){
        
        return $answer;
        
    }else{

        return "false";
        
    }
}

function getScheduleList($group){
    
    $response = json_decode(file_get_contents("https://schedule.zhrt.ru/api.php?group=" . mb_strtoupper($group) . "&list=true"), true);
    
    if (count($response["schedules"]) == 0){
        
        return "К сожалению, мне не удалось найти новых раписаний для группы {$group}. Может быть, их и нету?"; 
        
    }else{
        
        $splitter = '&#128280; ';
        
        $answer = "Мне удалось найти расписание на следующие даты:\n\n";
        
        for ($i = 0; $i < count($response["schedules"]); $i++){
            
            $answer .= $splitter . getLongDayMonth($response["schedules"][$i]) . "\n";
            
        }
        
        return $answer . "\nЧтобы запросить расписание на определённый день, в Вашем сообщении должна содержаться дата в формате число и название месяца, например: \"Расписание на 3 сентября\"";
        
    }
    
}

function getLongDate($date){
    
     
        $months = array( 1 => 'января' , 'февраля' , 'марта' , 'апреля' , 'мая' , 'июня' , 'июля' , 'августа' , 'сентября' , 'октября' , 'ноября' , 'декабря' );
        $daysofweek = array("monday" => "понедельник",
                        "tuesday" => "вторник",
                        "wednesday" => "среда",
                        "thursday" => "четверг",
                        "friday" => "пятница",
                       );
        
    
    return date('d ' . $months[date( 'n', strtotime($date) )] . ' Y г.', strtotime($date));
    
}

function getLongDayMonth($date){
    
     
        $months = array( 1 => 'января' , 'февраля' , 'марта' , 'апреля' , 'мая' , 'июня' , 'июля' , 'августа' , 'сентября' , 'октября' , 'ноября' , 'декабря' );
        $daysofweek = array("monday" => "понедельник",
                        "tuesday" => "вторник",
                        "wednesday" => "среда",
                        "thursday" => "четверг",
                        "friday" => "пятница",
                       );
        
    
    return date('d ' . $months[date( 'n', strtotime($date) )], strtotime($date));
    
}

function getSchedule($group, $date, $advices){    
    
$holidays = getHolidays();
    
$holidaysNames = getHolidaysNames();
    
switch ($date){
        case "today":
			today:
            $date = date("Y-m-d");
            if ((date("N")) == 6){
                $date = date("Y-m-d", strtotime("+2 day"));
            }else if ((date("N")) == 7){
                $date = date("Y-m-d", strtotime("+1 day"));
            }
            break;
			
        case "tomorrow":    
            $d = strtotime("+1 day");
            $date = date("Y-m-d", $d);
            
            if (in_array($date, $holidays)){
             
                return 'Завтра праздник - ' . $holidaysNames[$date]; 
                
            }
            
            if ((date("N", $d) == 6)){
				// поменять
				$date = date("Y-m-d", strtotime("+1 day"));
                
                $check = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . $date);                
                if ($check == 'false'){
                
                    return "Завтра суббота."; 
                    
                }
            }
            else if (date("N", $d) == 7){
                return "Завтра воскресенье.";
            }
            break;
        
        case "DAtomorrow":    
            $d = strtotime("+2 day");
            $date = date("Y-m-d", $d);
            
            if (in_array($date, $holidays)){
             
                return 'Послезавтра праздник - ' . $holidaysNames[$date]; 
                
            }
            
            if ((date("N", $d) == 6)){
				// поменять
				$date = date("Y-m-d", strtotime("+2 day"));
                
                $check = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . $date);                
                if ($check == 'false'){
                
                    return "Послезавтра суббота."; 
                    
                } 
				$date = date("Y-m-d", strtotime("+2 day"));
            }
            else if (date("N", $d) == 7){
                return "Послезавтра воскресенье.";
            }
            break;
            
        case "yesterday":
            $d = strtotime("-1 day");
            $date = date("Y-m-d", $d);
            
            if ((date("N", $d) == 6)){
                $check = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . $date);                
                if ($check == 'false'){
                    
                return "Вчера была суббота.";
                    
                }
                
                $date = date("Y-m-d", $d);
                
            }else if (date("N", $d) == 7){
                
                return "Вчера было воскресенье.";
                
            }
            break;
		case "monday":
				if ((date("N") >= 5)){
					$d = ((7 - (date("N"))) + 1);
					$date = date("Y-m-d", strtotime("+" . $d . " days"));				
				}else{
					goto today;
				}	
			break;
		case "friday":
				if ((date("N") >= 5) || (date("N") == 1)){
					if ((date("N")) >= 5){
					$d = ((5 - (date("N"))));
					}
					$date = date("Y-m-d", strtotime($d . " days"));				
				}
                
                if ((date("N") == 4)){
                    $date = date("Y-m-d", strtotime("+1 day"));
                }
                
			break;
                
    }
    
$holiday = false;
           
if (in_array($date, $holidays)){
    
    $holiday = true;
    
    $offset = 1;
	
	while (in_array($date, $holidays) || (date("N", strtotime($date)) > 5)){
		
		$date = date("Y-m-d", $date + strtotime("+" . $offset . " days"));
		$offset++;
        
	}	
	
}
    
//return date("N", strtotime($date));
//return $date;
//return var_dump(in_array($date, $holidays));
    
//$date = '2018-05-03';
    
    
$months = array( 1 => 'января' , 'февраля' , 'марта' , 'апреля' , 'мая' , 'июня' , 'июля' , 'августа' , 'сентября' , 'октября' , 'ноября' , 'декабря' );
$daysofweek = array("monday" => "понедельник",
                    "tuesday" => "вторник",
                    "wednesday" => "среда",
                    "thursday" => "четверг",
                    "friday" => "пятница",
                   );
    
    
if (mb_substr($group, 0, 1) == "З" && 
   json_decode(file_get_contents("https://schedule.zhrt.ru/api.php?group=" . $group . "&date=" . $date), true)["type"] == 'main'){ // if no schedule on chosen day.
    
    return getScheduleList($group);    
    
}else if (mb_substr($group, 0, 1) != "З"){ // if it is not night group

    $cr = checkSchedule($date, $group);

    if ($cr == "false"){

    return "&#128681; Изменения на " . getLongDate($date) . " пока что не были опубликованы. " . getAdvice(2, $group); 

    }else if ($cr == "exams" || $cr == "holidays"){



    }else{

        if (date("N") > 5 && !$holiday){

            $date = date("Y-m-d", strtotime("+" . (8 - date("N")) . " days"));

        }

    }
    
} 
    
    
$response = file_get_contents("https://schedule.zhrt.ru/api.php?group=" . $group . "&date=" . $date . "&day=" . mb_strtolower(date("l", strtotime($date))));
        
$response = json_decode($response, true);

// изменения
$group = mb_strtoupper($group);
    
if ($response["type"] == "change"){
    
    $splitter = '&#128280;';
	
    $schedule = "&#128681; Изменения " . $group . " на " . getLongDate($date) . ":\n";
    
    if (mb_substr($group, 0, 1) == "З"){
        
        $schedule = "Расписание заочного отделения, группа {$group} на " . getLongDate($date) . ":\n";
        
    }
    
    if ($response["first"] != ""){
     
    $schedule .= "\n" . $splitter . "1 пара - " . printsc($response["first"], $response["firstT"], $response["firstC"]);
    
    }
        
    if ($response["firstHalf"] != ""){
    
    $schedule .= "\n" . $splitter . "2 половина 1 пары - " . $response["firstHalf"] . " / " . $response["firstTHalf"]. " / " . $response["firstCHalf"];
    
    }
    
    if ($response["second"] != ""){
        
    $schedule .= "\n" . $splitter . "2 пара - " . printsc($response["second"], $response["secondT"], $response["secondC"]);
    
    }
    
    if ($response["secondHalf"] != ""){
    
    $schedule .= "\n" . $splitter . "2 половина 2 пары - " . $response["secondHalf"] . " / " . $response["secondTHalf"]. " / " . $response["secondCHalf"];
    
    }
    
    if ($response["third"] != ""){
    
    $schedule .= "\n" . $splitter . "3 пара - " . printsc($response["third"], $response["thirdT"], $response["thirdC"]);
    
    }
        
    if ($response["thirdHalf"] != ""){
    
    $schedule .= "\n" . $splitter . "2 половина 3 пары - " . $response["thirdHalf"] . " / " . $response["thirdTHalf"]. " / " . $response["thirdCHalf"];
    
    }
    
    if ($response["fourth"] != ""){
            
    $schedule .= "\n" . $splitter . "4 пара - " . printsc($response["fourth"], $response["fourthT"], $response["fourthC"]);
    
    }
        
    if ($response["fourthHalf"] != ""){
    
    $schedule .= "\n" . $splitter . "2 половина 4 пары - " . $response["fourthHalf"] . " / " . $response["fourthTHalf"]. " / " . $response["fourthCHalf"];
    
    }
    
    if ($response["fifth"] != ""){
    
    $schedule .= "\n" . $splitter . "5 пара - " . printsc($response["fifth"], $response["fifthT"], $response["fifthC"]);
    
    }
        
    if ($response["fifthHalf"] != ""){
    
    $schedule .= "\n" . $splitter . "2 половина 5 пары - " . $response["fifthHalf"] . " / " . $response["fifthTHalf"]. " / " . $response["fifthCHalf"];
    
    }
    
    if ($response["sixth"] != ""){
    
    $schedule .= "\n" . $splitter . "6 пара - " . printsc($response["sixth"], $response["sixthT"], $response["sixthC"]);
    
    }
        
    if ($response["sixthHalf"] != ""){
    
    $schedule .= "\n" . $splitter . "2 половина 6 пары - " . $response["sixthHalf"] . " / " . $response["sixthTHalf"]. " / " . $response["sixthCHalf"];
    
    }
    
}else if ($response["type"] == "main"){

    // основное
    
	$schedule = "&#128681; Без изменений ";
	
	
    $schedule .= "на " . getLongDate($date) . ".\n\nОсновное расписание для " . $group . " (" . $response["week"] . " неделя - " . $daysofweek[mb_strtolower(date("l", strtotime($date)))] . "):\n";
    
    
	if ($response["first"] != ""){
	
		$schedule .= "\n" . $splitter . "1 пара - " . printsc($response["first"], $response["firstT"], $response["firstC"]);
        
	}
	
	if ($response["second"] != ""){
    
		$schedule .= "\n" . $splitter . "2 пара - " . printsc($response["second"], $response["secondT"], $response["secondC"]);
    
	}
	
	if ($response["third"] != ""){
    
		$schedule .= "\n" . $splitter . "3 пара - " . printsc($response["third"], $response["thirdT"], $response["thirdC"]);
	
	}
    
	if ($response["fourth"] != ""){
	
	$schedule .= "\n" . $splitter . "4 пара - " . printsc($response["fourth"], $response["fourthT"], $response["fourthC"]);
    
	}
	
	if ($response["fifth"] != ""){
		
    $schedule .= "\n" . $splitter . "5 пара - " . printsc($response["fifth"], $response["fifthT"], $response["fifthC"]);
	
	}
//    $schedule .= "\n6 пара - " . printsc($response["sixth"], $response["sixthT"], $response["sixthC"]);
    
}else if ($response['type'] == 'exams'){
    
        $message = "Экзамены группы " . mb_strtoupper($group) . ": \n";

        $point = "&#128280;";

        for ($i = 0; $i < $response['count']; $i++){

            $type = $response['exam' . $i]['type'];

            $response['exam' . $i]['date'] = date('d.m.Y', strtotime($response['exam' . $i]['date']));

            switch ($type){

                case 'consult':
                    $type = 'Консультация';
                    break;
                case 'exam':
                    $type = 'Экзамен';
                    break;

                default:
                    break;

            }

            $message .= "\n{$point}" . $type . " / " . $response['exam' . $i]['name'] . " / " . $response['exam' . $i]['teacher'];
            $message .= "\n" . $response['exam' . $i]['cabinet'] . " / " . $response['exam' . $i]['date'] . " / " . $response['exam' . $i]['time'];
            $message .= "\n";
        }
    
    return $message; 
    
    }else if ($response['type'] == 'emptyExams'){
    
        return 'Хмм... Не могу найти твоё расписание. Что-то пошло не так.';
    
    }else if ($response['type'] == 'holidays'){
           
        return 'У группы ' . $group . ' каникулы до ' . getLongDate($response['end']);
    
    }else if ($response['type'] == 'study_practice'){
           
        return 'У группы ' . $group . ' учебная практика до ' . getLongDate($response['end']);
    
    }else if ($response['type'] == 'production_practice'){
           
        return 'У группы ' . $group . ' производственная практика до ' . getLongDate($response['end']);
    
    }else if ($response['type'] == 'summer_holidays'){
           
        return 'У группы ' . $group . ' летние каникулы до ' . getLongDate($response['end']);
    
    }else{
    
        return 'Хмм... Что-то пошло не так. Код ошибки: WRONG_API_ANSWER ' . $response['type'];    
    
    }
    
    if($advices)
        $schedule .= getAdvice(1, $group);
    
    return $schedule;
    
}

function getAdvice($id, $group){
    
    global $user_id;
    
    if ($group == null || $group == ""){
        $group = "Д1Т1";
    }
    
    if (!subscribe("checkSubscribed", $user_id, null) && !subscribe("checkSubscribing", $user_id, null)){
        
        switch($id){
            case 1:
                return "\n\n&#10071; Я обратил внимание, что Вы всё ещё не подписаны на уведомления. Рекомендую сделать это, чтобы получать уведомления о новом расписании. К примеру, если хотите подписаться на группу " . mb_strtoupper($group) . ", напишите мне что-то вроде \"Подпиши меня на {$group}\"";
                break;
            case 2:
                return "\n\n&#10071; Чтобы я смог написать Вам, когда расписание станет доступно, Вам следует подписаться на уведомления. Например, чтобы подписаться на группу " . mb_strtoupper($group) . ", следует написать что-то вроде \"Подпиши меня на " . mb_strtoupper($group) . ", дружище-бот\"";
                break;
        }
    }
    
    return '';
}

function getScheduleTeacher($date, $teacher, $r_case){
    
    $holidays = getHolidays();

    $holidaysNames = getHolidaysNames();
        
    switch ($date){
           
        case "today":
			today:
            $date = date("Y-m-d");
            if ((date("N")) == 6){
                $date = date("Y-m-d", strtotime("+2 day"));
            }else if ((date("N")) == 7){
                $date = date("Y-m-d", strtotime("+1 day"));
            }
            break;
			
        case "tomorrow":    
            $d = strtotime("+1 day");
            $date = date("Y-m-d", $d);
            
               if (in_array($date, $holidays)){
             
                return 'Завтра праздник - ' . $holidaysNames[$date]; 
                
                }
           
            if ((date("N", $d) == 6)){
				// поменять
                $check = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . $date);                
                if ($check == 'false'){
                    return "Завтра суббота."; 
                }
				$date = date("Y-m-d", strtotime("+1 day"));
            }
            else if (date("N", $d) == 7){
                return "Завтра воскресенье.";
            }
            break;
       
       case "DAtomorrow":    
            $d = strtotime("+2 day");
            $date = date("Y-m-d", $d);
            
               if (in_array($date, $holidays)){
             
                return 'Послезавтра праздник - ' . $holidaysNames[$date]; 
                
            }
           
            if ((date("N", $d) == 6)){
				// поменять
                $check = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . $date);                
                if ($check == 'false'){
                    
                    return "Послезавтра суббота."; 
                    
                }
                
				$date = date("Y-m-d", $d);
            }
            else if (date("N", $d) == 7){
                return "Послезавтра воскресенье.";
            }
            break;
            
        case "yesterday":
            $d = strtotime("-1 day");
            $date = date("Y-m-d", $d);
            
            if ((date("N", $d) == 6)){
                
                $check = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=checkSchedule&date=" . $date);                
                if ($check == 'false'){
                
                    return "Вчера была суббота.";
                    
                }
                
            }else if (date("N", $d) == 7){
                return "Вчера было воскресенье.";
            }
            break;
		case "monday":
				if ((date("N") >= 5)){
					$d = ((7 - (date("N"))) + 1);
					$date = date("Y-m-d", strtotime("+" . $d . " days"));				
				}else{
					goto today;
				}	
			break;
		case "friday":
				if ((date("N") >= 5) || (date("N") == 1)){
					if ((date("N")) >= 5){
					$d = ((5 - (date("N"))));
					}
					$date = date("Y-m-d", strtotime($d . " days"));				
				}	
			break;
                
    }
    
    $holiday = false;

    if (in_array($date, $holidays)){

        $holiday = true;

        $offset = 1;

        while (in_array($date, $holidays) || (date("N", strtotime($date)) > 5)){

            $date = date("Y-m-d", $date + strtotime("+" . $offset . " days"));
            $offset++;

        }	

    }

    $months = array( 1 => 'января' , 'февраля' , 'марта' , 'апреля' , 'мая' , 'июня' , 'июля' , 'августа' , 'сентября' , 'октября' , 'ноября' , 'декабря' );
    $daysofweek = array("monday" => "понедельник",
                        "tuesday" => "вторник",
                        "wednesday" => "среда",
                        "thursday" => "четверг",
                        "friday" => "пятница",
                       );
      
    
    
    if (checkSchedule($date, null) == "false"){

    return "Изменения на " . getLongDate($date) . " пока что не были опубликованы."; 

}else{
    
    if (date("N") > 5 && !$holiday){
        
        $date = date("Y-m-d", strtotime("+" . (8 - date("N")) . " days"));
        
    }
    
}
    
    $response = file_get_contents("https://schedule.zhrt.ru/api.php?teacher=" . urlencode($teacher) . "&date=" . $date);
    
    $response = json_decode($response, true);
    
//    print_r($response);

    if ($response["type"] == "emptyTeacher"){
        
        return "Пусто! У " . $r_case . " на " . date('d ' . $months[date( 'n', strtotime($date) )] . ' Y г.', strtotime($date)) . " нету занятий!";

    }else if ($response["type"] == "teacher"){
        
        
        $schedule = "Пары у " . $r_case . " на " . date('d ' . $months[date( 'n', strtotime($date) )] . ' Yг', strtotime($date)) . ":\n";
        
        $splitter = '&#128280;';
        
        if ($response["first"]) {
            
            $schedule .= "\n" . $splitter . "1 пара у " . $response["firstG"] . " в " . $response["firstC"] . " - " . $response["first"];
            
        }
        
        if ($response["firstHalf"]) {
            
            $schedule .= "\n" . $splitter . "2 половина 1 пары у " . $response["firstGHalf"] . " в " . $response["firstCHalf"] . " - " . $response["firstHalf"];
            
        }
        
        if ($response["second"]) {
            
            $schedule .= "\n" . $splitter . "2 пара у " . $response["secondG"] . " в " . $response["secondC"] . " - " . $response["second"];
            
        }
        
        if ($response["secondHalf"]) {
            
            $schedule .= "\n" . $splitter . "2 половина 2 пары у " . $response["secondGHalf"] . " в " . $response["secondCHalf"] . " - " . $response["secondHalf"];
            
        }
        
        if ($response["third"]) {
            
            $schedule .= "\n" . $splitter . "3 пара у " . $response["thirdG"] . " в " . $response["thirdC"] . " - " . $response["third"];
            
        }
        
        if ($response["thirdHalf"]) {
            
            $schedule .= "\n" . $splitter . "2 половина 3 пары у " . $response["thirdGHalf"] . " в " . $response["thirdCHalf"] . " - " . $response["thirdHalf"];
            
        }
        
        if ($response["fourth"]) {
            
            $schedule .= "\n" . $splitter . "4 пара у " . $response["fourthG"] . " в " . $response["fourthC"] . " - " . $response["fourth"];
            
        }
        
        if ($response["fourthHalf"]) {
            
            $schedule .= "\n" . $splitter . "2 половина 4 пары у " . $response["fourthGHalf"] . " в " . $response["fourthCHalf"] . " - " . $response["fourthHalf"];
            
        }
        
        if ($response["fifth"]) {
            
            $schedule .= "\n" . $splitter . "5 пара у " . $response["fifthG"] . " в " . $response["fifthC"] . " - " . $response["fifth"];
            
        }
        
        if ($response["fifthHalf"]) {
            
            $schedule .= "\n" . $splitter . "2 половина 5 пары у " . $response["fifthGHalf"] . " в " . $response["fifthCHalf"] . " - " . $response["fifthHalf"];
            
        }
        
        if ($response["sixth"]) {
            
            $schedule .= "\n" . $splitter . "6 пара у " . $response["sixthG"] . " в " . $response["sixthC"] . " - " . $response["sixth"];
            
        }
        
        if ($response["sixthHalf"]) {
            
            $schedule .= "\n" . $splitter . "2 половина 6 пары у " . $response["sixthGHalf"] . " в " . $response["sixthCHalf"] . " - " . $response["sixthHalf"];
            
        }
        
        return $schedule;

    }

}

// ADDING SCHEDULE
//
if ($_POST['function'] == "addSchedule"){
    
    function addSchedulePublished($date){
        
        $date = date("Y-m-d", strtotime($date));

        global $db_host, $db_name, $db_username, $db_password;

        $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
        // Подключение к DB
        mysql_select_db($db_name, $connect_to_db)
        or die();
        mysql_query("SET NAMES utf8");
        // Делаем SQL
        $sql = "INSERT INTO schedule_published (date, post_link) VALUES ('{$date}', '{$_POST['url']}')";
        @mysql_query($sql)
            or die(mysql_error());
            
    }
        
	// удаляем кэш
	
	function removeDir($path){
	if(file_exists($path) && is_dir($path))
	{
		$dirHandle = opendir($path);
		while (false !== ($file = readdir($dirHandle))) 
		{
			if ($file!='.' && $file!='..') 
			{
				$tmpPath=$path.'/'.$file;
				chmod($tmpPath, 0777);
				
				if (is_dir($tmpPath))
	  			{  // если папка
					RemoveDir($tmpPath);
			   	} 
	  			else 
	  			{ 
	  				if(file_exists($tmpPath))
					{
	  					unlink($tmpPath);
					}
	  			}
			}
		}
		closedir($dirHandle);
		if(file_exists($path))
		{
			rmdir($path);
		}
	}
	else
	{
		
	}

	}

    ///

	$access = false;
	
if ($_POST['push'] == "true" || $_POST['mail'] == "true" || $_POST['vk_bot'] == "true"){
	
    global $db_host, $db_name, $db_username, $db_password;
		
    // Соединение
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться: " . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
	// Выбираем все значения из таблицы
        $qr_result = mysql_query("SELECT token FROM tokens")
        or die(mysql_error());
    
    // Выводим значения
    
    while($data = mysql_fetch_array($qr_result))
    {
		$token = $data['token']; 
    }
		
    // Закрываем соединение с DB
        mysql_close($connect_to_db);	
		
	if ($token == $_POST['token']){
		$access = true;
	}
		
	}

    if ($access
//        && !$_POST['onlyme']
       ){
        
        $filename = $_SERVER['DOCUMENT_ROOT'] . "/wp-content/cache/supercache/zhrt.ru/%d1%80%d0%b0%d1%81%d0%bf%d0%b8%d1%81%d0%b0%d0%bd%d0%b8%d0%b5";

        removeDir($filename);

        addSchedulePublished($_POST['date']);
        
    }
		
	if ($access && ($_POST['push'] == "true")){

	if ($_POST['url']){
		$url = $_POST['url'];
	}else{
		$url = 'https://zhrt.ru/';
	}
		
	$content = $_POST['content'];
	$ids = Array();
		
function POSTDevices(){ 
  $app_id = "YOUR_ONESIGNAL_APP_ID_HERE";
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/players?app_id=883f177f-8e35-4dcc-a070-538110174a07"); 
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 
                                             'Authorization: Basic ZjQ2MzMzMWQtZDU2YS00ZjcyLTgwYzgtMGZiOTA2ZmZjM2Q5')); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  $response = curl_exec($ch); 
  curl_close($ch); 
  return $response; 
	}

	$response = POSTDevices();
    if (!$response){
        echo '[BugReport] Ошибка при получении списка устройств для Push';
    }else{
	$data = json_decode($response, true);
	$count = $data[total_count];
	$_c = 0;
		
	while ($_c < $count){
		$ids[$_c] = $data[players][$_c][id];
		$_c++;
	}
		
	$return["allresponses"] = $response;
	$return = json_encode($return); 
		
		if ($_POST['onlyme'] == true){
			$ids = array("c6b4671a-8f40-463f-995e-27582dbc95ca");
		}
		
		$content = array(
            "en" => $_POST["name"],
            );

        $fields = array(
            'app_id' => "883f177f-8e35-4dcc-a070-538110174a07",
            'include_player_ids' => $ids,
			'url' => $url,
			//array("c6b4671a-8f40-463f-995e-27582dbc95ca"),
            'data' => array("foo" => "bar"),
            'contents' => $content
        );
		
        //print_r($fields);
        
        $fields = json_encode($fields);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                                   'Authorization: Basic ZjQ2MzMzMWQtZDU2YS00ZjcyLTgwYzgtMGZiOTA2ZmZjM2Q5'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response == false){
            echo '[BugReport] Ошибка при отправке Push';
        }else{
            echo 'Push=>Success';
        }
        
        //return $response;
        }
    }

    echo '|';
    
	if ($access && ($_POST['mail'] == "true")){
		
	global $db_host, $db_name, $db_username, $db_password;

    $db_name = "b17587_main";
        
    $emailSuccess = true;
		
    // Соединение
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
	// Выбираем все значения из таблицы
        $qr_result = mysql_query("SELECT * FROM `wp_es_emaillist` WHERE `es_email_status`='Confirmed'")
        or die(mysql_error());
    
    // Выводим значения
    
	$emails = array();
	$names = array();
	$i = 0;
		
    while($data = mysql_fetch_array($qr_result))
    {
		$names[$i] = $data['es_email_name'];
		$emails[$i] = $data['es_email_mail'];
		$i++;
    }
		
	if ($_POST['onlyme'] == true){
		$names = array("Братишкааа");
		$emails = array("mr.anomalyy@gmail.com");
	}
		
	$subject = "Уведомление о расписании";
		
	if (!empty($_POST['name'])){
		 $subject = $subject . " - " . $_POST['name'];
	}
		
	$i = 0;
		
	while ($i < count($emails)){	
		
	$to  = "<" . $emails[$i] . ">, " ; 
	$to .= $emails[$i];
		
	if ($names[$i] == ''){
		$currName = $emails[$i];
	}else{
		$currName = $names[$i];
	}
		
	switch ($_POST['mailTemplate']){
		case 0:
			
			$message = "Здравствуйте, " . $currName . ". <br><br>
		

		Новое расписание было опубликовано на сайте";
			
		if (!empty($_POST['name'])){
			$message .= ": " . $_POST['name'] . "";
		}
			
		if (!empty($_POST['url'])){
		
		$message = $message . "<br>
		Вы можете:<br>
		
		<a href=\"" . $_POST['url'] . "\"><br>Открыть это расписание</a><br>";
			
		}
		$message = $message . "
		<br><a href=\"http://zhrt.ru/расписание\">Просмотреть страницу расписания занятий</a>
		<br><br><hr>
		Вы получили это письмо, потому что подписаны на рассылку.<br>
		<i>
		Спасибо за то, что Вы с нами!<br>
		Администрация сайта</i>";
			
			break;
		default:
			die("[BugReport] Ошибка при выборе шаблона сообщения");
		break;
	}
	
	

    $headers  = "Content-type: text/html; charset=utf-8 \r\n"; 
    $headers .= "From: ЖГК <no-reply@zhrt.ru>\r\n"; 
    $headers .= "Reply-To: admin@zhrt.ru\r\n"; 
		
    $result = mail($to, $subject, $message, $headers);
		
	if (!$result){
        $emailSuccess = false;
		echo '[BugReport] Возникла ошибка при отправке E-Mail по адресату: ' . $names[$i] . ' <' . $emails[$i] . '><br>';
	}
		$i++;
		
	}	
		
    // Закрываем соединение с DB
        mysql_close($connect_to_db);	
		
        $db_name = $config->db["table"];
        
        if ($emailSuccess){
            echo 'Email=>Success';
        }
        
	}

	echo '|';

	if ($access && ($_POST['vk_bot'] == "true")){
		
        global $db_host, $db_name, $db_username, $db_password;

        $vk_bot_error = false;
        
        $Answers = getAnswers("");
        
        $config = new config();

        //Строка для подтверждения адреса сервера из настроек Callback API
        $confirmationToken = $config->vk_bot['confirmation'];
        //Ключ доступа сообщества
        $token = $config->vk_bot['token'];
        // Secret key
        $secretKey = $config->vk_bot['secret'];

        $ids = array();
        $chats = array();

        // получаем подписчиков
        $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) or die("Не могу подключиться:" . mysql_error());
        // Подключение к DB
        mysql_select_db($db_name, $connect_to_db)
        or die();
        mysql_query("SET NAMES utf8");
        // Делаем SQL
        $sql = "SELECT * FROM vk_bot_users WHERE status='subscribed';";
        $results = mysql_query($sql)
            or die(mysql_error());

        $i = 0;

        while($data = mysql_fetch_array($results)){
            $ids[$i] = $data["VK_ID"];
            $i++;
        }

        // Делаем SQL
        $sql = "SELECT * FROM vk_bot_chats WHERE status='true';";
        $results = mysql_query($sql)
            or die(mysql_error());

        $i = 0;

        while($data = mysql_fetch_array($results)){
            $chats[$i] = $data["chat_id"];
            $i++;
        }

         if ($_POST['onlyme'] == true){

             $ids = array("156152406");
             $chats = array("3");

         }

         for($i = 0; $i < count($ids); $i++){

         //затем с помощью users.get получаем данные об авторе
            $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$ids[$i]}&v=5.0&access_token=" . $token));
            $userInfo = $userInfo->response[0];
            //и извлекаем из ответа его имя
            $user_name = $userInfo->first_name;

         if (isset($_POST['vkmessage']) && $_POST['vkmessage'] != ""){
             $message = str_replace(
                 array( // what need to replace
                            "@USERNAME",
                            "@LASTNAME",
                          ), 
                 array( // what place instead
                            $userInfo->first_name,
                            $userInfo->last_name,
                           ), $_POST['vkmessage']);
         }else{

            if (date("N") != 5){		 

                $message = "Привет, " . $user_name . "! Я узнал об изменениях на завтра! Пиши!";

            }else{

                $message = "Привет, " . $user_name . "! Я узнал об изменениях на " . $_POST['name'] . "! Пиши!";

            }

            // here can be hot.

            $group = subscribe("getGroup", $ids[$i], null);

            if ($group)         
                $message = getSchedule($group, $_POST['date'], true);

         }

         //С помощью messages.send и токена сообщества отправляем ответное сообщение
            $request_params = array(
                'message' => "$message",
                'user_id' => $ids[$i],
                'random_id' => mt_rand(15, 200000),
                'access_token' => $token,
                'read_state' => 1,
                'v' => '5.84'
            );
            $get_params = http_build_query($request_params);
            file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);

         }

         for ($i = 0; $i < count($chats); $i++){

            $message = "Привет, беседа! Новое расписание доступно! Спрашивайте! ";

            $group = subscribe("getChatGroup", $chats[$i], 'Братииишкааа, я тебе покушать принёс.');

            if ($group)
                $message = "&#10071;" . $Answers["schedule"]["new"][rand(0, count($Answers["schedule"]["new"]) - 1)] . "\n\n" . getSchedule($group, $_POST['date'], false);

            //С помощью messages.send и токена сообщества отправляем ответное сообщение
            $request_params = array(
                'message' => "$message",
                'chat_id' => $chats[$i],
                'access_token' => $token,
                'read_state' => 1,
                'random_id' => mt_rand(15, 200000),
                'v' => '5.84'
            );
            $get_params = http_build_query($request_params);

            file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);         

         }

         echo 'vk_bot=>Success';
		
	}
}

?>
