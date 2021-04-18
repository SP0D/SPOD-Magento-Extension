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
        $sql = sprintf('UPDATE %s SET installed_at = NOW()', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql);
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
        $sql = sprintf('UPDATE %s SET initsync_start_at = NOW()', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql);
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
        $sql = sprintf('UPDATE %s SET initsync_end_at = NOW()', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql);
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
        $sql = sprintf('UPDATE %s SET lastsync_at = NOW()', $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $connection->query($sql);
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
        $sql = sprintf('SELECT %s FROM %s', $valueKey, $connection->getTableName(self::SPODSYNC_STATUS_TABLE));
        $result = $connection->fetchAll($sql);
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
            'UPDATE %s SET initsync_start_at = NULL, initsync_end_at = NULL, lastsync_at = NULL, installed_at = NULL',
            $connection->getTableName(self::SPODSYNC_STATUS_TABLE)
        );
        $connection->query($sql);
    }
}
