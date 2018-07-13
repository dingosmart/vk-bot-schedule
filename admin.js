var addNews = {

    check: function () {

        if (document.getElementById('addNewsTitle').value != "" &&
            document.getElementById('addNewsMessage').value != "") {

            return true;

        }

    },

    send: function () {

        if (!addNews.check())
            return false;

        var body = document.getElementById('function0');

        body.style.filter = 'blur(2px)';

        var title = document.getElementById('addNewsTitle');
        var message = document.getElementById('addNewsMessage');

        sendPOST("functions.php?function=addNews", "token=" + getCookie("token") + "&title=" + encodeURIComponent(title.value) + '&message=' + encodeURIComponent(message.value), function (result) {

            if (result == 'ok') {

                updateNews();

                title.value = "";
                message.value = "";

                setTimeout(function () {

                    body.style.filter = 'none';

                    setTimeout(function () {

                        cp.close();

                    }, 100);

                }, 490);

            } else {


                setTimeout(function () {

                    body.style.filter = 'none';

                }, 490);


            }

            console.log(result);

        });


    },

};

var parseBrowserDate = function (str) {

    str = str.split(' ');

    var date = str[0].split(".");

    date = date[2] + '-' + date[1] + '-' + date[0];

    str = date + "T" + str[1];

    return str;

    console.log(str);

}

var parseDate = function (str) {

    str = str.split('T');

    str = str[0] + ' ' + str[1];

    return str;

    console.log(str);

}

var parseNewsDate = function (str) {

    str = str.split('T');

    var date = str[0].split("-");

    date = date[2] + '.' + date[1] + '.' + date[0];

    str = date + ' ' + str[1];

    return str;

    console.log(str);

}

var news = {

    edit: function (id) {

        var message = document.getElementById('message-' + id);
        var title = document.getElementById('title-' + id);
        var text = document.getElementById('text-' + id);
        var date = document.getElementById('date-' + id);
        var edit = document.getElementById('edit-' + id);

        message.style.opacity = 0;

        setTimeout(function () {


            if (message == null || title == null || text == null || date == null) {

                console.error('Ошибка при выборе новости ID: ' + id);
                return false;

            }

            var title_field = document.createElement('input');

            title_field.type = 'text';
            title_field.value = title.innerHTML;
            title_field.id = 'title_field-' + id;

            var text_field = document.createElement('textarea');

            text_field.innerHTML = text.innerHTML;
            text_field.id = 'text_field-' + id;
            text_field.rows = '6';

            var date_field = document.createElement('input');

            date_field.type = 'datetime-local';
            date_field.id = 'date_field-' + id;
            date_field.value = parseBrowserDate(date.innerHTML);
            date_field.style.width = '70%';

            title.innerHTML = '';
            title.appendChild(title_field);

            text.innerHTML = '';
            text.appendChild(text_field);

            date.innerHTML = '';
            date.appendChild(date_field);

            edit.style.background = 'url(save.png)'
            edit.style.backgroundSize = 'contain';
            edit.style.backgroundRepeat = 'no-repeat';
            edit.style.backgroundPosition = 'center';

            edit.onclick = function () {
                news.save(id);
            };

        }, 390);

        setTimeout(function () {
            message.style.opacity = 1;
        }, 390);

        console.log(id);

    },

    save: function (id) {

        var _title = document.getElementById('title_field-' + id);
        var _text = document.getElementById('text_field-' + id);
        var _date = document.getElementById('date_field-' + id);
        var _edit = document.getElementById('edit-' + id);

        var message = document.getElementById('message-' + id);
        var title = document.getElementById('title-' + id);
        var text = document.getElementById('text-' + id);
        var date = document.getElementById('date-' + id);
        var edit = document.getElementById('edit-' + id);

        var param = "token=" + getCookie("token") + "&id=" + encodeURIComponent(id) + "&title=" + encodeURIComponent(_title.value) + "&text=" + encodeURIComponent(_text.value) + "&date=" + encodeURIComponent(parseDate(_date.value));

        message.style.opacity = 0;

        sendPOST('functions.php?function=editNews', param, function (result) {

            if (result == 'ok') {

                setTimeout(function () {

                    title.innerHTML = _title.value;
                    text.innerHTML = _text.value;
                    date.innerHTML = parseNewsDate(_date.value);

                    edit.style.background = 'url(edit.png)'
                    edit.style.backgroundSize = 'contain';
                    edit.style.backgroundRepeat = 'no-repeat';
                    edit.style.backgroundPosition = 'center';

                    edit.onclick = function () {
                        news.edit(id);
                    };

                    setTimeout(function () {

                        message.style.opacity = 1;

                    }, 290);

                }, 100);

            } else {

                setTimeout(function () {

                    message.style.opacity = 1;

                }, 390);
            }

        });

    },

    delete: function (id) {

        console.log(id);

        if (!confirm("Вы уверены, что хотите удалить эту новость?")) {

            return false;

        }

        var message = document.getElementById('message-' + id);

        message.style.opacity = 0;

        sendPOST('functions.php?function=deleteNews', "token=" + getCookie("token") + "&id=" + id, function (result) {

            console.log(result);

            if (result == 'ok') {

                setTimeout(function () {

                    document.getElementById('news').removeChild(message);

                    setTimeout(function () {

                        message.style.opacity = 1;

                    }, 290);

                }, 100);

            } else {

                setTimeout(function () {

                    message.style.opacity = 1;

                }, 390);
            }

        });


    },

};

var message = {

    chooseGroups: {

        loaded: false,

        count: 0,

        open: function () {

            var frame = document.getElementById('groupsFrame');

            var blur = document.getElementById('blur');

            blur.style.display = 'block';

            blur.onclick = function () {
                message.chooseGroups.close();
            };

            setTimeout(function () {

                frame.style.display = 'flex';

            }, 490);

            var list = document.getElementById('groupList');

            if (!this.loaded) {

                this.loaded = true;

                sendPOST("functions.php?function=getGroups", "", function (json) {

                    var groups = JSON.parse(json);

                    list.innerHTML = '';

                    for (i = 0; i < groups.length; i++) {

                        message.chooseGroups.count++;

                        var elem = document.createElement('div');
                        elem.id = 'groupEl-' + i;
                        elem.className = 'group';

                        var checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = 'group-' + i;
                        checkbox.value = groups[i];
                        checkbox.name = "group-" + i;

                        var label = document.createElement('label');
                        label.for = 'group-' + i;
                        label.innerHTML = groups[i];

                        elem.appendChild(checkbox);
                        elem.appendChild(label);

                        list.appendChild(elem);


                    }


                });

            }

        },

        close: function () {

            var frame = document.getElementById('groupsFrame');

            var blur = document.getElementById('blur');

            blur.style.animation = 'fadeOut 0.3s 1';
            frame.style.animation = 'zoomOut 0.3s 1';

            document.getElementById('sendMessageSelect').value = 'all';

            setTimeout(function () {

                frame.style.display = 'none';
                frame.style.animation = 'zoomIn 0.4s 1';

                blur.style.display = 'none';
                blur.style.animation = 'fadeIn 0.6s 1';

            }, 290);

        },

        confirm: function () {

            message.chooseGroups.choosed = [];

            // смотрим группы.

            var f = 0;

            var str = "Выбрано: ";

            var firstGroup = true;

            for (i = 0; i < message.chooseGroups.count; i++) {

                if (document.getElementById('group-' + i).checked) {

                    message.chooseGroups.choosed[f++] = document.getElementById('group-' + i).value;

                    if (!firstGroup) {
                        str += ", ";
                    }

                    str += document.getElementById('group-' + i).value;

                    if (firstGroup) {

                        firstGroup = !firstGroup;

                    }

                }

            }

            this.count = f;

            // смотрим учителей

            if (document.getElementById('sendTeachers').checked) {

                message.chooseGroups.toTeachers = true;

                str += ", а также учителя";

            } else {

                message.chooseGroups.toTeachers = false;

            }

            if (f == 0 && !message.chooseGroups.toTeachers) {

                document.getElementById('sendMessageSelect').value = 'all';
                str = "";

            } else if (f == 0 && message.chooseGroups.toTeachers) {

                str = "Выбраны только учителя";

            }

            document.getElementById('choosed').innerHTML = str;

            // закрываем 

            var frame = document.getElementById('groupsFrame');

            var blur = document.getElementById('blur');

            blur.style.animation = 'fadeOut 0.3s 1';
            frame.style.animation = 'zoomOut 0.3s 1';

            setTimeout(function () {

                frame.style.display = 'none';
                frame.style.animation = 'zoomIn 0.4s 1';

                blur.style.display = 'none';
                blur.style.animation = 'fadeIn 0.6s 1';

            }, 290);

            return false;

        },

        toTeachers: false,

        choosed: [],

    },

    setVK_ID: {

        open: function () {

            var frame = document.getElementById('inputVK_ID');

            var blur = document.getElementById('blur');

            blur.style.display = 'block';

            blur.onclick = function () {
                message.setVK_ID.close();
            };

            setTimeout(function () {

                frame.style.display = 'flex';

            }, 490);


        },

        close: function () {

            var frame = document.getElementById('inputVK_ID');

            var blur = document.getElementById('blur');

            blur.style.animation = 'fadeOut 0.3s 1';
            frame.style.animation = 'zoomOut 0.3s 1';

            document.getElementById('sendMessageSelect').value = 'all';

            setTimeout(function () {

                frame.style.display = 'none';
                frame.style.animation = 'zoomIn 0.4s 1';

                blur.style.display = 'none';
                blur.style.animation = 'fadeIn 0.6s 1';

            }, 290);

        },

        timeout: null,

        onchange: function () {

            clearTimeout(message.setVK_ID.timeout);

            setTimeout(function () {

                message.setVK_ID.updateTarget();

            }, 1500);

        },

        block: false,

        updateTarget: function () {

            if (message.setVK_ID.block) {
                return false;
            }

            message.setVK_ID.block = true;

            setTimeout(function () {

                message.setVK_ID.block = false;

            }, 1500);

            var avatar = document.getElementById('VK_IDAvatar');

            var name = document.getElementById('VK_IDName');

            var image = document.createElement('img');

            var id = document.getElementById('VK_IDField')

            avatar.style.opacity = 0;

            console.log('updating');

            sendPOST("/bot.php?getInfo=true", "id=" + id.value + "&token=" + getCookie("token"), function (result) {


                var info = JSON.parse(result);

                image.style.width = '40%';
                image.style.height = '40%';
                image.style.objectFit = 'cover';
                image.style.transform = 'translateX(75%)';

                try {

                    var check = info['response'].length;

                } catch (err) {
                    var check = 'error';
                }

                if (info['error'] || check == 0) {

                    if (check == 0) {

                        name.innerHTML = ' ';

                    } else {

                        name.innerHTML = 'Пользователь не найден';

                    }

                    image.src = 'dog_vk_deal.jpg';
                    image.style.filter = 'saturate(50%)';
                    document.getElementById('VK_IDSendBtn').disabled = true;

                } else {

                    image.src = info['response'][0]['photo_400_orig'];

                    name.innerHTML = info['response'][0]['first_name'] + ' ' + info['response'][0]['last_name'];


                    if (name.innerHTML == 'DELETED ') {

                        document.getElementById('VK_IDSendBtn').disabled = true;

                    } else {

                        document.getElementById('VK_IDSendBtn').disabled = false;

                    }

                }

                setTimeout(function () {

                    avatar.innerHTML = '';

                    avatar.appendChild(image);
                    avatar.style.opacity = 1;

                }, 700);

            });

        },


    },

    mode: 'all',

    send: function () {

        var param = null;

        switch (message.mode) {

            case 'all':
                break;
            case 'by_group':

                var url = "https://bot.zhrt.ru/bot.php?send=true&groupsCount=" + message.chooseGroups.count;

                for (k = 0; k < message.chooseGroups.count; k++) {

                    url += "&group-" + k + "=" + message.chooseGroups.choosed[k];

                }

                if (message.chooseGroups.toTeachers) {

                    url += "&teachers=true";

                }

                console.log(url);

                sendPOST(url, "token=" + getCookie('token'), function (result) {
                    console.log(result);
                });

                break;
            case 'by_VK_ID':
                break;

            default:
                console.error("Error on sending: unknown mode choosed - " + message.mode);
                break;

        };



    },

    onchange: function () {

        var select = document.getElementById('sendMessageSelect');

        switch (select.value) {

            case 'all':

                document.getElementById('choosed').innerHTML = '';
                message.mode = 'all';

                break;

            case 'by_group':

                message.mode = 'by_group';
                message.chooseGroups.open();

                break;

            case 'by_VK_ID':

                document.getElementById('choosed').innerHTML = '';
                message.mode = 'by_VK_ID';
                message.setVK_ID.open();

                break;

            default:

                console.error("Unexpected select value: " + select.value);

                break;

        }

    }


};

// setup
document.getElementById('addNewsBtn').onclick = addNews.send;
document.getElementById('sendMessageSelect').onchange = message.onchange;
document.getElementById('sendMessageBtn').onclick = message.send;
document.getElementById('VK_IDField').onkeyup = message.setVK_ID.onchange;
