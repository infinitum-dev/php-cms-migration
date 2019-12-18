<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms;

require_once __DIR__ . '/../vendor/autoload.php';

use Fyi\Cms\Modules\Connection\Source;
use Fyi\Cms\Modules\Connection\Destiny;

/**
 * A client to access the Infinitum API
 */
class Cms
{
	protected $source;
	
	protected $destiny;

	public function __construct($source, $destiny)
	{
		// connection with the source database
		$this->source = new Source($source);

		// connection with the destiny database
		$this->destiny = new Destiny($destiny);
	}

	/**************************************
	 * MIGRATE FUNCTION
	 **************************************/
	/**
	 * Migrate all languages on v3 to v4
	 * 
	 * @return json with total inserted
	 */
	public function migrateLanguages($config = []) {
		$langMapped = $this->createLanguages();

		return response()->json([
			"status" => true,
			"total" => count($langMapped)
		]);
	}
	
	/**
	 * Migrate all Types on v3 to v4
	 * 
	 * @return json with total inserted
	 */
	public function migrateTypes($config = []) {
		// Get languages mapped (create if not exists)
		$langMapped = $this->createLanguages();

		$typeMapped = $this->createTypes($langMapped);

		return response()->json([
			"status" => true,
			"total" => count($typeMapped)
		]);
	}
	
	/**
	 * Migrate all Objects on v3 to Contents on v4
	 * 
	 * @return json with total inserted
	 */
	public function migrateContents($config = [])
	{
		// Get langauges mapped
		$langMapped = $this->createLanguages();
		
		// Get types mappaed
		$typeMapped = $this->createTypes($langMapped);

		if (!isset($config["types"]))
		{
			$config["types"] = "all";
		}

		if (!isset($config["published"]))
		{
			$config["published"] = 1;
		}

		if (!isset($config["id_status"]))
		{
			$config["id_status"] = 1;
		}
		
		// Create contents
		$objectsTotal = $this->createContents($config, $langMapped, $typeMapped);

		return response()->json([
			"stauts" => true,
			"total" => $objectsTotal
		]);
	}

	public function migrateAddons($config = [])
	{
		if (!isset($config["addons"]))
		{
			$config["addons"] = [
				"attachments" => "attachments",
				"categories" => "categories",
				"events" => "events",
				"maps" => "maps",
			];
		}

		// Create addons
		$addonsMapped = $this->createAddons($config["addons"]);

		// Get langauges mapped
		$langMapped = $this->createLanguages();
		
		// Get types mappaed
		$typeMapped = $this->createTypes($langMapped);

		// Associate addons to types
		$this->createAddonsTypes($addonsMapped, $typeMapped);

		return response()->json([
			"stauts" => true,
			"total" => count($addonsMapped)
		]); 
	}

	public function migrateAddonsAttachments($params = [])
	{
		// Get langauges mapped
		$langMapped = $this->createLanguages();
		
		// Get types mappaed
		$typeMapped = $this->createTypes($langMapped);

		// Create addons types (e.g. attachments)
		$addonsTotal = $this->createAddonsAttachment($langMapped, $typeMapped, $params);

		return response()->json([
			"stauts" => true,
			"total" => $addonsTotal
		]); 
	}

	public function migrateAddonsCategories($params = [])
	{
		// Get langauges mapped
		$langMapped = $this->createLanguages();
		
		// Get types mappaed
		$typeMapped = $this->createTypes($langMapped);

		// Associate addons to types
		$addons = $this->createAddonsCategories($params, $langMapped, $typeMapped);

		return response()->json([
			"stauts" => true,
			"total" => count($addons)
		]); 
	}

	public function migrateAddonsEvents($config = [])
	{
		// Associate addons to types
		$addons = $this->createAddonsEvents($config);

		return response()->json([
			"stauts" => true,
			"total" => count($addons)
		]); 
	}

	public function migrateAddonsMaps($config = [])
	{
		// Associate addons to types
		$addons = $this->createAddonsMaps($config);

		return response()->json([
			"stauts" => true,
			"total" => count($addons)
		]); 
	}
	
	/**
	 * Migrate languages, types and contents
	 * 
	 * @return json with total inserted
	 */
	public function migrateAll($config = [])
	{
		$config["types"] = "all";

		return $this->migrateContents($config);
	}

	/**********************************************************
	 * MAP FUNCTIONS
	 **********************************************************/
	public function mapAll($map, $config)
	{		
		// create languages
		$langMapped = $this->createLanguages();

		$addonsMapped = $this->mapAddons($map);

		$typeMapped = $this->mapTypes($map, $addonsMapped, $langMapped);
		
		// create addons type
		$this->createAddonsTypes($addonsMapped, $typeMapped);

		$res = $this->mapContents($map, $config, $langMapped, $addonsMapped, $typeMapped);
		
		return $res;
	}

	private function mapContents($map, $config, $langMapped, $addonsMapped, $types)
	{
		if (isset($map["source"]["date"]))
		{
			$sync = $this->destiny->getMigrationSync("content", ["limit" => 1, "order" => "cms_v3_updated_at,DESC"]);
			if (count($sync) > 0)
				$config["date"] = $sync[0]["cms_v3_updated_at"];
		}

		$objects = $this->source->getObjectsToMap($map["source"], $config);
		$objectsLength = count($objects);
		$res = [];
		
		// Get default language
		$defaultLanguage = null;
		if ($objectsLength > 0)
		{
			$defaultLanguage = $langMapped[$objects[0]["id_language"]];
		}

		$otherLanguages = $this->filterLanguageDefault($langMapped, $defaultLanguage);
		
		foreach($objects as $object)
		{
			// object aggregate to object with differents languages 
			foreach($otherLanguages as $key => $lang)
			{
				$params = [
					"id_language" => $key,
					"aggregator" => $object["id_object"]
				];

				$object["objectAggregator"] = [];
				$objectAggregator = $this->source->getObjects($params);

				$object["objectAggregator"][$key] = $objectAggregator;
			}

			// check if object type exists on app
			if (isset($types[$object['id_type']]))
			{
				// create objects and object languages
				$contentId = $this->destiny->createContent(
					$object,
					$types[$object['id_type']],
					$defaultLanguage,
					$otherLanguages
				);

				$res[] = $contentId;

				// map addons
				$this->mapAddonByContent($object['id_object'], $contentId, $addonsMapped, $types[$object['id_type']], $map, $langMapped);
			}
		}

		return $objectsLength;
	}

	private function mapAddonByContent($objectId, $contentId, $addonsMapped, $type, $map, $langMapped)
	{
		foreach ($addonsMapped as $key => $addon)
		{
			switch($addon["alias"]) {
				case "maps":
					$this->mapAddonsMap($objectId, $contentId);
					break;
				case "events":
					$this->mapAddonsEvent($objectId, $contentId);
					break;
				case "attachments":
					$this->mapAddonsAttachments($objectId, $contentId);
					break;
				case "fields":
					$this->createAddonsFields($map["destiny"]["addons"]["fields"], $type, $langMapped);
					break;
				case "categories":
					$this->mapAddonsCategories($map["destiny"]["addons"]["categories"], $type, $langMapped, $objectId, $contentId);
					break;
			}
		}
	}

	private function mapAddonsCategories($categories, $type, $langMapped, $objectId, $contentId)
	{
		// get object $objectId
		$categoriesMapped = [];		

		// get cat
		foreach($categories as $key => $category)
		{
			$params = [
				"checkCat" => true,
				"name" => $key,
				"system_name" => $this->slugify($key),
				"description" => $key,
				"language_id" => 1,
				"children" => [],
				"type_id" => $type
			];

			$catId = $this->destiny->createAddonCategory($params);

			foreach($category as $c)
			{
				$categoriesMapped[$c] = $catId;
			}
		}

		// Get objects Categories
		$objectCats = $this->source->getAddonsCategoryObjects(["id_object" => $objectId]);

		// associate $contentId
		foreach($objectCats as $objCat)
		{
			if (isset($categoriesMapped[$objCat["id_category"]]))
			{

				$paramCreate = [
					"content_id" => $contentId,
					"category_id" => $categoriesMapped[$objCat["id_category"]]
				];

				$this->destiny->createCategoryContent($paramCreate);
			}
		}
	}

	private function createAddonsFields($fields, $type, $langMapped)
	{
		$res = [];
		foreach($fields as $field)
		{
			$field["languages"] =  $langMapped;
			$res[] = $this->destiny->createAddonField($field, $type);
		}
	}

	private function mapAddonsMap($objectId, $contentId)
	{
		$params = [
			"id_status" => 1,
			"id_object" => $objectId
		];
		$addons = $this->source->getAddonsMaps($params);
		
		foreach($addons as $addon)
		{
			$params = [
				"id_maps" => $addon["id_maps"],
				"address" => $addon["address"],
				"locality" => $addon["locality"],
				"latitude" => $addon["latitude"],
				"longitude" => $addon["longitude"],
				"country" => $addon["country"],
				"zipcode" => $addon["zipcode"],
				"content_id" => $contentId
			];

			$id = $this->destiny->createAddonMap($params);
		}

		return $addons;
	}
	
	private function mapAddonsEvent($objectId, $contentId)
	{
		$params = [
			"id_status" => 1,
			"id_object" => $objectId
		];

		$addons = $this->source->getAddonsEvents($params);

		foreach ($addons as $addon)
		{
			$paramsCreate = [
				"id_event" => $addon["id_agenda"],
				"start" => $addon["start"],
				"end" => $addon["end"],
				"timezone" => $addon["timezone"],
				"repeating" => $addon["repeating"],
				"content_id" => $contentId,
			];
			
			$id = $this->destiny->createAddonEvent($paramsCreate);	
		}

		return $addons;
	}

	private function mapAddonsAttachments($objectId, $contentId)
	{
		$params = [
			"id_status" => 1,
			"id_object" => $objectId
		];

		$attachs = $this->source->getAddonsByType("attachments", $params);
		
		foreach($attachs as $attach)
		{
			if (empty($attach["name"]) && empty($attach["file"]) && empty($attach["type"]))
			{
				continue;
			}

			$file_headers = @get_headers($attach["file"]);
			if (
				$file_headers[0] == 'HTTP/1.0 404 Not Found' ||
				$file_headers[0] == 'HTTP/1.1 302 Moved Temporarily' ||
				$file_headers[0] == 'HTTP/1.1 400 Bad Request'
			)
			{
				continue;
			}
			
			// create attachment 
			$paramsAttachment = [
				"id_attach" => $attach["id_attach"],
				"name" => $attach["name"],
				"description" => $attach["description"],
				"file" => $attach["file"],
				"mimetype" => $attach["type"],
				"link" => $attach["link"],
				"orderattachment" => $attach["orderattachment"],
				"language_id" => 1,
				"content_id" => $contentId
			];

			try {
				$addons[] = $this->destiny->createAddonAttachment($paramsAttachment);
			} catch(\Exception $exc) {
				print_r("Addon Error: " . $attach["id_attach"] . "\n");
			}
		}
	}

	private function mapAddons($map)
	{
		$addonsMapped = [];
		$addons = [];

		foreach($map["source"]["addons"] as $key => $addon)
		{
			$addons[$key] = $key;
		}
		
		foreach($map["destiny"]["addons"] as $key => $addon)
		{
			$addons[$key] = $key;
		}

		foreach ($addons as $addon)
		{
			$addonName = substr($addon, 0, -1);
			$addonOld = $this->source->getAddons(["alias" => $addon]);
			if (count($addonOld) > 0)
			{
				$params = [
					"id_addon" => $addonOld[0]["id_addon"],
					"name" => $addonOld[0]["name"],
					"alias" => $addonName
				];
	
				$addonsMapped[$addonOld[0]["id_addon"]] = [
					"alias" => $addonName,
					"addon_id" => $this->destiny->createAddon($params)
				];
			}		
		}

		return $addonsMapped;
	}
	
	private function mapTypes($map, $addonsMapped, $langMapped)
	{
		$typeMapped = [];

		// create type
		if (isset($map["destiny"]["type"]))
		{
			$type = [
				"alias" => $this->removeChar($map["destiny"]["type"]),
				"name" => $map["destiny"]["type"],
				"description" => $map["destiny"]["type"],
				"id_type" => $map["source"]["type"],
				"date_modified" => date("Y-m-d H:i:s")
			];

			$typeId = $this->destiny->createType($type, $langMapped);
			$typeMapped[$type["id_type"]] = $typeId;

		}

		return $typeMapped;
	}

	/*************************************************************
	 * UPDATE FUNCTIONS
	 *************************************************************/
	public function updateInfo($config = [ "offset" => 0, "limit" => 500])
	{
		$contents = $this->destiny->getMigrationSync("content", $config);
		$size = count($contents);

		foreach($contents as $content)
		{
			$contentOld = $this->source->getObjects(["id_object" => $content["id_cms_v3"]]);

			if (count($contentOld) > 0)
			{
				// check languages
				$params = [
					"content_id" => $content["id_cms_v4"],
					"name" => $contentOld[0]["name"],
					"created_at" => $contentOld[0]["date_created"],
					"updated_at" => $contentOld[0]["date_modified"]
				];

				$this->destiny->updateContent($params);
			}
		}

		return $size;
	}
	
	public function updateAlias($config = [ "offset" => 0, "limit" => 500])
	{
		// $this->destiny->removeAllAlias();
		// dd('test');
		$contents = $this->destiny->getContentLanguages($config);
		$size = count($contents);

		foreach($contents as $content)
		{
			$params = [
				"content_id" => $content["id"],
				"name" => $content["name"]
			];

			$this->destiny->updateContent($params);
		}

		return $size;
	}

	public function updateCategories($config  = [ "offset" => 0, "limit" => 500])
	{
		$config["types"] = [10, 11];
		$contents = $this->destiny->getContent($config);
		$size = count($contents);

		foreach($contents as $content)
		{
			// check if content has categories
			$paramsGet = [
				"content_id" => $content["id"],
				"categories" => true
			];

			$addons = $this->destiny->getContentAddons($paramsGet);
			if (count($addons) === 0)
			{
				// add if not
				$params = [
					"content_id" => $content["id"],
					"category_id" => 7
				];

				$this->destiny->createCategoryContent($params);
			}

		}

		return $size;
	}

	public function updateFiles($params = [])
	{	
		$config = [
			"path" => "files",
			"localPath" => "C:\Users\gabri\Documents\Agora\misc\plazermisc\\"
		];
		
		// Get contents
		$contents = $this->destiny->getContent($params);
		$size = count($contents);
		
		foreach($contents as $content)
		{
			$type = $this->destiny->getType(["limit" => 1, "type_id" => $content["type_id"]]);
			$contentPath = $config["path"] . "/" . $type[0]["alias"] . "/" . $content["id"] . "/";
			
			if (!file_exists($contentPath))
			{
				mkdir($contentPath, 0700, true);
			}

			$paramsMove = [
				"content_id" => $content["id"],
				"path" => $contentPath,
				"localPath" => $config["localPath"]
			];

			$this->destiny->moveContentsFiles($paramsMove);
		}

		return $size;
	}

	public function updateFields($params, $config)
	{
		$paramsGet = [
			"types" => [$params["type"]],
			"limit" => $config["limit"],
			"offset" => $config["offset"],
			"sync" => true
		];

		$contents = $this->destiny->getContent($paramsGet);
		$size = count($contents);

		foreach($contents as $content)
		{
			$sync = $this->destiny->getMigrationSync("content", ["v4Id" => $content["id"], "limit" => 1]);
			
			foreach($params["fields"] as $key => $field)
			{
				$paramField = [
					"id_field" => $key,
					"id_object" => $sync[0]["id_cms_v3"]
				];
				$fieldOld = $this->source->getFieldValue($paramField);

				if (count($fieldOld) > 0)
				{
					$paramCreate = [
						"field_id_old" => $key,
						"field_id" => $field,
						"content_id" => $content["id"],
						"value" => empty($fieldOld[0]["value"]) ? 0 : $fieldOld[0]["value"],
						"created_at" => $fieldOld[0]["date_created"],
						"updated_at" => $fieldOld[0]["date_modified"] ? $fieldOld[0]["date_modified"] : $fieldOld[0]["date_created"]
					];

					$this->destiny->createFieldValue($paramCreate);
				}
			}
		}
		
		return $size;
	}

	/**************************************************************
	 * AUXILIAR FUNCTIONS
	 *************************************************************/

	/**
	 * Get v3 languages and create to each one a v4 language
	 * 
	 * @return int total inserted
	 */
	private function createLanguages()
	{
		$languages = $this->source->getLanguages();
		$langMapped = []; // [ old_id => new_id ]

		foreach($languages as $language)
		{
			$params = [
				"id_language" => $language["id_language"],
				"name" => $language["name"],
				"locale" => $language["code"],
				"code" => $language["code"],
			];

			$langMapped[$language["id_language"]] = $this->destiny->createLanguages($params);
		}

		return $langMapped;
	}

	/**
	 * Get v3 types and create to each one a v4 type
	 * 
	 * @return int total inserted
	 */
	private function createTypes($langMapped)
	{
		// Content Types
		$types = $this->source->getObjectTypes();
		$typeMapped = [];

		foreach($types as $type)
		{
			$typeId = $this->destiny->createType($type, $langMapped);
			$typeMapped[$type["id_type"]] = $typeId;
		}

		return $typeMapped;
	}

	/**
	 * Get v3 objects and create to each one a v4 content
	 * 
	 * @return int total inserted
	 */
	private function createContents($config, $langMapped, $types)
	{
		$results = [];

		$params = [
			"types" => $config["types"],
			"aggregator" => 0
		];

		if (isset($config["offset"]))
		{
			$params["offset"] = $config["offset"];
		}

		if (isset($config["limit"]))
		{
			$params["limit"] = $config["limit"];
		}
		
		if (isset($config["published"]))
		{
			$params["published"] = $config["published"];
		}
		
		if (isset($config["id_status"]))
		{
			$params["id_status"] = $config["id_status"];
		}

		// Get objects v3
		$objects = $this->source->getObjects($params);
		$objectsLength = count($objects);
		
		// Get default language
		$defaultLanguage = null;
		if (count($objects) > 0)
		{
			$defaultLanguage = $langMapped[$objects[0]["id_language"]];
		}

		$otherLanguages = $this->filterLanguageDefault($langMapped, $defaultLanguage);
		
		foreach($objects as $object)
		{
			// object aggregate to object with differents languages 
			foreach($otherLanguages as $key => $lang)
			{
				$params = [
					"id_language" => $key,
					"aggregator" => $object["id_object"]
				];

				$object["objectAggregator"] = [];
				$objectAggregator = $this->source->getObjects($params);

				$object["objectAggregator"][$key] = $objectAggregator;
			}

			// check if object type exists on app
			if (isset($types[$object['id_type']]))
			{
				$object["uploadPath"] = $config["uploadPath"];
				$object["urlPath"] = $config["urlPath"];

				// create objects and object languages
				$contentId = $this->destiny->createContent(
					$object,
					$types[$object['id_type']],
					$defaultLanguage,
					$otherLanguages
				);

				$results[] = $contentId;
			}

		}

		return $objectsLength;
	}

	private function createAddons($config)
	{
		$addons = $this->source->getAddons();
		$res = [];
		
		foreach ($addons as $addon)
		{
			if (!in_array($addon["system_name"], $config))
			{
				continue;
			}

			$params = [
				"id_addon" => $addon["id_addon"],
				"name" => $addon["name"],
				"alias" => $addon["system_name"]
			];

			$res[$addon["id_addon"]] = [
				"alias" => $addon["system_name"],
				"addon_id" => $this->destiny->createAddon($params)
			];
		}

		return $res;
	}

	private function createAddonsTypes($addonsMapped, $typeMapped)
	{
		$addonsTypes = $this->source->getAddonsTypes(array_keys($typeMapped));

		$addonsTypes = $this->filterAddonsMappedByAllowed($addonsTypes, array_keys($addonsMapped));
		
		foreach($addonsTypes as $addonType)
		{
			$params = [
				"id_type" => $typeMapped[$addonType["id_type"]],
				"id_addon" => $addonsMapped[$addonType["id_addon"]]["addon_id"],
				"required" => $addonType["required"]
			];

			$this->destiny->createAddonType($params);
		}
	}

	private function createAddonsAttachment($langMapped, $typeMapped, $params = [])
	{	
		$addons = [];

		$addonsOld = $this->source->getAddonsByType("attachments", $params);
		$addonOldTotal = count($addonsOld);

		// group by object
		$addonsGrouped = $this->groupArrayByKey($addonsOld, "id_object");

		foreach($addonsGrouped as $key => $addonOld)
		{
			// get object
			$paramsSync = [
				"limit" => 1,
				"v3Id" => $key
			];

			$sync = $this->destiny->getMigrationSync("content", $paramsSync);
			if (count($sync) === 0)
			{
				print_r("Sync " . $key . "\n");
				continue;
			}

			// get old object
			$paramsOldObejct = [
				"id_object" => $sync[0]["id_cms_v3"],
				"limit" => 1
			];
			$oldObject = $this->source->getObjects($paramsOldObejct);
			
			// get new object
			$paramsNewObejct = [
				"content_id" => $sync[0]["id_cms_v4"],
				"limit" => 1
			];
			$newObject = $this->destiny->getContent($paramsNewObejct);
			
			if (count($oldObject) === 0 || count($newObject) === 0)
			{
				continue;
			}

			// get language
			$paramsSync = [
				"limit" => 1,
				"v3Id" => $oldObject[0]["id_language"]
			];

			$syncLang = $this->destiny->getMigrationSync("language", $paramsSync);
			if (count($syncLang) === 0)
			{
				continue;
			}

			// create attachments
			$path = str_replace("{typeId}", $newObject[0]["type_id"], $params["uploadPath"]);
			$pathUrl = str_replace("{typeId}", $newObject[0]["type_id"], $params["urlPath"]);
			
			$path = str_replace("{contentId}", $sync[0]["id_cms_v4"], $path);
			$pathUrl = str_replace("{contentId}", $sync[0]["id_cms_v4"], $pathUrl);

			foreach($addonOld as $attach)
			{
				if (empty($attach["name"]) && empty($attach["file"]) && empty($attach["type"]))
				{
					continue;
				}

				$file_headers = @get_headers($attach["file"]);
				if (
					$file_headers[0] == 'HTTP/1.0 404 Not Found' ||
					$file_headers[0] == 'HTTP/1.1 302 Moved Temporarily' ||
					$file_headers[0] == 'HTTP/1.1 400 Bad Request'
				)
				{
					continue;
				}
				
				// create attachment 
				$paramsAttachment = [
					"key" => $key,
					"path" => $path,
					"pathUrl" => $pathUrl,
					"id_attach" => $attach["id_attach"],
					"name" => $attach["name"],
					"description" => $attach["description"],
					"file" => $attach["file"],
					"mimetype" => $attach["type"],
					"link" => $attach["link"],
					"orderattachment" => $attach["orderattachment"],
					"language_id" => $syncLang[0]["id_cms_v4"],
					"content_id" => $sync[0]["id_cms_v4"]
				];

				try {
					$addons[] = $this->destiny->createAddonAttachment($paramsAttachment);
				} catch(\Exception $exc) {
					print_r("Addon Error: " . $attach["id_attach"] . "\n");
				}
			}
		}

		return $addonOldTotal;
	}

	private function createAddonsCategories($params, $langMapped, $typeMapped)
	{
		$addonsOld = $this->source->getAddonsCategories($params);

		$categoriesMapped = [];

		foreach($addonsOld as $addon)
		{
			if ($addon["subCategory"] == 0)
			{
				$addon["children"] = $this->filterCategoriesByParent($addonsOld, $addon["id_category"], $typeMapped, $langMapped);
				$addon["type_id"] = $typeMapped[$addon["id_type"]];
				$addon["language_id"] = $langMapped[$addon["id_language"]];
				
				$categoriesMapped[$addon["id_category"]] = $this->destiny->createAddonCategory($addon);
			}
		}

		$categoryObjects = $this->source->getAddonsCategoryObjects($params);
		$categoryObjectsGrouped = $this->groupArrayByKey($categoryObjects, "id_object");
		
		foreach($categoryObjectsGrouped as $idObject => $categoty)
		{
			$content = $this->destiny->getMigrationSync("content", ["v3Id" => $idObject, "limit" => 1]);

			if (count($content) > 0)
			{
				foreach($categoty as $c)
				{
					if (isset($categoriesMapped[$c["id_category"]]))
					{
						$paramsInsert = [
							"category_id" => $categoriesMapped[$c["id_category"]],
							"content_id" => $content[0]["id_cms_v4"]
						];
	
						$this->destiny->createCategoryContent($paramsInsert);
					}
				}
			}
		}

		return $categoryObjects;
	}

	private function createAddonsEvents($config)
	{
		$addons = $this->source->getAddonsEvents($config);

		foreach ($addons as $addon)
		{
			// get object
			$paramsSync = [
				"limit" => 1,
				"v3Id" => $addon["id_object"]
			];

			$sync = $this->destiny->getMigrationSync("content", $paramsSync);
			
			if (count($sync) > 0)
			{
				$paramsCreate = [
					"id_event" => $addon["id_agenda"],
					"start" => $addon["start"],
					"end" => $addon["end"],
					"timezone" => $addon["timezone"],
					"repeating" => $addon["repeating"],
					"content_id" => $sync[0]["id_cms_v4"],
				];
				
				$id = $this->destiny->createAddonEvent($paramsCreate);
			}
		}

		return $addons;
	}

	private function createAddonsMaps($config)
	{
		$addons = $this->source->getAddonsMaps($config);

		foreach($addons as $addon)
		{
			// get object
			$paramsSync = [
				"limit" => 1,
				"v3Id" => $addon["id_object"]
			];

			$sync = $this->destiny->getMigrationSync("content", $paramsSync);
			if (count($sync) > 0)
			{
				$params = [
					"id_maps" => $addon["id_maps"],
					"address" => $addon["address"],
					"locality" => $addon["locality"],
					"latitude" => $addon["latitude"],
					"longitude" => $addon["longitude"],
					"country" => $addon["country"],
					"zipcode" => $addon["zipcode"],
					"content_id" => $sync[0]["id_cms_v4"]
				];

				$id = $this->destiny->createAddonMap($params);
			}
		}

		return $addons;
	}

	/**
	 * Filter array of language and returns all that are not the default.
	 * 
	 * @return array
	 */
	private function filterLanguageDefault($languages, $default)
	{
		$otherLanguages = array_filter($languages, function($k) use ($default) {
			return $k != $default;
		});

		return $otherLanguages;
	}
	
	private function filterAddonsMappedByAllowed($addons, $types)
	{
		$addons = array_filter($addons, function($k) use ($types) {
			return in_array($k["id_addon"], $types);
		});

		return $addons;
	}

	private function groupArrayByKey($array, $key)
	{
		$result = [];
		foreach ($array as $element) {
			$result[$element[$key]][] = $element;
		}

		return $result;
	}

	private function filterCategoriesByParent($array, $id, $typeMapped, $langMapped)
	{
		$arrayFiltered = array_reduce($array, function($a, $item) use ($id) {
			if ($item["subCategory"] === $id)
			{
				$item["type_id"] = $typeMapped[$item["id_type"]];
				$item["language_id"] = $langMapped[$item["id_language"]];

				array_push($a, $item);
			}

			return $a;
		}, []);
		
		return $arrayFiltered;
	}

	private function removeChar($str)
	{
		$unwanted_array = array(
			'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
			'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
			'Þ'=>'B',
			'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
			'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
			'Ğ'=>'G',
			'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
			'Ł'=>'L',
			'Ñ'=>'N', 'Ń'=>'N',
			'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
			'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
			'Ț'=>'T',
			'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
			'Ý'=>'Y',
			'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
			'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
			'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
			'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
			'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
			'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
			'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
			'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
			'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
			'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
			'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
			'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
			'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
			'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
			'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
			'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
			'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
			'ק'=>'q',
			'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
			'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
			'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
			'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
			'в'=>'v', 'ו'=>'v', 'В'=>'v',
			'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
			'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
			'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
		);

		return strtolower(strtr($str, $unwanted_array));
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
}
