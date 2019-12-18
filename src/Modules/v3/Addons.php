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

class Addons
{
    protected $connection;
    
    public function __construct($connection, $app)
	{
        $this->connection = $connection;

        $this->addonsAttachments = new AddonsAttachment($connection, $app);
    }

    public function get($params = [])
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addons");

        if (isset($params["alias"]))
        {
            $sql->andWhere("system_name like \"%" . $params["alias"] . "%\"");
        }

        $results = [];
       
        $stmt = $this->connection->query($sql);
        while($addon = $stmt->fetch())
        {
            array_push($results, $addon);
        }

        return $results;
    }

    public function getAddonsTypes($types)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addons_addonsObject_types")
            ->where("id_type IN (" . implode(",", $types) . ")");

        $results = [];
       
        $stmt = $this->connection->query($sql);
        while($addon = $stmt->fetch())
        {
            array_push($results, $addon);
        }

        return $results;
    }
}
