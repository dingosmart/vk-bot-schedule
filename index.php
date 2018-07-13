<?php

ini_set('date.timezone', 'Europe/Samara');

function GetIP() {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}

if (!$_COOKIE['old']){

    file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=addView&type=new&ip={$ip}");
    
    setcookie("old", "true");

}else{
    
    file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=addView&type=old&ip={$ip}");
    
}

function printNews(){
    
    $news = file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/functions.php?function=getNews");

    $news = json_decode($news, true);
    
    for ($i = 0; $i < count($news["title"]); $i++){
        
        echo '<div class="message" id="message-' . $news["ids"][$i] . '"><div class="buttons"><div class="edit" id="edit-' . $news["ids"][$i] . '" onClick="news.edit(\'' . $news["ids"][$i] . '\');"></div><div class="delete" id="delete-' . $news["ids"][$i] . '" onClick="news.delete(\'' . $news["ids"][$i] . '\');"></div></div><div class="title" id="title-' . $news["ids"][$i] . '">' . $news["title"][$i] . '</div><div class="text" id="text-' . $news["ids"][$i] . '">' . $news["message"][$i] . '</div><div class="date" id="date-' . $news["ids"][$i] . '">' . $news["date"][$i] . '</div></div>';
        
    }
}

?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>ВК Бот ЖГК</title>
        <link rel="stylesheet" href="style.css">
        <script src='Chart.min.js'></script>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    </head>

    <body>

        <form action="" method="get">
            <div class="groups" id="groupsFrame">
                <div class="logo">
                    Выберите группы
                </div>


                <div class="list" id="groupList"></div>


                <hr width="60%">

                <div class="teachers">Отправить учителям?<input type="checkbox" id="sendTeachers"></div>

                <p><input type="submit" id="confirmGroupsBtn" onclick="return message.chooseGroups.confirm()" value="Подтвердить"></p>
        </form>

        </div>


        <div class="inputVK_ID" id="inputVK_ID">

            <div class="inputs">

                <textarea id="VK_IDSendTo" placeholder="Текст" rows="3" cols="25"></textarea>
                <br>
                <br>
                <input type="button" id="VK_IDSendBtn" value="Отправить" disabled>

            </div>

            <div class="profile">

                <div class="avatar" id="VK_IDAvatar">

                    <img style="transform: translateX(75%); filter: saturate(50%);" src="dog_vk_deal.jpg" width="40%" height="40%" alt="">

                </div>

                <div class="name" id="VK_IDName">Неизвестный пользователь</div>

                <div class="field"><input type="text" id="VK_IDField" placeholder="Введите ID"></div>

            </div>

        </div>

        <div class="blur" id="blur"></div>
        <div class="page">
        </div>

        <div class="arrowR" id="arrowR"></div>
        <div class="arrowL" id="arrowL"></div>

        <div class="mainframe" id="mainframe">
            <div class="news" id="news">

                <div class="logo">Новости бота</div>

                <?php printNews(); ?>

            </div>
            <div class="splitter" id="splitter"></div>
            <div class="analytics">
                <div class="counter">
                    <span class="number" id="messCount">0000</span> <span class="line" id="line">бот отправил</span> <br> <span class="messagesText">сообщений</span>
                </div>

                <span id="from">из них:</span>

                <div class="diagramm" id="diagrammPlace">
                    <canvas id="diagrammCnv"></canvas>
                </div>

                <span id="and"> а также:</span>
                <div class="container">
                    <div class="info">
                        <div class="line" id="info0"></div>
                        <div class="line" id="info1"></div>
                        <div class="line" id="info2"></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="cp" id="cp">

            <div class="login" id="loginContainer">

                <div class="title">Панель управления</div>

                <div class="alert" id="alert">
                    <div class="title">Ошибка!</div>
                    <div id="alertText"></div>
                </div>

                <div class="container">
                    <input type="text" id="login" placeholder="Логин">
                    <input type="password" id="password" placeholder="********">
                    <input type="submit" id="loginBtn" value="Вход">
                </div>

            </div>


            <div class="controls" id="cpControls">

                <div class="title">Панель управления</div>

                <div class="functions">

                    <div class="function" id="function0">
                        <div class="head">Добавить новость</div>
                        <div class="body"><input type="text" placeholder="Тема" id="addNewsTitle"><textarea id="addNewsMessage" rows="5" placeholder="Сообщение"></textarea><input id="addNewsBtn" type="button" value="Отправить"></div>
                    </div>

                    <div class="function" id="function1">
                        <div class="head">Отправить сообщение</div>
                        <div class="body"><textarea id="sendMessageText" rows="5" placeholder="Сообщение"></textarea>
                            <select id="sendMessageSelect">
                        <option value="all">Всем</option>
                        <option value="by_group">По группе... (в т.ч. учителя)</option>
                        <option value="by_VK_ID">По VK ID...</option>
                    </select>

                            <div class="choosed" id="choosed"></div>

                            <input id="sendMessageBtn" type="button" value="Отправить"></div>
                    </div>

                    <div class="function" id="function2">
                        <div class="head">Выбрать случайного</div>
                        <div class="body">

                            <img class="randomAvatar" style="filter: saturate(50%);" src="dog_vk_deal.jpg" width="40%" height="40%" alt="">
                            
                            <div id="randomName"></div>

                            <input type="button" value="Обновить">
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </body>

    <script src="scripts.js"></script>

    </html>
