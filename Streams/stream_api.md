# Описание API для стримов

Changelog:

    [+] 23.11.2014 возможность получения списка подписчиков
    [+] 11.11.2014 добавлен флаг premium
    [+] 28.06.2014 id канала совпадает с id чата, который теперь можно получить по ссылке http://goodgame.ru/chat/[id канала]
    [+] 14.04.2014 В методы getggchannelstatus и getchannelstatus добавлено поле games со списком прикрепленных игр
    [!] 28.11.2013 Метод getupcomingbroadcasts - переименован в getupcomingbroadcast. Теперь возвращает только один ближайший анонс. Изменен ответ.
    [+] 22.10.2013 Добавлен метод getupcomingbroadcasts - для получения информации о предстоящих трансляциях (анонсы).
    [+] 18.02.2013 Добавлен метод getggchannelstatus - для получения информации о плеерах gg.
    [+] 31.01.2013 В вовращаемый ответ добавлен параметр key, значение которого совпадает с параметром запроса, указанного в id.
    [+] 31.01.2013 Добавлена поддержка символьного ключа канала, вместо  числового идентификатора. Рекомендуется использовать именно его.

# Для получения информации о трансляциях на GG предоставляет следующий интерфейс:

    http://goodgame.ru/api/getchannelstatus

Метод возвращает первый плеер, привязанный к каналу пользователя (необязательно плеер goodgame). Получение информацию о статусе gg-плееров описано ниже.

## Параметры (POST или GET):

* `id` Идентификатор трансляции. Можно передавать идентификаторы нескольких трансляций через запятую. Рекомендуется использовать символьный ключ вместо числовых идентификаторов (пример, Miker, Hireling)
* `fmt` Формат возвращаемых данных. Поддерживается 3 формата — xml, json, serialize. По-умолчанию используется xml формат

## Возвращаемые данные:

* `stream_id` - Внутренний идентификатор трансляции
* `key` - Идентификатор трансляции, указанный в запросе
* `premium` - Тип трансляции (премуим/обычная)
* `title` - Название трансляции
* `status` - Статус трансляции
* `viewers` - Количество зрителей
* `usersinchat` - Количество пользователей в чате
* `embed` - HTML-код плеера
* `img` - Полноразмерное изображение трансляции (максимальный размер 1280x720)
* `thumb` - Уменьшенное изображение трансляции (195х110)
* `description` - Описание трансляции
* `games` - Список названий игр, прикрепленных к каналу, через запятую
* `url` - Адрес страницы трансляции на сайте goodgame.ru


## Примеры использования:

Запрос:

    http://goodgame.ru/api/getchannelstatus?id=1022

Ответ:

```xml
<?xml version="1.0"?>
<root>
<stream id="1022">
<stream_id>1022</stream_id>
<key>1022</key>
<premium>true</premium>
<title>Lokki7</title>
<status>Live</status>
<viewers>123</viewers>
<usersinchat>100</usersinchat>
<embed><![CDATA[<iframe frameborder="0" width="800" height="450" src="http://www.goodgame.ru/player2.php?1022"></iframe>]]></embed>
<img>http://goodgame.ru/thumbs/1022.large.jpg</img>
<thumb>http://goodgame.ru/thumbs/1022.jpg</thumb>
<description><![CDATA[<p>test123</p>]]></description>
<games>Space Invaders Get Even, Gears of War 3, Outland</games>
<url>http://goodgame.ru/allstreams2.php?ocd=view</url>
</stream>
</root>
```

Запрос:

    http://goodgame.ru/api/getchannelstatus?id=Miker&fmt=json

Ответ:

```json
    {"5":{"stream_id":"5","key":"Miker","premium":"true","title":"Miker","status":"Live","viewers":"1465","usersinchat":"646","embed":"<iframe frameborder=\"0\" width=\"800\" height=\"450px\" src=\"http:\/\/goodgame.ru\/player?6\"><\/iframe>","img":"http:\/\/goodgame.ru\/files\/logotypes\/ch_5_39Tp.png","thumb":"","description":"","games":"Space Invaders Get Even, Gears of War 3, Outland"
    ,"url":"http:\/\/goodgame.ru\/channel\/Miker\/"}}
```

Запрос:

    http://goodgame.ru/api/getchannelstatus?id=5,1022&fmt=serialize

Ответ:

    a:2:{i:5;a:11:{s:9:"stream_id";s:1:"5";s:3:"key";s:1:"5";s:7:"premium";s:4:"true";s:5:"title";s:5:"Miker";s:6:"status";s:4:"Live";s:7:"viewers";s:4:"1455";s:11:"usersinchat";s:3:"642";s:5:"embed";s:94:"<iframe frameborder="0" width="800" height="450px" src="http://goodgame.ru/player?6"></iframe>";s:3:"img";s:48:"http://goodgame.ru/files/logotypes/ch_5_39Tp.png";s:5:"thumb";s:0:"";s:11:"description";s:0:"";s:3:"url";s:33:"http://goodgame.ru/channel/Miker/";}i:1022;a:11:{s:9:"stream_id";s:4:"1022";s:3:"key";s:4:"1022";s:7:"premium";s:4:"true";s:5:"title";s:6:"Lokki7";s:6:"status";s:4:"Dead";s:7:"viewers";s:1:"0";s:11:"usersinchat";s:1:"1";s:5:"embed";s:98:"<iframe frameborder="0" width="800" height="450px" src="http://goodgame.ru/player2?1022"></iframe>";s:3:"img";s:0:"";s:5:"thumb";s:0:"";s:11:"description";s:14:"<p>test123</p>";s:3:"url";s:34:"http://goodgame.ru/channel/lokki7/";}}

# Для получения информации о статусе плееров GoodGame, используется следующий URL.

    http://goodgame.ru/api/getggchannelstatus

## Параметры (POST или GET):

* `id` Идентификатор трансляции. Можно передавать идентификаторы нескольких трансляций через запятую. Рекомендуется использовать символьный ключ вместо числовых идентификаторов (пример, Miker, Hireling)
* `fmt` Формат возвращаемых данных. Поддерживается 3 формата — xml, json, serialize. По-умолчанию используется xml формат

## Возвращаемые данные:

* `stream_id` - Внутренний идентификатор трансляции
* `key` - Идентификатор трансляции, указанный в запросе
* `premium` - Тип трансляции (премуим/обычная)
* `title` - Название трансляции/канала
* `status` - Статус плеера
* `viewers` - Количество зрителей, просматривающих этот плеер
* `usersinchat` - Количество пользователей в чате
* `embed` - HTML-код плеера
* `img` - Полноразмерное изображение трансляции (максимальный размер 1280x720)
* `thumb` - Уменьшенное изображение трансляции (195х110)
* `description` - Описание трансляции
* `games` - Список названий игр, прикрепленных к каналу, через запятую
* `url` - Адрес страницы трансляции на сайте goodgame.ru

## Примеры использования:

Запрос:

    http://goodgame.ru/api/getggchannelstatus?id=Miker,Pomi&fmt=json

Ответ:

```json
    {"5":{"stream_id":"5","key":"Miker","premium":"true","title":"XCOM","status":"Live","viewers":"880","usersinchat":"531","embed":"<iframe frameborder=\"0\" width=\"800px\" height=\"450px\" src=\"http:\/\/goodgame.ru\/player?6\"><\/iframe>","img":"http:\/\/goodgame.ru\/files\/logotypes\/ch_5_39Tp.png","thumb":"","description":"","url":"http:\/\/goodgame.ru\/channel\/Miker\/"},
    "1644":{"stream_id":"1644","key":"Pomi","premium":"true","title":"$ 2000 HotS KotH by GD Studio ","status":"Dead","viewers":"12","usersinchat":"5","embed":"<iframe frameborder=\"0\" width=\"800px\" height=\"450px\" src=\"http:\/\/goodgame.ru\/player?pomi\"><\/iframe>","img":"http:\/\/goodgame.ru\/files\/logotypes\/ch_1644_oRqp.jpg","thumb":"","description":"<p><a href=\"http:\/\/goodgame.ru\/channel\/showmatch\/\" rel=\"nofollow\">http:\/\/goodgame.ru\/channel\/showmatch\/<\/a> - \u043f\u043e\u043c\u043e\u0433\u0430\u0435\u043c \u043e\u0440\u0433\u0430\u043d\u0438\u0437\u0430\u0446\u0438\u0438 \u0448\u043e\u0443\u043c\u0430\u0442\u0447\u0435\u0439<\/p>\n<p>\u00a0<\/p>\n<p>\u043c\u043e\u0439 \u043a\u043e\u0448\u0435\u043b\u0451\u043a\u00a0<span>R351383023689\u00a0<\/span><\/p>\n<p><span>\u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0Z272683067160<\/span><\/p>\n<p><span><span>\u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0 \u00a0410011704670305 \u042f\u043d\u0434\u0435\u043a\u0441 \u0434\u0435\u043d\u044c\u0433\u0438<\/span><\/span><\/p>\n<p>\u00a0<\/p>\n<p><a href=\"https:\/\/docs.google.com\/presentation\/d\/1OdHxioFOBKc-u6qJmE-3bOzEZlkuNOOafmwe56oHtrw\/present#slide=id.gf0a79b3_0_39\" rel=\"nofollow\">https:\/\/docs.google.com\/presentation\/d\/1OdHxioFOBKc-u6qJmE-3bOzEZlkuNOOafmwe56oHtrw\/present#slide=id.gf0a79b3_0_39<\/a><\/p>\n<p>\u00a0- \u041f\u043e\u0434\u0434\u0435\u0440\u0436\u0438 \u0441\u0430\u0439\u0442! <span>\u041f\u043e\u0434\u043f\u0438\u0441\u044b\u0432\u0430\u0439\u0441\u044f \u043d\u0430 \u043f\u0440\u0435\u043c\u0438\u0443\u043c \u0442\u0440\u0430\u043d\u0441\u043b\u044f\u0446\u0438\u0438! (\u043d\u0435\u0442 \u0440\u0435\u043a\u043b\u0430\u043c\u044b :D)<br \/><\/span><\/p>\n<p><span>\u00a0<\/span><\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>\n<p>\u00a0<\/p>","url":"http:\/\/goodgame.ru\/channel\/Pomi\/"}}
```

# Для получения информации о предстоящей трансляции (анонс)

    http://goodgame.ru/api/getupcomingbroadcast

## Параметры (POST или GET):

* `id` Идентификатор канала. Можно передавать идентификаторы нескольких каналов через запятую. Рекомендуется использовать символьный ключ (пример, Miker, Hireling, вместо числовых идентификаторов)
* `fmt` Формат возвращаемых данных. Поддерживается 3 формата — xml, json, serialize. По-умолчанию используется xml формат

## Возвращаемые данные:

* `stream_id` - Внутренний идентификатор канала
* `stream_key` - Идентификатор канала, указанный в запросе
* `stream_name` - Текущее название канала, с учетом текущего анонса
* `stream_status` - Статус канала (Live, Dead)
* `broadcast_key` - Идентификатор анонса
* `broadcast_url` - Адрес страницы анонса на сайте goodgame.ru
* `broadcast_title` - Название анонса
* `broadcast_start` - timestamp начала анонса
* `broadcast_end` - timestamp окончания анонса
* `broadcast_game` - Список названий игр, прикрепленных к анонсу, через запятую
* `broadcast_description` - Описание анонса
* `broadcast_logo` - Логотип анонса

## Примеры использования:

Запрос:

    http://goodgame.ru/api/getupcomingbroadcast?id=Hawk

Ответ:
```xml
<root>
<stream id="0">
<stream_id>24</stream_id>
<stream_key>Hawk</stream_key>
<stream_name>HawK</stream_name>
<stream_status>Dead</stream_status>
<broadcast_key>26</broadcast_key>
<broadcast_url>http://goodgame.ru/channel/HawK/26/</broadcast_url>
<broadcast_title>WCG 2013 Grand Final WarCraft III:TFT</broadcast_title>
<broadcast_start>1385701200</broadcast_start>
<broadcast_end>1385729400</broadcast_end>
<broadcast_games>Warcraft III: TFT</broadcast_games>
<broadcast_description><p align="center"><strong><span> </span></strong></p>
<p align="center"><strong><span>Расписание<br /></span></strong>(Время московское, GMT+4)</p>
</broadcast_description>
<broadcast_logo>http://goodgame.ru/files/logotypes/br_20997_jGjT_logo.jpg</broadcast_logo>
</stream>
</root>
```

# Для получения информации о трансляциях по конкретной игре

    http://goodgame.ru/api/getchannelsbygame

## Параметры (POST или GET):

* `game` Текстовый идентификатор игры

## Возвращаемые данные:

* `stream_id` - Внутренний идентификатор трансляции
* `key` - Идентификатор трансляции, указанный в запросе
* `title` - Название трансляции
* `status` - Статус трансляции
* `viewers` - Количество зрителей
* `usersinchat` - Количество пользователей в чате
* `embed` - HTML-код плеера
* `img` - Полноразмерное изображение трансляции (максимальный размер 1280x720)
* `thumb` - Уменьшенное изображение трансляции (195х110)
* `description` - Описание трансляции
* `games` - Список названий игр, прикрепленных к каналу, через запятую
* `url` - Адрес страницы трансляции на сайте goodgame.ru


## Примеры использования:

Запрос:

    http://goodgame.ru/api/getchannelsbygame?game=starcraft-ii-heart-of-the-swarm

Ответ:

```xml
<?xml version="1.0"?>
<root>
<stream id="6192">
<stream_id>6192</stream_id>
<key>Couguar</key>
<title>Сказки Аюра</title>
<status>Live</status>
<viewers>123</viewers>
<usersinchat>100</usersinchat>
<embed><![CDATA[<iframe frameborder="0" width="100%" height="100%" src="http://goodgame.ru/player?6192"></iframe>]]></embed>
<img>http://goodgame.ru/files/logotypes/br_48455_KR3f_orig.jpg</img>
<thumb/>
<description><![CDATA[<p>test123</p>]]></description>
<games>StarCraft II: Heart of the Swarm</games>
<url>http://goodgame.ru/channel/Couguar/</url>
</stream>
<stream id="52">
<stream_id>52</stream_id>
<key>52</key>
<title>iNcontrol (P)</title>
<status>Live</status>
<viewers>2235</viewers>
<usersinchat>12</usersinchat>
<embed>
<![CDATA[
<object type="application/x-shockwave-flash" width="100%" height="100%" id="live_embed_player_flash" data="http://www.twitch.tv/widgets/live_embed_player.swf?channel=incontroltv" bgcolor="#000000"><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><param name="allowNetworking" value="all" /><param name="wmode" value="opaque" /><param name="movie" value="http://www.twitch.tv/widgets/live_embed_player.swf" /><param name="flashvars" value="hostname=www.twitch.tv&channel=incontroltv&auto_play=false" /></object>
]]>
</embed>
<img>http://goodgame.ru/images/channel-logo.jpg</img>
<thumb>
http://static-cdn.jtvnw.net/previews-ttv/live_user_incontroltv-320x180.jpg
</thumb>
<description>
<![CDATA[ ]]>
</description>
<adult>0</adult>
<games>StarCraft II: Heart of the Swarm</games>
<url>http://goodgame.ru/channel/52/</url>
</stream>
</root>
```


# Получение токена авторизации

Для использования некоторых функций API необходимо пройти авторизацию.

    http://goodgame.ru/api/token

## Параметры (POST):

* `username` Ваш логин на сайте goodgame.ru
* `password` Ваш пароль

## Возвращаемые данные:

* `success` - флаг успешности авторизации
* `access_token` - токен для последующих запросов


# Список подписчиков

Вы можете получить список подписчиков вашего канала.

    http://goodgame.ru/api/getchannelsubscribers

## Параметры (POST):

* `oauth_token` - Токен, полученный при авторизации

## Возвращаемые данные:

* `success` - Флаг успеха
* `response` - Массив подписчиков, содержащий id и username пользователей

## Пример ответа:

Ответ:

```json
{
    "success":true,
    "response":[
        { "id":"5545", "username":"y32b4" },
        { "id":"52993", "username":"unlucky-irk" },
        { "id":"167969", "username":"Leonty" },
        { "id":"27080", "username":"nextg.loki" },
        { "id":"194701", "username":"Salyvador" },
        { "id":"55438", "username":"clearevil" },
        { "id":"666", "username":"lokki7" },
        { "id":"231", "username":"Spail" }
    ]
}
```