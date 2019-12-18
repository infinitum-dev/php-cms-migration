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

class Type
{
    protected $connection;
    protected $migrationSync;

    public function __construct($connection)
	{
        $this->connection = $connection;
        $this->migrationSync = new MigrationSync($this->connection);
    }

    public function getTypeLanguage($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("typesLanguages");

        if (isset($params["type_id"]))
        {
            $queryBuilder
                ->andwhere("type_id = '" . $params["type_id"] . "'");
        }
        
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

    public function createType($type, $langMapped)
    {
        // Check if exists a type with the same alias
        $paramsGet['alias'] = $type["alias"];
        $paramsGet['limit'] = 1;

        $results = $this->getTypeLanguage($paramsGet);

        // Return type with the same alias if it exists
        if (count($results) > 0)
        {
            // check if is sync, add on sync if not...
            $sync = $this->migrationSync->get("type", $type["id_type"], $results[0]["type_id"]);

            if (count($sync) === 0)
            {
                // add to migration sync
                $this->migrationSync->create("type", $type["id_type"], $results[0]["type_id"], $type["date_modified"]);
            }
            
            return $results[0]["type_id"];
        }

        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("types");

        $this->connection->query($sql);
        $typeId = $this->connection->lastInsertId();

        $params = [
            "name" => $type["name"],
            "alias" => $type["alias"],
            "description" => $type["description"],
            "type_id" => $typeId
        ];

        foreach($langMapped as $l)
        {
            $params["language_id"] = $l;
            $this->createTypeLanguage($params);
        }

        // add to migration sync
        $this->migrationSync->create("type", $type["id_type"], $typeId, $type["date_modified"]);

        return $typeId;
    }
    
    public function createTypeLanguage($params)
    {
        $paramsGet['alias'] = $params["alias"];
        $paramsGet['language_id'] = $params["language_id"];
        $paramsGet['limit'] = 1;

        $results = $this->getTypeLanguage($paramsGet);

        if (count($results) > 0)
        {
            return $results[0]["id"];
        }

        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("typesLanguages")
            ->setValue("name", '"' . $params["name"] .'"')
            ->setValue("alias", '"' . $params["alias"] .'"')
            ->setValue("description", '"' . $params["description"] .'"')
            ->setValue("type_id", '"' . $params["type_id"] .'"')
            ->setValue("language_id", '"' . $params["language_id"] .'"');

        $this->connection->query($sql);

        return $this->connection->lastInsertId();
    }
}
