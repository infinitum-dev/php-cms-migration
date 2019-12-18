<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\v3;

class AddonsFields
{
    protected $connection;
    protected $app;
    
    public function __construct($connection, $app)
	{
        $this->connection = $connection;
        $this->app = $app;
    }

    public function getValue($params = [])
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        
        $sql = $queryBuilder
            ->select("*")
            ->from("addons_fieldsobjects", "af")
            ->andwhere("af.id_object = " . $params["id_object"])
            ->andwhere("af.id_field = " . $params["id_field"]);

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

        $results = [];
       
        $stmt = $this->connection->query($sql);
        while($object = $stmt->fetch())
        {
            array_push($results, $object);
        }

        return $results;
    }
}
