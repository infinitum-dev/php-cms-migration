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

class AddonsEvent
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
        $sync = $this->migrationSync->get("addonEvent", $params["id_event"], null, ["limit" => 1]);
        
        if (count($sync) > 0)
        {
            return $sync[0]["id_cms_v4"];
        }

        $start = new \DateTime($params["start"]);
        $end = new \DateTime($params["end"]);
        
        $start = $start->format("Y-m-d H:i:s");
        $end = $end->format("Y-m-d H:i:s");

        // Insert event...
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder
            ->insert("addon_events")
            ->setValue("start", "\"". $start . "\"")
            ->setValue("end", "\"". $end . "\"")
            ->setValue("timezone", "\"". $params["timezone"] . "\"")
            ->setValue("repeating", "\"". $params["repeating"] . "\"")
            ->setValue("content_id", $params["content_id"]);


        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        // add to migratio nsync
        $this->migrationSync->create("addonEvent", $params["id_event"], $id);

        return $id;
    }
}
