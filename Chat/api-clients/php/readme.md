# Класс для работы с GOODGAME CHAT API из PHP


## Требования

Класс использует библиотеку https://github.com/php-curl-class/php-curl-class

## Установка

* Закинуть библиотеку php-curl-class
* Закинуть файлы Interface.php и Api.php
* Проверить пути к библиотеке, интерфейсу.

## Описание

Данный класс реализует интерфейс для работы с [GOODGAME CHAT API v2](http://chat.goodgame.ru:8081/docs/index.html) из PHP

Основные этапы работы:

 * Создать экземпляр класса `Chat_Api`
 * Установить опцию `base_url` либо передав в конструкторе, либо использовать метод `setOption()`
 * При необходимости вызвать метод `authorization`, если требуется доступ к защищенной части API
 * Вызвать метод API
 * Получить результат работы вызова. Все вызовы возвращают `true` при успехе или `false` при неудаче.
 * При необходимости получить коды ошибок и сообщения об ошибках

## Пример

```php
    $chatApi = new Chat_Api(array(
        'base_url' => 'http://chat.goodgame.ru:8081/api',
        'api_id' => 'testuser',
        'api_token' => 'testpass'
    ));

    $chatApi->authorization();

    if (!$chatApi->addChannel(1 /* id канала */, 'asdf' /* key */, Chat_Api_Interface::CHANNEL, 'OLolo' /* title */, array(1/* id пользователя*/)))
    {
        echo "Last action : {$chatApi->getLastAction()},
              Last data: <pre>".print_r($chatApi->getLastData(), true)."</pre>,
              Http Err: {$chatApi->getLastHttpErrorCode()},
              Curl err: {$chatApi->getLastCurlErrorCode()},
              Err msg: {$chatApi->getLastErrorMsg()}";
    }
    else {
        echo "{$chatApi->getLastAction()} OK";
    }
```

## Обработка ошибок

Коды ошибок делятся на сетевые ошибки (CURL) и ошибки работы с API (HTTP).

Рекомендуется вести лог ошибочных вызовов, для того, что бы повторно обрабатывать ошибочные ситуации в автоматическом режиме.

Для этого создаем таблицу в БД:

```sql
    CREATE TABLE `chat_api_error_log` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `action` varchar(255) NOT NULL,
      `data` text,
      `datetime` int(11) NOT NULL,
      `error_msg` varchar(255) DEFAULT NULL,
      `http_code` varchar(45) DEFAULT NULL,
      `curl_code` varchar(45) DEFAULT NULL,
      `status` tinyint(4) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `status` (`status`),
      KEY `httpcode` (`http_code`),
      KEY `curlcode` (`curl_code`)
    ) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;
```

При получении результата `false` заполняем поля таблицы используя методы:

 * `getLastAction` - для получения имени метода
 * `getLastDate` - для получения массива с переданными аргументами (при сохранении в БД нужно сериализовать)
 * `getLastHttpErrorCode` - HTTP код ответа, 200 означает что операция успешна
 * `getLastCurlErrorCode` - CURL код, список возможных значений можно посмотреть тут http://curl.haxx.se/libcurl/c/libcurl-errors.html
 * `getLastErrorMsg` - Сообщение об ошибке, как правило API будет возвращать сообщение по которому можно понять что не так.

Для автоматического исправления сетевых ошибок добавляем в крон мониторинг и повторный вызов необходимых методов

```php

    $failedRequests = /* SELECT c.* FROM chat_api_error_log c WHERE curl_code > 0 AND status > -1 */;
    $chatApi = new Chat_Api();
    foreach ($failedRequests as $request)
    {
        $result = call_user_func_array(array($chatApi, $request['action']), unserialize($request['data']));

        if ($result)
        {
            // помечаем запись, что бы повторно не вызвать
        }
    }
```

Не сетевые ошибки (HTTP), лучше отслеживать в ручную, т.к. как правило, такие ошибки говорят о наличии некорекктных данных
