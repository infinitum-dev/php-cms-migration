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
use \Gumlet\ImageResize;

class Content
{
    protected $connection;
    protected $migrationSync;
    protected $addonsAttachment;

    public function __construct($connection)
	{
        $this->connection = $connection;
        $this->migrationSync = new MigrationSync($this->connection);
        $this->addonsAttachment = new AddonsAttachment($this->connection);
    }

    public function get($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("contents");

        if (isset($params["content_id"]))
        {
            $queryBuilder
                ->andwhere("id = '" . $params["content_id"] . "'");
        }
        
        if (isset($params["token"]))
        {
            $queryBuilder
                ->andwhere("token = '" . $params["token"] . "'");
        }

        if (isset($params["types"]))
        {
            $queryBuilder
                ->andwhere("type_id in (" . implode(",", $params["types"]) . ")");
        }
        
        if (isset($params["sync"]) && $params["sync"])
        {
            $queryBuilder
                ->andwhere("id in ( select id_cms_v4 from migration_sync where type = 'content')");
        }

        // offset
        if (isset($params["offset"]))
        {
            $queryBuilder->setFirstResult($params["offset"]);
        }
        
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
   
    public function getContentLanguage($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("contentsLanguages");

        if (isset($params["content_id"]))
        {
            $queryBuilder
                ->andwhere("content_id = '" . $params["content_id"] . "'");
        }
        
        if (isset($params["alias"]))
        {
            $queryBuilder
                ->andwhere("alias like '" . $params["alias"] . "%'");
        }

        // offset
        if (isset($params["offset"]))
        {
            $queryBuilder->setFirstResult($params["offset"]);
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

    public function getAddons($params)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select("*")
            ->from("contents");

        if (isset($params["content_id"]))
        {
            $queryBuilder
                ->andwhere("contents.id = '" . $params["content_id"] . "'");
        }
        
        if (isset($params["token"]))
        {
            $queryBuilder
                ->andwhere("token = '" . $params["token"] . "'");
        }

        if (isset($params["types"]))
        {
            $queryBuilder
                ->andwhere("type_id in (" . implode(",", $params["types"]) . ")");
        }

        if (isset($params["categories"]))
        {
            // join body
            $queryBuilder
                ->join('contents', 'addon_categories', 'addon_categories', 'contents.id = addon_categories.content_id');
        }
        
         // group by
         $queryBuilder->groupBy("contents.id");

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
     * 
     * @param {array} $object - v3 object
     * @param {string} $type - v4 type
     * @param {string} $type - v4 lang
     * @param {array} $type - v4 langs
     */
    public function createContent($object, $type, $defaultLanguage, $otherLanguages = [])
    {
       // check if is sync, add on sync if not...
       $sync = $this->migrationSync->get("content", $object["id_object"], null);
       if (count($sync) > 0)
       {
           return $sync[0]["id_cms_v4"];
       }

        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();

        $params = $this->parseContentParams($object, $type);

        // Generate token
        $token = $this->getToken();
        
        $sql = $queryBuilder
            ->insert("contents")
            ->setValue("token", '"' . $token .'"')
            ->setValue("created_at", '"' . $params["created_at"] .'"')
            ->setValue("updated_at", '"' . $params["updated_at"] .'"')
            ->setValue("ordercontent", '"' . $params["ordercontent"] .'"')
            ->setValue("visibility", '"' . $params["visibility"] .'"')
            ->setValue("published", '"' . $params["published"] .'"')
            ->setValue("status", '"' . $params["status"] .'"')
            ->setValue("type_id", '"' . $params["type_id"] .'"');

        $this->connection->query($sql);

        $contentId = $this->connection->lastInsertId();

        // insert by language default
        $params = $this->parseContentLanguageParams($object, $defaultLanguage, $contentId, $type);

        // create object language default
        $this->createContentLanguage($params);
        
        // fetch object foreach language and aggregator = id_object
		foreach($otherLanguages as $key => $lang)
		{			
			if (count($object["objectAggregator"][$key]) > 0)
			{
				$params = $this->parseContentLanguageParams($object["objectAggregator"][$key][0], $lang, $contentId, $type);
				
				// create object language default
				$this->createContentLanguage($params);
			}
        }
        
        // add to migration sync
        $this->migrationSync->create("content", $object["id_object"], $contentId, $object["date_modified"]);

        return $contentId;
    }

    public function update($params)
    {        
        // CONTENT
        if (isset($params["created_at"]))
        {
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder;

            $sql
                ->update("contents")
                ->set("created_at", "\"" . $params["created_at"] . "\"")
                ->set("updated_at", "\"" . $params["updated_at"] . "\"")
                ->where("id = " . $params["content_id"]);
    
            $this->connection->query($sql);
        }
        
        if (isset($params["name"]))
        {
            // alias
            $slug = $this->slugify($params["name"]);

            $contents = $this->getContentLanguage(["alias" => $slug]);
            if (count($contents) > 0)
            {
                $slug .= "-" . count($contents);
            }
            
            // CONTENT LANGUAGE
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder;
            $sql
                ->update("contentsLanguages")
                ->set("alias", "\"" . $slug . "\"")
                ->where("content_id = " . $params["content_id"]);
    
            $this->connection->query($sql);
        }
    }

    public function removeAllAlias()
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $sql = $queryBuilder;

        $sql
            ->update("contentsLanguages")
            ->set("alias", "\"\"");

        $this->connection->query($sql);
    }

    private function parseContentParams($object, $type)
    {
        // create object
		$params = [
			"created_at" => $object["date_created"],
			"updated_at" => $object["date_modified"],
			"ordercontent" => $object["orderobjects"],
			"visibility" => "public",
			"published" => $object["published"],
			"status" => $object["id_status"],
			"type_id" => $type
        ];
        
        return $params;
    }

    private function parseContentLanguageParams($object, $language, $contentId, $type)
    {
        $params = [
			"name" => $object["name"],
			"alias" => $object["alias"],
			"description" => $object["description"],
			"image" => $object["image"],
			"image_name" => "",
			"body" => $object["body"],
			"status" => $object["id_status"] == 1 ? "active" : "inactive",
			"language_id" => $language,
			"content_id" => $contentId,
			"type_id" => $type,
			"created_at" => $object["date_created"],
			"updated_at" => $object["date_modified"]
        ];

        if(isset($object["uploadPath"]))
        {
            $params["uploadPath"] = $object["uploadPath"];
        }

        if(isset($object["urlPath"]))
        {
            $params["urlPath"] = $object["urlPath"];
        }
        
        return $params;
    }

    private function createContentLanguage($params)
    {
        // TODO: validar e filtrar $params
        $queryBuilder = $this->connection->createQueryBuilder();
        
        if (!empty($params["image"]))
        {
            // upload file
            // $file = $this->uploadFile($params["image"], $params);

            // if ($file)
            // {
            //     $params["image"] = $this->uploadFile($params["image"], $params);
            // }
        }

        // alias
        $slug = $this->slugify($params["name"]);

        $contents = $this->getContentLanguage(["alias" => $slug]);
        if (count($contents) > 0)
        {
            $slug .= "-" . count($contents);
        }
        
        $sql = $queryBuilder
            ->insert("contentsLanguages")
            ->setValue("name", '"' .  addslashes($params["name"]) .'"')
            ->setValue("alias", '"' . $slug .'"')
            ->setValue("description", '"' .addslashes(str_replace(PHP_EOL, '', $params["description"])) .'"')
            ->setValue("image", '"' . $params["image"] .'"')
            ->setValue("image_name", '"' . $params["image_name"] .'"')
            ->setValue("body", '"' . addslashes(str_replace(PHP_EOL, '', $params["body"])) .'"')
            ->setValue("status", '"' . $params["status"] .'"')
            ->setValue("language_id", '"' . $params["language_id"] .'"')
            ->setValue("content_id", '"' . $params["content_id"] .'"')
            ->setValue("created_at", '"' . $params["created_at"] .'"')
            ->setValue("updated_at", '"' . $params["updated_at"] .'"');

        $this->connection->query($sql);

        return $this->connection->lastInsertId();
    }

    private function getToken()
    {
        $flag = false;

		while (!$flag) {
            $token = $this->randomStr(40);
            
            $params = [
                "token" => $token,
                "limit" => 1
            ];

            $result = $this->get($params);

			if (count($result) === 0) {
				$flag = true;
			}
		}

		return $token;
    }

    private function randomStr($length = 10)
    {
        $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($x, ceil($length/strlen($x)) )),1,$length);
    }

    private function uploadFile($file, $params)
    {
        $file_headers = @get_headers($file);
        if (
            $file_headers[0] !== 'HTTP/1.0 404 Not Found' &&
            $file_headers[0] !== 'HTTP/1.1 302 Moved Temporarily' &&
            $file_headers[0] !== 'HTTP/1.1 400 Bad Request'
        )
        {
            // create attachments
            $path = str_replace("{typeId}", $params["type_id"], $params["uploadPath"]);
            $pathUrl = str_replace("{typeId}", $params["type_id"], $params["urlPath"]);
            
            $path = str_replace("{contentId}", $params["content_id"], $path);
            $pathUrl = str_replace("{contentId}", $params["content_id"], $pathUrl);

            // create dir
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
        
            $fileName = basename($file);
            $file = file_get_contents($file);
            file_put_contents($path . $fileName, $file);
            
            return $pathUrl . $fileName;
        }

        return false;
    }

    private function slugify($text)
	{
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// trim
		$text = trim($text, '-');

		// remove duplicate -
		$text = preg_replace('~-+~', '-', $text);

		// lowercase
		$text = strtolower($text);

		if (empty($text)) {
			return 'n-a';
		}

		return $text;
    }
    
    public function moveContentsFiles($params)
    {

        // Get Contents Languages
        $paramsLang = ["content_id" => $params["content_id"]];

        $contentLanguages = $this->getContentLanguage($paramsLang);
        
        foreach($contentLanguages as $lang)
        {
            if (empty($lang["image"]))
                continue;

            preg_match('/http:\/\/www.portolazer.pt\/assets\/(misc.*)/', $lang["image"], $out);
            if (count($out) === 0)
                preg_match('/http:\/\/www.agoraporto.pt\/assets\/(misc.*)/', $lang["image"], $out);

            if (count($out) === 0)
                continue;

            $path = $out[1];

            $localFile = urldecode($params["localPath"].$path);
            $fileInfo = pathinfo($out[1]);

            try {
                $name = $this->slugify(urldecode($fileInfo["filename"])) . "." . $fileInfo["extension"];
            } catch (\Exception $e) {
                continue;
            }

            $newPath = $params["path"] . $name;

            try {
                copy($localFile, $newPath);
            } catch(\Exception $ex) {
                continue;
            }

            // UPDATE CONTENT LANGUAGE
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder;
            $sql
                ->update("contentsLanguages")
                ->set("image", "\"" . $newPath . "\"")
                ->where("content_id = " . $params["content_id"]);
    
            $this->connection->query($sql);
        }

        // Get Contents Addons Atatchments
        $this->moveAttachmentsFiles($params);
    }
    
    private function moveAttachmentsFiles($params)
    {
        $paramsAddon = ["content_id" => $params["content_id"]];
        $attachments = $this->addonsAttachment->getAttachments($paramsAddon);
        foreach($attachments as $attach)
        {
            if (empty($attach["file"]) || $attach["mimetype"] == "video")
                continue;

            preg_match('/http:\/\/www.portolazer.pt\/assets\/(misc.*)/', $attach["file"], $out);
            if (count($out) === 0)
                preg_match('/http:\/\/www.agoraporto.pt\/assets\/(misc.*)/', $attach["file"], $out);

            if (count($out) === 0)
                continue;

            $path = $out[1];

            $localFile = urldecode($params["localPath"].$path);
            $fileInfo = pathinfo($out[1]);

            try {
                $name = $this->slugify(urldecode($fileInfo["filename"])) . "." . $fileInfo["extension"];
            } catch (\Exception $e) {
                continue;
            }

            $newPath = $params["path"] . $name;

            try {
                copy($localFile, $newPath);
            } catch(\Exception $ex) {
                continue;
            }

            // UPDATE CONTENT LANGUAGE
            $queryBuilder = $this->connection->createQueryBuilder();
            $sql = $queryBuilder;
            $sql
                ->update("addon_attachments")
                ->set("file", "\"" . $newPath . "\"")
                ->where("id = " . $attach["id"]);
    
            $this->connection->query($sql);
        }
    }
}

