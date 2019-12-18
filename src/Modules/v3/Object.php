<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\v3;

use Fyi\Cms\Modules\Connection\Source;

class Object
{
    protected $connection;
    
    protected $app;

    public function __construct($connection, $app)
	{
        $this->connection = $connection;
        $this->app = $app;
    }

    public function objects($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("objects")
            ->where("id_app = " . $this->app);

        if (isset($params["id_object"]))
        {
            $sql->andWhere("objects.id_object = " . $params["id_object"]);
        }

        if (isset($params["date"]))
        {
            $sql->andWhere("objects.date_created > '" . $params["date"] . "'");
        }
        
        if (isset($params["id_status"]))
        {
            $sql->andWhere("id_status = " . $params["id_status"]);
        }
        
        if (isset($params["published"]))
        {
            $sql->andWhere("published = " . $params["published"]);
        }

        if (isset($params["types"]) && strpos($params["types"], 'all') === false)
        {
            $sql->andWhere("id_type IN (" . $params["types"] . ")");
        }

        if (isset($params["aggregator"]))
        {
            $sql->andWhere("aggregator = " . $params["aggregator"]);
        }
        
        if (isset($params["id_language"]))
        {
            $sql->andWhere("id_language = " . $params["id_language"]);
        }
        
        if (isset($params["objects"]))
        {
            $sql->andWhere("objects.id_object in (" . implode(",", $params["objects"]) . ")");
        }

        if (isset($params["ignore"]))
        {
            for ($i = 0; $i < count($params["ignore"]); $i++)
            {
                $sql->andWhere("objects.id_object not in (select " . $params["ignore"][$i] . ".id_object from " . $params["ignore"][$i] . ") ");
            }
        }
        
        if (isset($params["only"]))
        {
            for ($i = 0; $i < count($params["only"]); $i++)
            {
                $sql->andWhere("objects.id_object in (select " . $params["only"][$i] . ".id_object from " . $params["only"][$i] . ") ");
            }
        }

        // join body
        $sql->join('objects', 'objects_body', 'objects_body', 'objects.id_object = objects_body.id_object');

        // group by
        $queryBuilder->groupBy("objects.id_object");

        // offset
        if (isset($params["offset"]))
        {
            $queryBuilder->setFirstResult($params["offset"]);
        }

        // limit
        if (isset($params["limit"]))
        {
            $queryBuilder->setMaxResults($params["limit"]);
        }

        $objects = [];
        
        $stmt = $this->connection->query($sql);
        while($object = $stmt->fetch())
        {
            array_push($objects, $object);
        }

        return $objects;
    }
}
