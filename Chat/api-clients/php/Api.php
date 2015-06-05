<?php
/**
 * Description of ChatApi
 *
 * @author Eugene Gruzdev <yugeon.ru at gmail.com>
 */

require_once ROOT . 'libs/php-curl-class/Curl.php';
use \Curl\Curl;


class Chat_Api implements Chat_Api_Interface
{
    protected $curl = null;
    protected $options = array(
        'base_url' => '',
        'api_id' => '',
        'api_token' => ''
    );
    protected $baseUrl = '';
    protected $lastAction = '';
    protected $lastData = '';
    protected $lastHttpErrorCode = '';
    protected $lastCurlErrorCode = '';
    protected $lastErrorMsg = '';

    /**
     * init api
     */
    function __construct($options = array())
    {
        $this->setOptions($options);
        $this->curl = new Curl();
    }

    public function getCurl()
    {
        return $this->curl;
    }

    public function setOptions($options)
    {
        $this->options = array_replace($this->options, $options);
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function getOption($name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : false;
    }

    public function authorization($apiId = '', $apiToken = '')
    {
        if (empty($apiId))
        {
            $apiId = $this->getOption('api_id');
        }

        if (empty($apiToken))
        {
            $apiToken = $this->getOption('api_token');
        }

        $this->curl->setHeader('authorization',  $apiId . ':' . $apiToken);
    }

    protected function setApiUrl($apiUrl)
    {
        $this->curl->setUrl($this->getOption('base_url') . $apiUrl);
        return true;
    }

    public function registerUser($userId, $nickname, $authToken, $regTime)
    {
        $this->setLastAction('registerUser');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/user');
        $this->curl->post(array(
            'user_id' => $userId,
            'nickname' => $nickname,
            'token' => $authToken,
            'regtime' => $regTime
        ));

        return $this->processRequest();
    }

    public function updateUser($userId, $nickname, $authToken)
    {
        $this->setLastAction('updateUser');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/user/'. $userId);
        $this->curl->put(array(
            'nickname' => $nickname,
            'token' => $authToken
        ));

        return $this->processRequest();
    }

    public function unregisterUser($userId)
    {
        $this->setLastAction('unregisterUser');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/user/' . $userId);
        $this->curl->delete(array());

        return $this->processRequest();
    }

    public function grantUserRights($userId, $channelId, $rights)
    {
        $this->setLastAction('grantUserRights');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/user/' . $userId . '/grant');
        $this->curl->post(array(
            'channel_id' => $channelId,
            'rights' => $rights
        ));

        return $this->processRequest();
    }

    public function addChannel($channelId, $channelKey, $channelType, $channelTitle, $owners = array())
    {
        $this->setLastAction('addChannel');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/channel');
        $this->curl->post(array(
            'channel_id' => $channelId,
            'channel_key' => $channelKey,
            'channel_type' => $channelType,
            'channel_title' => $channelTitle,
            'owners' => $owners
        ));

        return $this->processRequest();
    }

    public function updateChannelTitle($channelId, $channelTitle)
    {
        $this->setLastAction('updateChannelTitle');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/channel/' . $channelId);
        $this->curl->put(array(
            'channel_title' => $channelTitle
        ));

        return $this->processRequest();
    }

    public function addChannelOwners($channelId, $owners = array())
    {
        $this->setLastAction('addChannelOwners');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/channel/' . $channelId . '/owners');
        $this->curl->post(array(
            'owners' => $owners
        ));

        return $this->processRequest();
    }

    public function createGroup($groupKey, $groupTitle = '')
    {
        $this->setLastAction('createGroup');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/group');
        $this->curl->post(array(
            'group_key' => $groupKey,
            'group_title' => $groupTitle
        ));

        return $this->processRequest();
    }

    public function assignUserToGroup($userId, $groupKey, $channelId)
    {
        $this->setLastAction('assignUserToGroup');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/user/' . $userId . '/group');
        $this->curl->post(array(
            'group_key' => $groupKey,
            'channel_id' => $channelId
        ));

        return $this->processRequest();
    }

    public function unassignUserFromGroup($userId, $groupKey, $channelId)
    {
        $this->setLastAction('unassignUserToGroup');
        $this->setLastData(func_get_args());
        $this->setApiUrl('/user/' . $userId . '/group');
        $this->curl->delete(array(
            'group_key' => $groupKey,
            'channel_id' => $channelId
        ));

        return $this->processRequest();
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * */

    protected function processRequest()
    {
        if ($this->curl->error)
        {
            $this->setLastHttpErrorCode($this->curl->http_status_code);
            $this->setLastCurlErrorCode($this->curl->curl_error_code);

            if ($this->curl->curl_error_code > 0)
            {
                $this->setLastErrorMsg($this->curl->curl_error_message);
            }
            else
            {
                $this->setLastErrorMsg($this->curl->raw_response);
            }

            return false;
        }
        else
        {
            return true;
        }
    }

    public function getLastAction()
    {
        return $this->lastAction;
    }

    protected function setLastAction($lastAction)
    {
        $this->lastAction = $lastAction;
    }

    public function getLastData()
    {
        return $this->lastData;
    }

    protected function setLastData($lastData)
    {
        $this->lastData = $lastData;
    }

    public function getLastHttpErrorCode()
    {
        return $this->lastHttpErrorCode;
    }

    protected function setLastHttpErrorCode($lastErrorCode)
    {
        $this->lastHttpErrorCode = $lastErrorCode;
    }

    public function getLastCurlErrorCode()
    {
        return $this->lastCurlErrorCode;
    }

    public function setLastCurlErrorCode($lastCurlErrorCode)
    {
        $this->lastCurlErrorCode = $lastCurlErrorCode;
    }

    public function getLastErrorMsg()
    {
        return $this->lastErrorMsg;
    }

    protected function setLastErrorMsg($lastErrorMsg)
    {
        $this->lastErrorMsg = $lastErrorMsg;
    }

}
