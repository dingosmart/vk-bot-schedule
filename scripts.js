window.onfocus = function () { // чтоб не умирали плитки информации

    for (i = 0; i < 3; i++) {

        document.getElementById('info' + i).style.display = 'block';

    }

}


var _messages_count = 1;

var goCountInverval = null;

var firstCountLoad = true;

function goCount(count) {

    var counter = document.getElementById('messCount');

    if (!firstCountLoad) {

        counter.style.animation = 'fadeOut 0.3s 1';

        setTimeout(function () {
            counter.style.opacity = 0;
            counter.style.animation = 'none';
        }, 290);

    }

    setTimeout(function () {

        counter.innerHTML = count;

        counter.style.animation = 'fadeIn 0.6s 1';

        setTimeout(function () {
            counter.style.opacity = 1;
        }, 590);

    }, 350);

    if (firstCountLoad) {

        firstCountLoad = !firstCountLoad; // haha, u mad bro

    }

}

function getRandom(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

// COOKIE

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}


function setCookie(name, value, options) {
    options = options || {};

    var expires = options.expires;

    if (typeof expires == "number" && expires) {
        var d = new Date();
        d.setTime(d.getTime() + expires * 1000);
        expires = options.expires = d;
    }
    if (expires && expires.toUTCString) {
        options.expires = expires.toUTCString();
    }

    value = encodeURIComponent(value);

    var updatedCookie = name + "=" + value;

    for (var propName in options) {
        updatedCookie += "; " + propName;
        var propValue = options[propName];
        if (propValue !== true) {
            updatedCookie += "=" + propValue;
        }
    }

    document.cookie = updatedCookie;

}

function deleteCookie(name) {

    setCookie(name, "", {
        expires: -1
    })

}

function getSeveralRandom(min, max, num) {
    var i, arr = [],
        res = [];
    for (i = min; i <= max; i++) arr.push(i);
    for (i = 0; i < num; i++) res.push(arr.splice(Math.floor(Math.random() * (arr.length)), 1)[0])
    return res;
}

function updateCount() {
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function () {
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200)

            //            document.getElementById('messCount').innerHTML = xmlHttp.responseText;
//            messages_count = xmlHttp.responseText;
            goCount(xmlHttp.responseText);

    }
    xmlHttp.open("GET", "functions.php?function=getAllCount", true); // true for asynchronous 
    xmlHttp.send(null);
}

function updateNews(){
    
    sendPOST("functions.php?function=getNews", "", function(result){
        
      result = JSON.parse(result);
        
      var newsBlock = document.getElementById('news');
        
      newsBlock.innerHTML = '<div class="logo">Новости бота</div>';
        
      console.log(result);
        
      for (i = 0; i < result['title'].length; i++){
          
          console.log(result['ids'][i]);
          
          var newNew = document.createElement('div');
          newNew.id = 'message-' + result['ids'][i];
          newNew.className = 'message';
          
          var buttons = document.createElement('div');
          buttons.className = 'buttons';
                    
          var id = result['ids'][i];
          
          buttons.innerHTML = '<div class="edit" id="edit-' + id + '" onclick="news.edit(\'' + id + '\');"></div>';
          buttons.innerHTML += '<div class="delete" id="delete-' + id + '" onclick="news.delete(\'' + id + '\');"></div>';
          
          newNew.appendChild(buttons);
          
          var title = document.createElement('div');
          title.id = 'title-' + result['ids'][i];
          title.className = "title";
          title.innerHTML = result['title'][i];
          newNew.appendChild(title);
          
          var text = document.createElement('div');
          text.id = 'text-' + result['ids'][i];
          text.className = "text";
          text.innerHTML = result['message'][i];
          newNew.appendChild(text);
          
          var date = document.createElement('div');
          date.id = 'date-' + result['ids'][i];
          date.className = "date";
          date.innerHTML = result['date'][i];
          newNew.appendChild(date);
          
          newsBlock.appendChild(newNew);
          
      }
         
        
    });
    
    
}

function getXmlHttp() {
    var xmlhttp;
    try {
        xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
            xmlhttp = false;
        }
    }
    if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
        xmlhttp = new XMLHttpRequest();
    }
    return xmlhttp;
}


function sendPOST(url, params, todo) {

    var xmlhttp = getXmlHttp();
    xmlhttp.open('POST', url, true);
    xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    var xmlhttpstr = params;
    //    console.log("URL: " + url + " | Params: " + params);
    xmlhttp.send(xmlhttpstr);
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4) {
            if (xmlhttp.status == 200) {

                todo(xmlhttp.responseText);

            }
        }
    };

}

function randomColor(brightness) {
    function randomChannel(brightness) {
        var r = 255 - brightness;
        var n = 0 | ((Math.random() * r) + brightness);
        var s = n.toString(16);
        return (s.length == 1) ? '0' + s : s;
    }
    return '#' + randomChannel(brightness) + randomChannel(brightness) + randomChannel(brightness);
}

var diagramm = {

    infoCodes: [
        "firstUseMessage",
        "helloMessage",
        "insultMessage",
        "badlangMessage",
        "noMessage",
        "yesMessage",
        "thanksMessage",
        "unknownMessage",
        "subscribeGroup",
        "subscribeTeacher",
        "scheduleByGroup",
        "scheduleByTeacher",
        "scheduleMonday",
        "scheduleFriday",
        "scheduleToday",
        "scheduleTomorrow",
        "schedule",
        "scheduleFail",
        "forgetFunction",
    ],

    infoNames: [
        "Первое сообщение",
        "Приветствие",
        "Оскорбления",
        "Плохие слова",
        "Подписка: группа",
        "Подписка: учитель",
        "Нет",
        "Да",
        "Благодарность",
        "Неизвестное",
        "Расписание по группе",
        "Расписание по учителю",
        "Расписание \"на понедельник\"",
        "Расписание \"на пятницу\"",
        "Расписание \"на сегодня\"",
        "Расписание \"на завтра\"",
        "Расписание: без уточнений",
        "Неудача: расписание",
        "Функция \"Забыть\"",
    ],

    info: {

        firstUseMessage: 0,
        helloMessage: 0,
        insultMessage: 0,
        badlangMessage: 0,
        subscribeGroup: 0,
        subscribeTeacher: 0,
        noMessage: 0,
        yesMessage: 0,
        thanksMessage: 0,
        unknownMessage: 0,
        scheduleByGroup: 0,
        scheduleByTeacher: 0,
        scheduleMonday: 0,
        scheduleFriday: 0,
        scheduleToday: 0,
        scheduleTomorrow: 0,
        schedule: 0,
        scheduleFail: 0,
        forgetFunction: 0,

    },

    colors: {

        firstUseMessage: 0,
        helloMessage: 0,
        insultMessage: 0,
        badlangMessage: 0,
        subscribeGroup: 0,
        subscribeTeacher: 0,
        noMessage: 0,
        yesMessage: 0,
        thanksMessage: 0,
        unknownMessage: 0,
        scheduleByGroup: 0,
        scheduleByTeacher: 0,
        scheduleMonday: 0,
        scheduleFriday: 0,
        scheduleToday: 0,
        scheduleTomorrow: 0,
        schedule: 0,
        scheduleFail: 0,
        forgetFunction: 0,

    },

    draw: function () {

        setTimeout(function () {

            // пересоздать канвас

            document.getElementById('diagrammPlace').innerHTML = '';

            var newCNV = document.createElement('canvas');

            newCNV.id = 'diagrammCnv';

            document.getElementById('diagrammPlace').appendChild(newCNV);

            //
            var diagrammElement = document.getElementById('diagrammCnv');
            var diagrammCtx = document.getElementById('diagrammCnv').getContext("2d");

            var data = {

                labels: diagramm.infoNames,
                datasets: [
                    {
                        data: [""],
                        backgroundColor: [""]
            }],

                options: {
                    animation: {
                        animateRotate: false,
                        animateScale: true

                    },
                },

            };

            for (i = 0; i < diagramm.infoCodes.length; i++) {

                data.datasets[0]["data"][i] = diagramm.info[diagramm.infoCodes[i]];
                //        data.datasets.backgroundColor[i] = diagramm.colors[diagramm.infoCodes[i]];
                data.datasets[0]["backgroundColor"][i] = randomColor(5);


            }

            //            console.log(data);

            var pieChart = new Chart(diagrammElement, {
                type: 'pie',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animateScale: true,
                    segmentShowStroke: false,
                    animation: {
                        animationRotate: false,
                        animationScale: true,
                    },
                    legend: {
                        display: false
                    },
                    elements: {
                        arc: {
                            borderColor: 'transparent'
                        }
                    }

                }
            });

        }, 500);

    },

    loading: {},

    load: function (type) {

        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200)

                diagramm.info[type] = xmlHttp.responseText;
            diagramm.loading[type] = false;

            return xmlHttp.responseText;

        }
        xmlHttp.open("GET", "functions.php?function=getCount&type=" + type, true); // true for asynchronous 
        diagramm.loading[type] = true;
        xmlHttp.send(null);

    },

    update: function () {

        for (i = 0; i < this.infoCodes.length; i++) {

            this.load(this.infoCodes[i]);

        }

        updateCount();

        var timeout = 0;
        var success = false;

        setInterval(function () {
            timeout++
        }, 1000);

        while (timeout < 10 && success == false) {

            success = true;

            for (i = 0;
                (i < this.infoCodes.length) && (success = true); i++) {

                if (this.loading[this.infoCodes[i]] == false) {

                    success = false;

                }

            }

            if (success) {

                this.draw();

                if (info.loaded) {

                    info.reload();

                } else {

                    info.load();

                }

                //                console.log('success update');

            }

        }


    },

    cnv: document.getElementById('diagrammCnv'),

    onMouseOver: function () {

    },

    onMouseOut: function () {

    },

};

var info = {

    loaded: false,

    types: [
            "countLastDay",
            "countLast3Days",
            "countLast7Days",
            "countBadWordsLast7Days",
            "countTeacherLast3Days",
            "countTeacherLast7Days",
            "userCount",
            "subscribedUserCount",
            ],

    do: function (type, line) {


        var leftSide = '';
        var rightSide = '';

        switch (type) {

            case "countLastDay":

                leftSide = '<h3>Отправлено сообщений</h3>';
                rightSide = 'за последние 24 часа';

                break;

            case "countLast3Days":

                leftSide = '<h3>Отправлено сообщений</h3>';
                rightSide = 'за последние 3 дня';

                break;

            case "countLast7Days":

                leftSide = '<h3>Отправлено сообщений</h3>';
                rightSide = 'за последние 7 дней';

                break;

            case "countBadWordsLast7Days":

                leftSide = '<h3>Получено плохих слов</h3>';
                rightSide = 'за последние 7 дней';

                break;

            case "countTeacherLast3Days":

                leftSide = '<h3>Отправлено учителям</h3>';
                rightSide = 'сообщений за последние 3 дня';

                break;

            case "countTeacherLast7Days":

                leftSide = '<h3>Отправлено учителям</h3>';
                rightSide = 'сообщений за последние 7 дней';

                break;

            case "userCount":

                leftSide = '<h3>Открыто диалогов</h3>';
                rightSide = 'за всё время';

                break;

            case "subscribedUserCount":

                leftSide = '<h3>Подписано человек</h3>';
                rightSide = 'за всё время';

                break;

            default:

                console.error('[bug] unknown type of info: ' + type);
                return false;

                break;

        }


        sendPOST("functions.php?function=info&type=" + type, "", function (result) {

            line.innerHTML = leftSide + '<div class="value">' + result + '</div>' + rightSide;

            line.style.display = 'block';

        });

    },

    load: function () {

        var randoms = getSeveralRandom(0, info.types.length - 1, 3);

        for (i = 0; i < 3; i++) {

            var line = document.getElementById('info' + i);

            info.do(info.types[randoms[i]], line);

        }

        if (!info.loaded) {

            info.loaded = true;

        }

    },

    reload: function () {

        for (i = 0; i < 3; i++) {

            document.getElementById('info' + i).style.animation = 'none';

            var line = document.getElementById('info' + i);

            this.hide(line);

        }

        setTimeout(this.load, 450);

    },

    hide: function (el) {

        setTimeout(function () {

            el.style.animation = 'zoomOut 0.3s 1';

            setTimeout(function () {

                el.style.display = 'none';
                el.style.animation = 'bottomToTopFade 0.4s 1';

            }, 290);
        }, 100);


    },

}

diagramm.update();

window.onload = function () {

    setInterval(function () {
        diagramm.update();
    }, 60000);

}

diagramm.cnv.onmouseover = diagramm.onMouseOver;
diagramm.cnv.onmouseout = diagramm.onMouseOut;

var cp = {

    block: false,

    open: function () {

        if (getCookie("token")) {

            cp.login.checkToken(getCookie("token"));

        }

        var mainframe = document.getElementById('mainframe');
        var cpobj = document.getElementById('cp');

        var arrowR = document.getElementById('arrowR');
        var arrowL = document.getElementById('arrowL');


        arrowR.style.opacity = 0;

        setTimeout(function () {
            arrowR.style.display = 'none';
            arrowR.style.opacity = 0;
        }, 410);

        arrowL.style.display = 'block';

        setTimeout(function () {

            arrowL.style.opacity = 0.4;

        }, 700);


        mainframe.style.animation = 'pushLeft 0.8s 1';

        setTimeout(function () {

            cpobj.style.display = 'flex';

            cpobj.style.animation = 'pushFromRight 0.8s 1';

        }, 200);

        setTimeout(function () {

            setTimeout(function () {
                mainframe.style.animation = 'none';
                mainframe.style.display = 'none';

            }, 50);
        }, 750);

    },

    close: function () {


        var mainframe = document.getElementById('mainframe');
        var cp = document.getElementById('cp');

        var arrowR = document.getElementById('arrowR');
        var arrowL = document.getElementById('arrowL');


        arrowL.style.opacity = 0;

        setTimeout(function () {
            arrowL.style.display = 'none';
            arrowL.style.opacity = 0.4;
        }, 600);

        arrowR.style.display = 'block';

        setTimeout(function () {

            arrowR.style.opacity = 0.4;

        }, 600);

        cp.style.animation = 'pushRight 0.7s 1';

        setTimeout(function () {

            cp.style.display = 'none';

        }, 690);

        setTimeout(function () {

            mainframe.style.animation = 'pushFromLeft 0.7s 1';
            mainframe.style.display = 'flex';

        }, 250);


    },

    hideLogin: function () {

        var login = document.getElementById('loginContainer');
        var cp = document.getElementById('cp');
        var controls = document.getElementById('cpControls');

        var adminJS = document.createElement('script');

        adminJS.src = "admin.js";

        document.body.appendChild(adminJS);

        login.style.animation = 'zoomOut 0.6s 1';

        setTimeout(function () {

            login.style.display = 'none';

        }, 550);

        cp.style.height = '70%';
        cp.style.top = '15%';

        setTimeout(function () {

            controls.style.display = 'flex';
            controls.style.animation = 'fadeIn 0.6s 1';

        }, 480);

        var onBtns = document.createElement('style');

        onBtns.innerHTML = '.mainframe .news .message .buttons { display: flex; }';

        document.body.appendChild(onBtns);

    },

    setup: function () {

        document.getElementById('arrowR').onclick = this.open;
        document.getElementById('arrowL').onclick = this.close;

        document.getElementById('loginBtn').onclick = this.login.sendAuth;

    },

    login: {

        alert: function (text) {

            var alert = document.getElementById('alert');

            alert.style.display = 'block';

            alert.style.animation = 'zoomIn 0.4s 1';

            alert.style.opacity = 1;

            setTimeout(function () {

                alert.style.opacity = 0;

            }, 2500);

            document.getElementById('alertText').innerHTML = text;

        },

        sendAuth: function () {

            if (cp.login.check()) {

                sendPOST('functions.php?function=login', 'login=' + encodeURIComponent(document.getElementById('login').value) + '&password=' + encodeURIComponent(md5(document.getElementById('password').value)), cp.login.checkAuth);


            }

        },

        checkAuth: function (token) {

            token = token != "false" ? token : false

            if (token) {

                setCookie("token", token, 86400);
                cp.hideLogin();

            } else {

                cp.login.alert('Неправильная пара логин/пароль');

            }

        },

        checkToken: function (token) {

            sendPOST("functions.php?function=login", "token=" + token, this.checkAuth);

        },

        check: function () {

            console.log('xdxd');
            return true;

        },
    },

};


cp.setup();


//////////////////////////////////////// MD5 ///////////////////////////////////////////


/**
 * 
 * С кириллицей возможны проблемы, разные результаты между PHP md5-функцией и javascript-версией
 * Исходный код функции я не менял. Оставлены оригинальные комментарии.
 *
 */


function md5cycle(x, k) {
    var a = x[0],
        b = x[1],
        c = x[2],
        d = x[3];

    a = ff(a, b, c, d, k[0], 7, -680876936);
    d = ff(d, a, b, c, k[1], 12, -389564586);
    c = ff(c, d, a, b, k[2], 17, 606105819);
    b = ff(b, c, d, a, k[3], 22, -1044525330);
    a = ff(a, b, c, d, k[4], 7, -176418897);
    d = ff(d, a, b, c, k[5], 12, 1200080426);
    c = ff(c, d, a, b, k[6], 17, -1473231341);
    b = ff(b, c, d, a, k[7], 22, -45705983);
    a = ff(a, b, c, d, k[8], 7, 1770035416);
    d = ff(d, a, b, c, k[9], 12, -1958414417);
    c = ff(c, d, a, b, k[10], 17, -42063);
    b = ff(b, c, d, a, k[11], 22, -1990404162);
    a = ff(a, b, c, d, k[12], 7, 1804603682);
    d = ff(d, a, b, c, k[13], 12, -40341101);
    c = ff(c, d, a, b, k[14], 17, -1502002290);
    b = ff(b, c, d, a, k[15], 22, 1236535329);

    a = gg(a, b, c, d, k[1], 5, -165796510);
    d = gg(d, a, b, c, k[6], 9, -1069501632);
    c = gg(c, d, a, b, k[11], 14, 643717713);
    b = gg(b, c, d, a, k[0], 20, -373897302);
    a = gg(a, b, c, d, k[5], 5, -701558691);
    d = gg(d, a, b, c, k[10], 9, 38016083);
    c = gg(c, d, a, b, k[15], 14, -660478335);
    b = gg(b, c, d, a, k[4], 20, -405537848);
    a = gg(a, b, c, d, k[9], 5, 568446438);
    d = gg(d, a, b, c, k[14], 9, -1019803690);
    c = gg(c, d, a, b, k[3], 14, -187363961);
    b = gg(b, c, d, a, k[8], 20, 1163531501);
    a = gg(a, b, c, d, k[13], 5, -1444681467);
    d = gg(d, a, b, c, k[2], 9, -51403784);
    c = gg(c, d, a, b, k[7], 14, 1735328473);
    b = gg(b, c, d, a, k[12], 20, -1926607734);

    a = hh(a, b, c, d, k[5], 4, -378558);
    d = hh(d, a, b, c, k[8], 11, -2022574463);
    c = hh(c, d, a, b, k[11], 16, 1839030562);
    b = hh(b, c, d, a, k[14], 23, -35309556);
    a = hh(a, b, c, d, k[1], 4, -1530992060);
    d = hh(d, a, b, c, k[4], 11, 1272893353);
    c = hh(c, d, a, b, k[7], 16, -155497632);
    b = hh(b, c, d, a, k[10], 23, -1094730640);
    a = hh(a, b, c, d, k[13], 4, 681279174);
    d = hh(d, a, b, c, k[0], 11, -358537222);
    c = hh(c, d, a, b, k[3], 16, -722521979);
    b = hh(b, c, d, a, k[6], 23, 76029189);
    a = hh(a, b, c, d, k[9], 4, -640364487);
    d = hh(d, a, b, c, k[12], 11, -421815835);
    c = hh(c, d, a, b, k[15], 16, 530742520);
    b = hh(b, c, d, a, k[2], 23, -995338651);

    a = ii(a, b, c, d, k[0], 6, -198630844);
    d = ii(d, a, b, c, k[7], 10, 1126891415);
    c = ii(c, d, a, b, k[14], 15, -1416354905);
    b = ii(b, c, d, a, k[5], 21, -57434055);
    a = ii(a, b, c, d, k[12], 6, 1700485571);
    d = ii(d, a, b, c, k[3], 10, -1894986606);
    c = ii(c, d, a, b, k[10], 15, -1051523);
    b = ii(b, c, d, a, k[1], 21, -2054922799);
    a = ii(a, b, c, d, k[8], 6, 1873313359);
    d = ii(d, a, b, c, k[15], 10, -30611744);
    c = ii(c, d, a, b, k[6], 15, -1560198380);
    b = ii(b, c, d, a, k[13], 21, 1309151649);
    a = ii(a, b, c, d, k[4], 6, -145523070);
    d = ii(d, a, b, c, k[11], 10, -1120210379);
    c = ii(c, d, a, b, k[2], 15, 718787259);
    b = ii(b, c, d, a, k[9], 21, -343485551);

    x[0] = add32(a, x[0]);
    x[1] = add32(b, x[1]);
    x[2] = add32(c, x[2]);
    x[3] = add32(d, x[3]);
}

function cmn(q, a, b, x, s, t) {
    a = add32(add32(a, q), add32(x, t));
    return add32((a << s) | (a >>> (32 - s)), b);
}

function ff(a, b, c, d, x, s, t) {
    return cmn((b & c) | ((~b) & d), a, b, x, s, t);
}

function gg(a, b, c, d, x, s, t) {
    return cmn((b & d) | (c & (~d)), a, b, x, s, t);
}

function hh(a, b, c, d, x, s, t) {
    return cmn(b ^ c ^ d, a, b, x, s, t);
}

function ii(a, b, c, d, x, s, t) {
    return cmn(c ^ (b | (~d)), a, b, x, s, t);
}

function md51(s) {
    txt = '';
    var n = s.length,
        state = [1732584193, -271733879, -1732584194, 271733878],
        i;

    for (i = 64; i <= s.length; i += 64) {
        md5cycle(state, md5blk(s.substring(i - 64, i)));
    }

    s = s.substring(i - 64);
    var tail = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    for (i = 0; i < s.length; i++) {
        tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
    }

    tail[i >> 2] |= 0x80 << ((i % 4) << 3);

    if (i > 55) {
        md5cycle(state, tail);
        for (i = 0; i < 16; i++) {
            tail[i] = 0;
        }
    }

    tail[14] = n * 8;
    md5cycle(state, tail);
    return state;
}

/* there needs to be support for Unicode here,
 * unless we pretend that we can redefine the MD-5
 * algorithm for multi-byte characters (perhaps
 * by adding every four 16-bit characters and
 * shortening the sum to 32 bits). Otherwise
 * I suggest performing MD-5 as if every character
 * was two bytes--e.g., 0040 0025 = @%--but then
 * how will an ordinary MD-5 sum be matched?
 * There is no way to standardize text to something
 * like UTF-8 before transformation; speed cost is
 * utterly prohibitive. The JavaScript standard
 * itself needs to look at this: it should start
 * providing access to strings as preformed UTF-8
 * 8-bit unsigned value arrays.
 */
function md5blk(s) {
    var md5blks = [],
        i; /* Andy King said do it this way. */
    for (i = 0; i < 64; i += 4) {
        md5blks[i >> 2] = s.charCodeAt(i) + (s.charCodeAt(i + 1) << 8) + (s.charCodeAt(i + 2) << 16) + (s.charCodeAt(i + 3) << 24);
    }

    return md5blks;
}

var hex_chr = '0123456789abcdef'.split('');

function rhex(n) {
    var s = '',
        j = 0;
    for (; j < 4; j++) {
        s += hex_chr[(n >> (j * 8 + 4)) & 0x0F] + hex_chr[(n >> (j * 8)) & 0x0F];
    }

    return s;
}

function hex(x) {
    for (var i = 0; i < x.length; i++) {
        x[i] = rhex(x[i]);
    }

    return x.join('');
}

function md5(s) {
    return hex(md51(s));
}

/* this function is much faster,
so if possible we use it. Some IEs
are the only ones I know of that
need the idiotic second function,
generated by an if clause.  */
function add32(a, b) {
    return (a + b) & 0xFFFFFFFF;
}

if (md5('hello') != '5d41402abc4b2a76b9719d911017c592') {
    function add32(x, y) {
        var lsw = (x & 0xFFFF) + (y & 0xFFFF),
            msw = (x >> 16) + (y >> 16) + (lsw >> 16);
        return (msw << 16) | (lsw & 0xFFFF);
    }
}
