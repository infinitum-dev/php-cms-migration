<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\Connection;

use Fyi\Cms\Modules\v4\MigrationSync;
use Fyi\Cms\Modules\Connection\Connection;
use Fyi\Cms\Modules\v3\Language;
use Fyi\Cms\Modules\v3\ObjectTypes;
use Fyi\Cms\Modules\v3\Object;
use Fyi\Cms\Modules\v3\Addons;
use Fyi\Cms\Modules\v3\AddonsAttachment;
use Fyi\Cms\Modules\v3\AddonsCategories;
use Fyi\Cms\Modules\v3\AddonsEvents;
use Fyi\Cms\Modules\v3\AddonsMaps;
use Fyi\Cms\Modules\v3\AddonsFields;

class Source extends Connection
{
    protected $app;
    protected $object;
    protected $objectTypes;
    protected $language;
    protected $addons;
    protected $addonsAttachments;
    protected $addonsCategories;
    protected $addonsEvents;
    protected $addonsMaps;
    protected $addonsFields;
    protected $migrationSync;

    public function __construct($connection)
	{
        parent::__construct($connection);
        
        $this->app = $connection["app"];

        $this->language = new Language($this->connection, $this->app);
        $this->objectTypes = new ObjectTypes($this->connection, $this->app);
        $this->object = new Object($this->connection, $this->app);
        $this->addons = new Addons($this->connection, $this->app);
        $this->addonsAttachments = new AddonsAttachment($this->connection, $this->app);
        $this->addonsCategories = new AddonsCategories($this->connection, $this->app);
        $this->addonsEvents = new AddonsEvents($this->connection, $this->app);
        $this->addonsMaps = new AddonsMaps($this->connection, $this->app);
        $this->addonsFields = new AddonsFields($this->connection, $this->app);
    }

    public function getObjects($params)
    {
        return $this->object->objects($params);
    }

    public function getObjectsToMap($map, $config)
    {
        $params = [
            "published" => 1,
            "id_status" => 1,
            "aggregator" => 0,
            "offset" => $config["offset"],
            "limit" => $config["limit"]
        ];

        if (isset($config["date"]))
        {
            $params["date"] = $config["date"];
        }

        if (isset($map["ignore"]))
        {
            $params["ignore"] = $map["ignore"];
        }
        
        if (isset($map["only"]))
        {
            $params["only"] = $map["only"];
        }

        if (isset($map["type"]))
        {
            $params["types"] = $map["type"];
        }
        
        if (isset($map["object"]))
        {
            $params["id_object"] = $map["object"];
        }
        
        if (isset($map["objects"]))
        {
            $params["objects"] = $map["objects"];
        }

        return $this->object->objects($params);
    }
    
    public function getObjectTypes()
    {
        return $this->objectTypes->objectTypes();
    }
   
    public function getLanguages()
    {
        return $this->language->languages();
    }

    public function getAddons($params = [])
    {
        return $this->addons->get($params);
    }

    public function getAddonsTypes($types)
    {
        return $this->addons->getAddonsTypes($types);
    }

    public function getAddonsByType($addonAlias, $params = [])
    {
        switch($addonAlias) {
            case "attachments":
                return $this->addonsAttachments->get($params);
            default:
                dd("Todo: Source > getAddonsByType " . $addonAlias);
        }
    }

    public function getAddonsAttachmentsCategories()
    {
        return $this->addonsAttachments->getCategories();
    }

    public function getAddonsCategories($params)
    {
        return $this->addonsCategories->get($params);
    }

    public function getAddonsCategoryObjects($params)
    {
        return $this->addonsCategories->getCategoryObjects($params);
    }

    public function getAddonsEvents($params)
    {
        return $this->addonsEvents->get($params);
    }
    
    public function getAddonsMaps($params)
    {
        return $this->addonsMaps->get($params);
    }

    public function getFieldValue($params)
    {
        return $this->addonsFields->getValue($params);
    }
}
