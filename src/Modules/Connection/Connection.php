<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\Connection;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

class Connection
{
    protected $connection;

    protected $queryBuilder;

    public function __construct($connection)
	{
        $driver =  $connection['driver'] === "mysql" ?  "pdo_mysql" : $connection['driver'];
        
        $connectionParams = array(
            'dbname' => $connection['db'],
            'user' => $connection['user'],
            'password' => $connection['password'],
            'host' => $connection['host'],
            'driver' => $driver,
            'charset' => "utf8",
            'COLLATE' => "utf8_general_ci",
        );

        $config = new \Doctrine\DBAL\Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);

        $ping = $this->connection->ping();

        
        if (!$ping)
        {
            dd($connectionParams, $ping);
            throw new \Fyi\Cms\Exceptions\CmsConnectionException;
        }
    }
}
