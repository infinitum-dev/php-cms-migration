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

class Language
{
    protected $connection;
    
    protected $app;

    public function __construct($connection, $app)
	{
        $this->connection = $connection;
        $this->app = $app;
    }

    public function languages()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("apps_languages", "l")
            ->where("id_app = " . $this->app);

        // join body
        $sql->innerJoin('l', 'apps_languagesApps', 'la', 'l.id_language = la.id_language');

        $results = [];
       
        $stmt = $this->connection->query($sql);
        while($object = $stmt->fetch())
        {
            array_push($results, $object);
        }

        return $results;
    }
}
