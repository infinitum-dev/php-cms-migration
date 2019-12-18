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

class AddonsCategory
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
                ->andWhere("alias = '" . $params["alias"] . "'");
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
    
    public function getCategoryLang($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("categorieslanguages");

        if (isset($params["name"]))
        {
            $queryBuilder
                ->andWhere("name = '" . $params["name"] . "'");
        }
        
        
        if (isset($params["alias"]))
        {
            $queryBuilder
                ->andWhere("alias = '" . $params["alias"] . "'");
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
    
    public function getCategoryType($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("categoriesTypes");

        if (isset($params["type_id"]))
        {
            $queryBuilder
                ->andWhere("type_id = '" . $params["type_id"] . "'");
        }
        
        
        if (isset($params["category_id"]))
        {
            $queryBuilder
                ->andWhere("category_id = '" . $params["category_id"] . "'");
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

    public function getCategoryContent($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addon_categories");

        if (isset($params["content_id"]))
        {
            $queryBuilder
                ->andWhere("content_id = '" . $params["content_id"] . "'");
        }
      
        if (isset($params["category_id"]))
        {
            $queryBuilder
                ->andWhere("category_id = '" . $params["category_id"] . "'");
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
     * It's not considering multilanguage
     * 
     * @param {array} $params
     * @return {int} $id
     */
    public function create($params)
    {
        // check category name
        $paramCat = [
            "limit" => 1,
            "alias" => $params["system_name"]
        ];

        $categoryId = null;
        $cat = $this->getCategoryLang($paramCat);
        
        if (count($cat) > 0)
        {
            $categoryId = $cat[0]["category_id"];
        }

        if (!isset($params["checkCat"]))
        {
            // check sync
            $sync = $this->migrationSync->get("addonCategory", $params["id_category"], null, ["limit" => 1]);
            
            if (count($sync) > 0)
            {
                return $sync[0]["id_cms_v4"];
            }
        }

        $categoryId = $this->createCategory($params, $categoryId);
        
        // Insert subcategories...
        foreach($params["children"] as $child)
        {
            $child["parent"] = $categoryId;
            $categoryId = $this->createCategory($child);
        }

        // add to migratio nsync
        if (!isset($params["checkCat"]))
        {
            $this->migrationSync->create("addonCategory", $params["id_category"], $categoryId);
        }

        return $categoryId;
    }

    public function createCategoryContent($params)
    {
        // check sync
        $params["limit"] = 1;
        $category = $this->getCategoryContent($params);
        
        if (count($category) > 0)
        {
            return $category[0]["id"];
        }

        // Insert Into categories
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder
            ->insert("addon_categories")
            ->setValue("category_id", $params["category_id"])
            ->setValue("content_id", $params["content_id"]);


        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        return $id;
    }

    private function createCategory($params, $categoryId = null)
    {
        // Insert Into categories
        if ($categoryId === null)
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder
                ->insert("categories");
            
            if (isset($params["parent"]))
            {
                $queryBuilder
                    ->setValue("parent", $params["parent"]);
            }
    
            $this->connection->query($sql);
            $categoryId = $this->connection->lastInsertId();
            
            // Insert Into categories Language
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder
                ->insert("categoriesLanguages")
                ->setValue("name", "\"" . $params["name"] ."\"")
                ->setValue("alias",  "\"" . $params["system_name"] ."\"")
                ->setValue("language_id", $params["language_id"])
                ->setValue("category_id", $categoryId);
    
            if (isset($params["description"]))
            {
                $queryBuilder
                    ->setValue("description", "\"" . $params["description"] ."\"");
            }
            
            $this->connection->query($sql);
        }

        $paramsT = [
            "limit" => 1,
            "type_id" => $params["type_id"],
            "category_id" => $categoryId
        ];
        $catT = $this->getCategoryType($paramsT);

        if (count($catT) === 0)
        {
            // Insert Into categories Type
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder
                ->insert("categoriesTypes")
                ->setValue("type_id", $params["type_id"])
                ->setValue("category_id", $categoryId);
            
            $this->connection->query($sql);
        }


        return $categoryId;
    }
}
