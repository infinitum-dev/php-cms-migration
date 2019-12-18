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

class ObjectTypes
{
    protected $connection;
    
    protected $app;

    public function __construct($connection, $app)
	{
        $this->connection = $connection;
        $this->app = $app;
    }

    public function objectTypes()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("objects_types", "o")
            ->innerJoin('o', 'objects_typesApps', 'ot', 'o.id_type = ot.id_type')
            ->where("ot.id_app = " . $this->app);

        $objects = [];
       
        $stmt = $this->connection->query($sql);
        while($object = $stmt->fetch())
        {
            array_push($objects, $object);
        }

        return $objects;
    }
}
