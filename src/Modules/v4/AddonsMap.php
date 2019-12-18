<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\v4;

use Fyi\Cms\Modules\Connection\Source;

class AddonsMap
{
    protected $connection;
    protected $migrationSync;
    
    public function __construct($connection)
	{
        $this->connection = $connection;
        $this->migrationSync = new MigrationSync($this->connection);
    }

    /**
     * Insert a new addon on destiny db
     * If on the database exists an addon with the same alias,
     * it is return the id of the existing addon.
     * 
     * It's not considering multilanguage
     * 
     * @param {array} $params
     * @return {int} $id
     */
    public function create($params)
    {
        // check sync
        $sync = $this->migrationSync->get("addonMap", $params["id_maps"], null, ["limit" => 1]);
        
        if (count($sync) > 0)
        {
            return $sync[0]["id_cms_v4"];
        }
        
        // Insert event...
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder
            ->insert("addon_maps")
            ->setValue("address", "\"". $params["address"] . "\"")
            ->setValue("locality", "\"". $params["locality"] . "\"")
            ->setValue("latitude", "\"". $params["latitude"] . "\"")
            ->setValue("longitude", "\"". $params["longitude"] . "\"")
            ->setValue("country", "\"". $params["country"] . "\"")
            ->setValue("zipcode", "\"". $params["zipcode"] . "\"")
            ->setValue("content_id", $params["content_id"]);


        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        // add to migratio nsync
        $this->migrationSync->create("addonMap", $params["id_maps"], $id);

        return $id;
    }
}
