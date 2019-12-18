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

class Addons
{
    protected $connection;
    protected $migrationSync;
    
    public function __construct($connection)
	{
        $this->connection = $connection;
        $this->migrationSync = new MigrationSync($this->connection);
    }

    public function get($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addons");

        if (isset($params["alias"]))
        {
            $queryBuilder
                ->andwhere("alias = '" . $params["alias"] . "'");
        }
        
        if (isset($params["limit"]))
        {
            $queryBuilder
                ->setMaxResults($params["limit"]);
        }

        $results = [];
       
        $stmt = $this->connection->query($sql);
        while($object = $stmt->fetch())
        {
            array_push($results, $object);
        }

        return $results;
    }

    public function getAddonsType($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addonsTypes");

        if (isset($params["type_id"]))
        {
            $queryBuilder
                ->andwhere("type_id = '" . $params["type_id"] . "'");
        }

        if (isset($params["addon_id"]))
        {
            $queryBuilder
                ->andwhere("addon_id = '" . $params["addon_id"] . "'");
        }
        
        if (isset($params["limit"]))
        {
            $queryBuilder
                ->setMaxResults($params["limit"]);
        }

        $results = [];
       
        $stmt = $this->connection->query($sql);
        while($object = $stmt->fetch())
        {
            array_push($results, $object);
        }

        return $results;
    }

    /**
     * Insert a new addon on destiny db
     * If on the database exists an addon with the same alias,
     * it is return the id of the existing addon.
     * 
     * @param {array} $params
     * @return {int} $id
     */
    public function create($params)
    {
        $paramsGet['alias'] = $params["alias"];
        $paramsGet['limit'] = 1;

        $results = $this->get($paramsGet);

        if (count($results) > 0)
        {
            return $results[0]["id"];
        }

        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("addons")
            ->setValue("name", '"' . $params["name"] .'"')
            ->setValue("alias", '"' . $params["alias"] .'"');

        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        // add to migratio nsync
        $this->migrationSync->create("addon", $params["id_addon"], $id);

        return $id;
    }

    public function createAddonType($params)
    {
        // Check if id_type and id_addon exist
        $paramsGet = [
            'type_id' => $params["id_type"],
            'addon_id'=> $params["id_addon"],
            'limit' => 1
        ];

        $results = $this->getAddonsType($paramsGet);
        
        if (count($results) > 0)
        {
            return $results[0]["id"];
        }

        // Create if not
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("addonsTypes")
            ->setValue("type_id", '"' . $params["id_type"] .'"')
            ->setValue("addon_id", '"' . $params["id_addon"] .'"')
            ->setValue("required", '"' . $params["required"] .'"');

        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        return $id;
    }
}
