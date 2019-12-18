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

class AddonsAttachment
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
                ->andwhere("alias = '" . $params["alias"] . "'");
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
    
    public function getAttachments($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("addon_attachments");

        if (isset($params["content_id"]))
        {
            $queryBuilder
                ->andwhere("content_id = '" . $params["content_id"] . "'");
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
     * @param {array} $params
     * @return {int} $id
     */
    public function create($params)
    {
        // check sync
        $sync = $this->migrationSync->get("addonAttachment", $params["id_attach"], null, ["limit" => 1]);
        if (count($sync) > 0)
        {
            return $sync[0]["id_cms_v4"];
        }
        
        // upload file
        $fileUrl = $this->uploadFile($params);
        
        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("addon_attachments")
            ->setValue("name", '"' . $params["name"] .'"')
            ->setValue("description", '"' . $params["description"] .'"')
            ->setValue("file", '"' . $fileUrl .'"')
            ->setValue("mimetype", '"' . $params["mimetype"] .'"')
            ->setValue("orderattachment", '"' . $params["orderattachment"] .'"')
            ->setValue("language_id", '"' . $params["language_id"] .'"')
            ->setValue("content_id", '"' . $params["content_id"] .'"');
        
        if (isset($params["link"]))
        {
            $sql->setValue("link", '"' . $params["link"] .'"');
        }

        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        // add to migratio nsync
        $this->migrationSync->create("addonAttachment", $params["id_attach"], $id);

        return $id;
    }

    public function createAddonType($params)
    {
        // Check if id_type and id_addon exist
        $paramsGet = [
            'type_id' => $params["id_type"],
            'addon_id'=> $params["id_addon"],
            'limit' => 1
        ];

        $results = $this->getAddonsType($paramsGet);
        
        if (count($results) > 0)
        {
            return $results[0]["id"];
        }

        // Create if not
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->insert("addonsTypes")
            ->setValue("type_id", '"' . $params["id_type"] .'"')
            ->setValue("addon_id", '"' . $params["id_addon"] .'"')
            ->setValue("required", '"' . $params["required"] .'"');

        $this->connection->query($sql);
        $id = $this->connection->lastInsertId();

        return $id;
    }

    private function uploadFile($params)
    {
        // $file = $params["file"];
        // $fileName = basename($file);

        // create dir
        // if (!file_exists($path)) {
        //     mkdir($params["path"], 0777, true);
        // }

        // $file = file_get_contents($file);
        // file_put_contents($params["path"] . $fileName, $file);
        // $fileUrl = $params["pathUrl"] . $fileName;

        return $params["file"];
    }
}
