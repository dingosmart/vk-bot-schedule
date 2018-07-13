<?php

    require 'config.php';

    $config = new config();

    $db_host = $config->db['host'];
    $db_name = $config->db['table'];
    $db_username = $config->db['user'];
    $db_password = $config->db['password'];
    
    $connect_to_db = @mysql_connect($db_host, $db_username, $db_password) 
        or die("Не могу подключиться:" . mysql_error());

    // Подключение к DB
    mysql_select_db($db_name, $connect_to_db)
    or die("Не могу выбрать базу данных:" . mysql_error());
    mysql_query("SET NAMES utf8");
    
    function is_allowed($token){

            $date = [date("Y-m-d H:i:s"), date("Y-m-d H:i:s", strtotime("-1 day"))];

            $sql = "SELECT * FROM vk_bot_cp_tokens WHERE token='" . $token . "' AND time < '{$date[0]}' AND time > '{$date[1]}';";

            $results = @mysql_query($sql)
                or die(mysql_error());

            $empty = true;


            while($data = mysql_fetch_array($results)){

                $empty = false;

            }

            if (!$empty){

                return true;

            }else{

                return false;

            }


}

    switch($_GET['function']){

        case 'addView':
            
                $sql = "INSERT INTO vk_bot_views (type) VALUES ('{$_GET['type']}')";
              
                @mysql_query($sql);
            
            break;
            
        case 'info':
            
            $currenttimestamp = date("Y-m-d H:i:s");
                        
            switch ($_GET['type']){
                    
                case 'countLastDay':
                    
                    $prevDay = date("Y-m-d H:i:s", strtotime("-1 day"));
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE timestamp < '" . $currenttimestamp . "' AND timestamp > '" . $prevDay . "';";
                    
                    break;
                    
                case 'countLast3Days':
                    
                    $prevDay = date("Y-m-d H:i:s", strtotime("-3 days"));
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE timestamp < '" . $currenttimestamp . "' AND timestamp > '" . $prevDay . "';";
                    
                    break;
                    
                case 'countLast7Days':
                    
                    $prevDay = date("Y-m-d H:i:s", strtotime("-7 days"));
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE timestamp < '" . $currenttimestamp . "' AND timestamp > '" . $prevDay . "';";
                    
                    break;
                    
                case 'countBadWordsLast7Days':
                    
                    $prevDay = date("Y-m-d H:i:s", strtotime("-7 days"));
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE timestamp < '" . $currenttimestamp . "' AND timestamp > '" . $prevDay . "' AND (type='badlangMessage' OR type='insultMessage');";
                    
                    break;
                    
                case 'countTeacherLast3Days':
                    
                    $prevDay = date("Y-m-d H:i:s", strtotime("-3 days"));
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE timestamp < '" . $currenttimestamp . "' AND timestamp > '" . $prevDay . "' AND type='scheduleByTeacher';";
                    
                    break;
                    
                case 'countTeacherLast7Days':
                    
                    
                    $prevDay = date("Y-m-d H:i:s", strtotime("-7 days"));
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE timestamp < '" . $currenttimestamp . "' AND timestamp > '" . $prevDay . "' AND type='scheduleByTeacher';";
                    
                    break;  
                    
                case 'userCount':
                                        
                    $sql = "SELECT COUNT(*) FROM vk_bot_known_users;";
                    
                    break;
                    
                case 'subscribedUserCount':
                    
                    $sql = "SELECT COUNT(*) FROM vk_bot_users WHERE status='subscribed';";
                    
                    break;
                    
                default:
                    echo 'error';
                    break;
                        
            }
            
            $results = @mysql_query($sql)
                or die(mysql_error());

            while($data = mysql_fetch_array($results)){

                echo $data['COUNT(*)'];

            }
            
            break;
            
        case 'getAllCount':

            $sql = "SELECT COUNT(*) FROM vk_bot_analytics";
            $results = @mysql_query($sql)
                or die(mysql_error());

            while($data = mysql_fetch_array($results)){

                echo $data['COUNT(*)'];

            }
            
        break;
            
        case 'getCount':
            
            $sql = "SELECT COUNT(*) FROM vk_bot_analytics WHERE type='" . $_GET['type'] . "';";
            $results = @mysql_query($sql)
                or die(mysql_error());

            while($data = mysql_fetch_array($results)){

                echo $data['COUNT(*)'];

            }
            
        break;
        
        case 'getGroups':
            
            $sql = "SELECT * FROM studygroups";
            
            $results = @mysql_query($sql)
                or die(mysql_error());

            $groups = [];
            $i = 0;
            
            while($data = mysql_fetch_array($results)){

                $groups[$i] = $data['name'];
                $i++;
            }
            
            echo json_encode($groups);
            
        break;
            
        case 'getNews':
            
            $sql = "SELECT * FROM vk_bot_news ORDER BY ID DESC";
            
            $results = @mysql_query($sql)
                or die(mysql_error());
            
            $count = 0;
            $news = [
                "title" => [],
                "message" => [],
                "date" => [],
                "ids" => [],
            ];

            while($data = mysql_fetch_array($results)){

                $news["title"][$count] = $data["title"];
                $news["message"][$count] = $data["message"];         
                $news["date"][$count] = date("d.m.Y H:i", strtotime($data["timestamp"]));
                $news["ids"][$count] = $data["ID"];
                
                $count++;
                
            }
            
            echo json_encode($news);
            
            break;
            
        case 'addNews':
            
            if (!is_allowed($_POST['token'])){
                
                echo 'wrong token';
                
                return false;
                
            }
            
            $sql = "INSERT INTO vk_bot_news (title, message) VALUES ('" . $_POST['title'] . "', '" . $_POST['message'] . "');";
            
            @mysql_query($sql)
                or die(mysql_error());
            
            echo 'ok';
            
            break;
            
        case 'editNews':
            
            if (!is_allowed($_POST['token'])){
                
                echo 'wrong token';
                
                return false;
                
            }
            
            $sql = 'UPDATE vk_bot_news SET title="' . $_POST['title'] . '", message="' . $_POST['text'] . '", timestamp="' . $_POST['date'] . '" WHERE ID="' . $_POST['id'] . '";';
            
            @mysql_query($sql)
                or die(mysql_error());
            
            echo 'ok';
            
            break; 
            
        case 'deleteNews':
            
            if (!is_allowed($_POST['token'])){
                
                echo 'wrong token';
                
                return false;
                
            }
            
            $sql = 'DELETE FROM vk_bot_news WHERE ID="' . $_POST['id'] . '";';
            
            @mysql_query($sql)
                or die(mysql_error());
            
            echo 'ok';
            
            break;
            
        case 'checkSchedule':
            
            $sql = "SELECT * FROM schedule_published WHERE date='{$_GET['date']}'";
            
             $results = @mysql_query($sql)
                or die(mysql_error());

            $empty = true;
            
            while($data = mysql_fetch_array($results)){

                $empty = false;
                
            }
            
            if ($empty){
                

                $sql = "SELECT * FROM exams WHERE studgroup='" . $_GET['group'] . "' AND date >= '" . $_GET['date'] . "' LIMIT 1;";

                 $results = @mysql_query($sql)
                    or die(mysql_error());

                $emptyExams = true;

                while($data = mysql_fetch_array($results)){

                    $emptyExams = false;

                }
                
                if ($emptyExams){
                
                    $sql = "SELECT * FROM schedule_holidays WHERE (studgroup='{$_GET['group']}' OR studgroup='all') AND start <= '{$_GET['date']}' AND end >= '{$_GET['date']}' LIMIT 1;";

                     $results = @mysql_query($sql)
                        or die(mysql_error());

                    $emptyHolidays = true;

                    while($data = mysql_fetch_array($results)){

                        $emptyHolidays = false;

                    }

                    if ($emptyHolidays){

                        echo 'false';

                    }else{

                        echo 'holidays';

                    }
                    
                }else{
                    
                    echo 'exams';
                    
                }
                
                
            }else{
                
                
                echo 'true';
                
            }
            
            break;
            
        case 'auth':
            
            if(is_allowed($_POST['token'])){
                echo 'true';
            }else{
                echo 'false';
            }
            
            return;
            
            break;
            
        case 'login':
            
            if (isset($_POST['token'])){
                
                $date = [date("Y-m-d H:i:s"), date("Y-m-d H:i:s", strtotime("-1 day"))];
                
                $sql = "SELECT * FROM vk_bot_cp_tokens WHERE token='" . $_POST['token'] . "' AND time < '{$date[0]}' AND time > '{$date[1]}';";

                $results = @mysql_query($sql)
                    or die(mysql_error());

                $empty = true;

                while($data = mysql_fetch_array($results)){

                    $empty = false;

                }

                if (!$empty){

                    echo $_POST['token'];

                }else{

                    echo 'false';

                }
                
                
            }else{
            
                $sql = "SELECT * FROM vk_bot_analytics_accounts WHERE login='" . $_POST['login'] . "' AND password='" . $_POST['password'] . "';";

                $results = @mysql_query($sql)
                    or die(mysql_error());

                $empty = true;

                while($data = mysql_fetch_array($results)){

                    $empty = false;

                }

                if (!$empty){

                    $token = md5(time() . '_rofl');

                    $sql = "INSERT INTO vk_bot_cp_tokens (token) VALUES ('" . $token . "')";

                    @mysql_query($sql)
                    or die(mysql_error());

                    echo $token;

                }else{

                    echo 'false';

                }
            
            }
            break;
            
    }

?>
