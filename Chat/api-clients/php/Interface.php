<?php

/**
 *
 * @author Eugene Gruzdev <yugeon.ru at gmail.com>
 */
interface Chat_Api_Interface
{
    // Channel Types
    CONST CHANNEL   = 0;
    CONST ROOM      = 1;

    // User Rights
    CONST RIGHTS_CASUAL         = 0;
    CONST RIGHTS_STREAM_MODER   = 10;
    CONST RIGHTS_STREAMER       = 20;
    CONST RIGHTS_MODERATOR      = 30;
    CONST RIGHTS_SMODERATOR     = 40;
    CONST RIGHTS_ADMIN          = 50;

    // credential roles
    CONST ROLE_ADMIN = 'admin';

    /**
     *
     * @param string $apiId
     * @param string $apiToken
     */
    public function authorization($apiId, $apiToken);

    /**
     *
     * @param int $userId
     * @param string $nickname
     * @param string $authToken
     * @param int $regTime
     * @return boolean
     */
    public function registerUser($userId, $nickname, $authToken, $regTime);

    /**
     *
     * @param int $userId
     * @param string $nickname
     * @param string $authToken
     * @return boolean
     */
    public function updateUser($userId, $nickname, $authToken);

    /**
     *
     * @param int $userId
     * @return boolean
     */
    public function unregisterUser($userId);

    /**
     *
     * @param int $userId
     * @param int $channelId
     * @param int $rights
     * @return boolean
     */
    public function grantUserRights($userId, $channelId, $rights);

    /**
     * @param int $channelId
     * @param string $channelKey
     * @param int $channelType
     * @param string $channelTitle
     * @param array $owners Optionally
     * @return boolean
     */
    public function addChannel($channelId, $channelKey, $channelType, $channelTitle, $owners = array());

    /**
     *
     * @param int $channelId
     * @param string $channelTitle
     * @return boolean
     */
    public function updateChannelTitle($channelId, $channelTitle);

    /**
     *
     * @param int $channelId
     * @param array $owners
     */
    public function addChannelOwners($channelId, $owners = array());

    /**
     *
     * @param int $start
     * @param int $count
     */
    public function getOnlineChannels($start = 0, $count = 0);

    /**
     *
     * @param string $groupKey
     * @param string $groupTitle
     * @return boolean
     */
    public function createGroup($groupKey, $groupTitle = '');

    /**
     *
     * @param int $userId
     * @param string $groupKey
     * @param int $channelId
     * @return boolean
     */
    public function assignUserToGroup($userId, $groupKey, $channelId);

    /**
     *
     * @param int $userId
     * @param string $groupKey
     * @param int $channelId
     * @return boolean
     */
    public function unassignUserFromGroup($userId, $groupKey, $channelId);

    /**
     *
     * @param int $userId
     * @param int $channelId
     * @param string $smileKey
     */
    public function addUserSmile($userId, $channelId, $smileKey);

    /**
     *
     * @param int $userId
     * @param int $channelId
     * @param string $smileKey
     */
    public function deleteUserSmile($userId, $channelId, $smileKey);

    /**
     *
     * @param int $siteId
     * @param string $authId
     * @param string $authToken
     * @param string $role
     * @return boolean
     */
    public function addSiteCredential($siteId, $authId, $authToken, $role);
}
