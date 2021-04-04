<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\ResourceConnection;

class StatusHelper
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function setInstallDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET installed_at = NOW()', $connection->getTableName('spodsync_status'));
        $connection->query($sql);
    }

    public function setInitialSyncStartDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET initsync_start_at = NOW()', $connection->getTableName('spodsync_status'));
        $connection->query($sql);
    }

    public function setInitialSyncEndDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET initsync_end_at = NOW()', $connection->getTableName('spodsync_status'));
        $connection->query($sql);
    }

    public function setLastsyncDate()
    {
        $connection = $this->resourceConnection->getConnection();
        $sql = sprintf('UPDATE %s SET lastsync_at = NOW()', $connection->getTableName('spodsync_status'));
        $connection->query($sql);
    }
}
