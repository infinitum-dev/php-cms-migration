<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\v4;

use \Doctrine\DBAL\Schema\Schema;

use Fyi\Cms\Modules\Connection\Source;

class MigrationSync
{
    const DESTINY_SYNC_TABLE_NAME = "migration_sync"; 
    const DESTINY_SYNC_TABLE_SEQ = "migration_sync_seq"; 

    protected $connection;

    public function __construct($connection)
	{
        $this->connection = $connection;

        $this->createSyncTable();
    }

    private function createSyncTable()
    {
        $sm = $this->connection->getSchemaManager();
        $sequences = $sm->listTables();
        
        // check if table sync exists
        $exist = false;
        foreach ($sequences as $sequence)
        {
            if ($sequence->getName() === self::DESTINY_SYNC_TABLE_NAME)
            {
                $exist = true;
                break;
            }
        }

        if ($exist)
        {
            return;
        }

        // create if not exists
        $schema = new Schema();
        $table = $schema->createTable(self::DESTINY_SYNC_TABLE_NAME);
        $table->addColumn("id", "integer", ["unsigned" => true, "autoincrement" => true]);
        $table->addColumn("type", "string", ["length" => 59]);
        $table->addColumn("id_cms_v3", "integer");
        $table->addColumn("id_cms_v4", "integer");
        $table->addColumn("cms_v3_updated_at", "datetime", ["notnull" => false]);
        $table->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"]);
        $table->addColumn("updated_at", "datetime", [
            "default" => "CURRENT_TIMESTAMP",
            "columnDefinition" =>  "timestamp default current_timestamp on update current_timestamp"
            ]);
        
        $table->setPrimaryKey(array("id"));

        $platform = $this->connection->getDatabasePlatform();
        $queries = $schema->toSql($platform);
        
        $stmt = $this->connection->query($queries[0]);
    }

    public function create($type, $v3Id, $v4Id, $v3UpdatedAt = null)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("migration_sync")
            ->setValue("type", '"' . $type .'"')
            ->setValue("id_cms_v3", '"' . $v3Id .'"')
            ->setValue("id_cms_v4", '"' . $v4Id .'"')
            ->setValue("cms_v3_updated_at", '"' . $v3UpdatedAt .'"');

        $this->connection->query($sql);

        $syncId = $this->connection->lastInsertId();

        return $syncId;
    }

    public function get($type = "all", $v3Id = null, $v4Id = null, $params = [])
    {
        if (isset($params["v3Id"]))
        {
            $v3Id = $params["v3Id"];
        }
        
        if (isset($params["v4Id"]))
        {
            $v4Id = $params["v4Id"];
        }

        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("migration_sync");

        if ($type !== "all")
        {
            $queryBuilder
                ->andWhere("type = '" . $type . "'");
        }
        
        if ($v3Id !== null)
        {
            $queryBuilder
                ->andWhere("id_cms_v3 = '" . $v3Id . "'");
        }
       
        if ($v4Id !== null)
        {
            $queryBuilder
                ->andWhere("id_cms_v4 = '" . $v4Id . "'");
        }
        
        if (isset($params["order"]))
        {
            $order = explode(",", $params["order"]);
            $queryBuilder
                ->orderBy($order[0], $order[1]);
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
}
