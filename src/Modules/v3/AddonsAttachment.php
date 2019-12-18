<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\v3;

class AddonsAttachment
{
    protected $connection;
    protected $app;
    
    public function __construct($connection, $app)
	{
        $this->connection = $connection;
        $this->app = $app;
    }

    public function get($params = [])
    {
        if (!isset($params["id_status"]))
        {
            $params["id_status"] = 1;
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        
        $sql = $queryBuilder
            ->select("at.*")
            ->from("addons_attachments", "at")
            ->join('at', 'objects', 'o', 'at.id_object = o.id_object')
            ->join('o', 'objects_typesApps', 'ot', 'o.id_type = ot.id_type')
            ->andwhere("ot.id_app = " . $this->app)
            ->andwhere("o.id_app = " . $this->app)
            ->andwhere("o.id_status = " . $params["id_status"])
            ->andwhere("o.published = 1")
            ->andWhere("at.id_revision IN ( select MAX(id_revision) from addons_attachments where id_object = at.id_object )");

        if (isset($params["id_object"]))
        {
            $queryBuilder->andwhere("o.id_object = " . $params["id_object"]);
        }

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

    public function getCategories($params = [])
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        
        $sql = $queryBuilder
            ->select("at.*")
            ->from("addons_attachments_categories", "at")
            ->andwhere("at.id_app = " . $this->app);

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
        while($attach = $stmt->fetch())
        {
            array_push($results, $attach);
        }

        return $results;
    }
}
