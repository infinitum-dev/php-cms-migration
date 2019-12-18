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

class AddonsFields
{
    protected $connection;
    protected $migrationSync;
    
    public function __construct($connection)
	{
        $this->connection = $connection;
        $this->migrationSync = new MigrationSync($this->connection);
    }

    public function getField($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("fields");
        
        if (isset($params["type"]))
        {
            $queryBuilder
                ->andWhere("type = '" . $params["type"] . "'");
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
    
    public function getFieldValue($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addon_fields");
        
        if (isset($params["content_id"]))
        {
            $queryBuilder
                ->andWhere("content_id = '" . $params["content_id"] . "'");
        }
        
        if (isset($params["field_id"]))
        {
            $queryBuilder
                ->andWhere("field_id = '" . $params["field_id"] . "'");
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
    
    public function getFieldLang($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("fieldsLanguages");
        
        if (isset($params["field_id"]))
        {
            $queryBuilder
                ->andWhere("field_id =" . $params["field_id"]);
        }
        
        if (isset($params["language_id"]))
        {
            $queryBuilder
                ->andWhere("language_id =" . $params["language_id"]);
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
    
    public function getFieldType($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("fieldsTypes");
        
        if (isset($params["field_id"]))
        {
            $queryBuilder
                ->andWhere("field_id =" . $params["field_id"]);
        }
        
        if (isset($params["type_id"]))
        {
            $queryBuilder
                ->andWhere("type_id =" . $params["type_id"]);
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
    public function create($params, $type)
    {
        // check if label exists
        dd($params, $type);

        // Insert field...
        $fieldId = $this->createField($params);

        // insert languages
        foreach($params["languages"] as $lang)
        {
            $paramsFLang = [
                "field_id" => $fieldId,
                "language_id" => $lang,
                "limit" => 1
            ];
            $fLang = $this->getFieldLang($paramsFLang);

            if (count($fLang) === 0)
            {
                // Insert Into fields langs
                $queryBuilder = $this->connection->createQueryBuilder();
                $sql = $queryBuilder
                    ->insert("fieldsLanguages")
                    ->setValue("label", "\"" . $params["label"] . "\"")
                    ->setValue("field_id", $fieldId)
                    ->setValue("language_id", $lang);
                
                $this->connection->query($sql);
            }
        }

        $fTypeParams = [
            "field_id" => $fieldId,
            "type_id" => $type,
            "limit" => 1
        ];

        $langType = $this->getFieldType($fTypeParams);

        // insert types
        if (count($langType) === 0)
        {
            // Insert Into fields langs
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder
                ->insert("fieldsTypes")
                ->setValue("type_id", $type)
                ->setValue("field_id", $fieldId);

            $this->connection->query($sql);
        }

        return $fieldId;
    }

    public function createField($params)
    {
        // check sync
        $params["limit"] = 1;
        $field = $this->getField($params);
        
        if (count($field) > 0)
        {
            return $field[0]["id"];
        }

        // Insert Into fields
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder
            ->insert("fields")
            ->setValue("type", "\"" . $params["type"] . "\"");


        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        return $id;
    }

    private function createCategory($params)
    {
        // Insert Into categories
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
        
        // Insert Into categories Type
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder
            ->insert("categoriesTypes")
            ->setValue("type_id", $params["type_id"])
            ->setValue("category_id", $categoryId);
        
        $this->connection->query($sql);

        return $categoryId;
    }

    public function createFieldValue($params)
    {
        $field = $this->getFieldValue(["content_id" => $params["content_id"], "field_id" => $params["field_id"]]);
        if (count($field) > 0)
        {
            return $field[0]["id"];
        }
        
        // Insert Into fields langs
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder
            ->insert("addon_fields")
            ->setValue("value", "\"" . $params["value"] . "\"")
            ->setValue("created_at", "\"" . $params["created_at"] . "\"")
            ->setValue("updated_at", "\"" . $params["updated_at"] . "\"")
            ->setValue("field_id", $params["field_id"])
            ->setValue("content_id", $params["content_id"]);
        
        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        return $id;
    }
}
