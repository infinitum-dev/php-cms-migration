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

class Language
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
            ->from("languages");

        if (isset($params["code"]))
        {
            $queryBuilder
                ->andwhere("code = '" . $params["code"] . "'");
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
     * Insert a new language on destiny db
     * If on the database exists a language with the same code,
     * it is return the id of the existing language.
     * 
     * @param {array} $params
     * @return {int} $id
     */
    public function create($params)
    {
        $paramsGet['code'] = $params["code"];
        $paramsGet['limit'] = 1;

        $results = $this->get($paramsGet);

        if (count($results) > 0)
        {
            return $results[0]["id"];
        }

        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("languages")
            ->setValue("name", '"' . $params["name"] .'"')
            ->setValue("locale", '"' . $params["locale"] .'"')
            ->setValue("code", '"' . $params["code"] .'"');

        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        // add to migratio nsync
        $this->migrationSync->create("language", $params["id_language"], $id);

        return $id;
    }
}
