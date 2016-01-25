# Введение

Этот документ описывает процедуру, как ваш сервис или приложение может выступать от лица зарегистрированного пользователя Goodgame,
используя API Goodgame, естественно с согласия самого пользователя.

Эта процедура построена на базе фреймворка [OAuth 2.0]
Почитать об OAuth 2.0 простым и понятным языком можно [тут]

[OAuth 2.0]:http://oauth.net/2/
[тут]:http://habrahabr.ru/company/mailru/blog/115163/

# Регистрация вашего приложения

Прежде всего необходимо зарегистрировать ваше приложение или сервис в своем профиле на сайте http://goodgame.ru, вкладка OAuth 2 Приложения.
Вы должны быть залогинены под своей учетной записью.

* Client ID - идентификатор вашего приложения, строка, максимальная длина 80 символов. Вводите понятные конечным пользователям идентификаторы. Можно на русском.
* Redirect URI - URI куда будет перенаправлен пользователь Goodgame, после того как подтвердит авторизацию вашего приложения на использование своего аккаунта. Если оставить пустым, то перенаправление будет на служебную страницу http://api2.goodgame.ru/oauth/receivecode
* Client Secret - пароль для вашего идентификатора (должны знать только вы).

Для получения доступа к защищенному API Goodgame ваше приложение или сервис должны получить `access_token` - идентификатор, подтверждающий что пользователь дал согласие выполнять запросы от его имени.

# Получение Access Token'а

API Goodgame использует два сценария получения `access_token`'а:

* Web-server application
* Browser-based application

## Web-server application

Этот сценарий подходит для случаев, когда ваше приложение или сервис может безопасно хранить ваш __Client Secret__.
Используя наше API вы можете получить __access token__ пройдя по следующим 3 этапам.

### 1) Запрос кода авторизации

Используя браузер, вы должны перенаправить пользователя на страницу

    http://api2.goodgame.ru/oauth/authorize
        ?response_type=code
        &client_id=[Ваш Client ID]
        &redirect_uri=[Redirect URI который вы указали при регистрации приложения]
        &scope=[список ограничений, нужных вашему приложению]
        &state=[любой идентификатор, сгенерированный вами]

Где, `scope` - это список, разделенный через пробел, разрешений, которые ваше приложение запрашивает у пользователя.
`state` - это любой сгенерированный вами идентификатор, необходим для избежания CSRF-атак на ваше приложение. Передается в неизменном виде при редиректе на ваш Redirect URI.

Эта страница потребует от пользователя быть залогиненым на Goodgame.ru, после чего пользователь может подтвердить или отклонить авторизацию вашего приложения.

### 2) Подтверждение авторизации

После подтверждения пользователем авторизации вашего приложения, он будет перенаправлен на страницу

    [Ваш Redirect Uri]?code=[Authorization Code]&state=[указанынй вами в предыдущем запросе параметр state]

### 3) Запрос Access Token


В данный момент вы имеете __Authorization Code__, который в течении 30 секунд вы должны обменять на __Access Token__.
Делается это с помощью POST-запроса на URI http://api2.goodgame.ru/oauth

    POST /oauth HTTP/1.1
    Accept: application/json
    Content-Type: application/json

    {
        "redirect_uri": "[Redirect URI указанный при регистрации]",
        "client_id": "[Client ID вашего приложения]",
        "client_secret": "[Client Secret вашего приложения]",
        "code": "[Authorization Code]",
        "grant_type" : "authorization_code"
    }

В результате вы должны получить примерно следующий ответ:

    HTTP/1.1 200 OK
    Content-Type: application/json

    {
        "access_token":"[Access Token]",
        "expires_in":86400,
        "token_type":"Bearer",
        "scope":"[list of scopes]",
        "refresh_token":"[Refesh Token]"
    }

## Browser-based application

Этот сценарий является довольно распространённым, например в Javascript-приложениях, когда вы не можете безопасно хранить свой __Client Secret__.
Вместо  предыдущего сценария используется так называемый _Implicit Grant_ - когда вместо получения кода авторизации, ваше приложение сразу получает __Access Token__.

### 1) Запрос кода авторизации

Используя браузер, вы должны перенаправить пользователя на страницу

    http://api2.goodgame.ru/oauth/authorize
        ?response_type=token
        &client_id=[Ваш Client ID]
        &redirect_uri=[Redirect URI который вы указали при регистрации приложения]
        &scope=[список ограничений, нужных вашему приложению]
        &state=[любой идентификатор, сгенерированный вами]

Где, `scope` - это список, разделенный через пробел, разрешений, которые ваше приложение запрашивает у пользователя.
`state` - это любой сгенерированный вами идентификатор, необходим для избежания CSRF-атак на ваше приложение. Передается в неизменном виде при редиректе на ваш Redirect URI.

Эта страница потребует от пользователя быть залогиненым на Goodgame.ru, после чего пользователь может подтвердить или отклонить авторизацию вашего приложения.

### 2) Подтверждение авторизации

После подтверждения пользователем авторизации вашего приложения, он будет перенаправлен на страницу __Redirect URI__, используя URI-фрагмент #access_token в качестве __Access Token__

    [Ваш Redirect Uri]#access_token=[Access Token]&expires_in=86400&token_type=Bearer&scope=[list of scopes]&state=[указанынй вами в предыдущем запросе параметр state]

После используя например Javascript, вы можете получить __Access Token__

    // function to parse fragment parameters
    var parseQueryString = function( queryString ) {
        var params = {}, queries, temp, i, l;

        // Split into key/value pairs
        queries = queryString.split("&");

        // Convert the array of strings into an object
        for ( i = 0, l = queries.length; i < l; i++ ) {
            temp = queries[i].split('=');
            params[temp[0]] = temp[1];
        }
        return params;
    };

    // get token params from URL fragment
    var tokenParams = parseQueryString(window.location.hash.substr(1));

# Scopes

Когда ваше приложение запрашивает доступ у пользователя Goodgame, в параметре _scope_ вы должны указать список прав, разделенных пробелом, которые нужны вашему приложению.

Поддерживаются следующие значения для _scope_:

* channel.subscribers - необходим для получения списка подписчиков канала пользователя.
* channel.premiums - необходим для получения списка премиум подписчиков (актуально только для премиум плееров)
* channel.donations - необходим для получения истории доната
* chat.token - необходим для получения токена чата и токена для сервера уведомлений.

Указывайте только те scope, которые действительно нужны вашему приложению.

# Refresh Token

__Access Token__ имеет ограниченный по времени срок действия (в нашем случае 86400 секунд - 24 часа). Протокол OAuth 2.0 позволяет обновлять __Access Token__ без участия конечного пользователя, для этого в сценарии Web-server application возвращается __Refresh Token__.
Используя следующий запрос, вы можете обновить __Access Token__

    POST http://api2.goodgame.ru/oauth
    Accept: application/json
    Content-Type: application/json

    {
        "grant_type": "refresh_token",
        "refresh_token": "[Refresh Token]",
        "client_id": "[Ваш Client ID]",
        "client_secret": "[Client Secret вашего приложения]"
    }

ответ будет примерно следующий:

    HTTP/1.1 200 OK
    Content-Type: application/json

    {
        "access_token": "[новый Access Token]",
        "expires_in": 86400,
        "token_type": "Bearer"
        "scope": [список scope для которых выдавался Access Token],
        "refresh_token": "[новый Refresh Token]"
    }

Вместе с новым __Access Token__, который будет действителен в течении 24 часов, вы так же получите обновленный __Refresh Token__, который действителен в течении 30 дней.

Если при запросе API вы получете ответ 401 Unauthorized, а так же присутствует заголовок вида

    WWW-Authenticate:  Bearer realm="Service", error="expired_token", error_description="The access token provided has expired"

то самое время обновить __Access Token__ используя __Refresh Token__

# Доступ к защищенному API

Имея __Access Token__ вы можете использовать API Goodgame от лица пользователя. Для этого необходимо указать __Access Token__ в HTTP-заголовке

    GET /oauth/resource HTTP/1.1
    Accept: application/json
    Authorization: Bearer [Access Token]

или используя GET-параметр access_token

    http://api2.goodgame.ru/oauth/resource?access_token=[Access Token]

Ответ будет вида

    HTTP/1.1 200 OK
    Content-Type:  application/json

    {
        "success":true,
        "message":"You accessed my APIs!"
    }

Так же, API Goodgame предоставляет защищенный тестовый ресурс, не требующий указания scope. Ресурс доступен по URL `http://api2.goodgame.ru/oauth/resource`

