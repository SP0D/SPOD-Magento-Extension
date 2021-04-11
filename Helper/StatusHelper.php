<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\ResourceConnection;

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
