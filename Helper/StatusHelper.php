<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\ResourceConnection;

/**
 * Used by the backend application
 * to manage the status like
 * lastsynced at, did the initial sync
 * happen and so on.
 *
 * @package Spod\Sync\Helper
 */
class StatusHelper
{
    const SPODSYNC_STATUS_TABLE = 'spodsync_status';
    const SPODSYNC_STATUS_DEFAULT_CONNECTION = 'default';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get and set webhook secret. Not using the Magento 2 config
     * to avoid caching issues.
     */
    public function getWebhookSecret()
    {
        return $this->getStatusValue('webhook_secret');
    }

    public function setWebhookSecret($secret)
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET webhook_secret = :SECRET WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql, ['SECRET' => $secret, 'connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    /**
     * Get and set API token. Not using the Magento 2 config
     * to avoid caching issues.
     */
    public function getApiToken()
    {
        return $this->getStatusValue('api_token');
    }

    public function setApiToken($apiToken)
    {
        $this->reinitDefaultConnection();
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET api_token = :TOKEN WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql, ['TOKEN' => $apiToken, 'connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    /**
     * Set when a valid API key was entered
     * for the first time. Used for showing
     * the status in the frontend.
     *
     * @return false|mixed
     */
    public function getInstallDate()
    {
        return $this->getStatusValue('installed_at');
    }

    public function setInstallDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET installed_at = NOW() WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    /**
     * Did the initial sync already start?
     *
     * @return false|mixed
     */
    public function getInitialSyncStartDate()
    {
        return $this->getStatusValue('initsync_start_at');
    }

    public function setInitialSyncStartDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET initsync_start_at = NOW() WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    /**
     * Was the initial sync already finished?
     *
     * @return false|mixed
     */
    public function getInitialSyncEndDate()
    {
        return $this->getStatusValue('initsync_end_at');
    }

    public function setInitialSyncEndDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET initsync_end_at = NOW() WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    /**
     * Last synchronization.
     *
     * @return false|mixed
     */
    public function getLastsyncDate()
    {
        return $this->getStatusValue('lastsync_at');
    }

    public function setLastsyncDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET lastsync_at = NOW() WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    /**
     * Helper method used to access the status
     * table with raw SQL.
     *
     * @return false|mixed
     */
    private function getStatusValue($valueKey)
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('SELECT %s FROM %s WHERE connection = :connection', $valueKey, $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $result = $connection->fetchAll($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
        $result = current($result);

        if (isset($result[$valueKey])) {
            return $result[$valueKey];
        } else {
            return false;
        }
    }

    /**
     * Resets all dates when the disconnect button is used
     * in the backend.
     */
    public function resetStatusDates()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf(
            'UPDATE %s SET initsync_start_at = NULL, initsync_end_at = NULL, lastsync_at = NULL, installed_at = NULL WHERE connection = :connection',
            $connection->getTableName(self::SPODSYNC_STATUS_TABLE)
        );
        $connection->query($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
    }

    private function reinitDefaultConnection()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('SELECT * FROM %s WHERE connection = :connection', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $result = $connection->fetchRow($sql, ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]);
        if (!$result) {
            $connection->query(
                sprintf('INSERT INTO %s (`connection`) VALUES (:connection)', $connection->getTableName(self::SPODSYNC_STATUS_TABLE)),
                ['connection' => self::SPODSYNC_STATUS_DEFAULT_CONNECTION]
            );
        }
    }
}
