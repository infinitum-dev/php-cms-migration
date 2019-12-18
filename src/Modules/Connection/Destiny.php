<?php

/**
 * FYI Infinitum SDK WEB
 *
 * @package FYI
 * @subpackage Infinitum SDK
 * @since 0.0.1
 */

namespace Fyi\Cms\Modules\Connection;

use Fyi\Cms\Modules\Connection\Connection;
use Fyi\Cms\Modules\v4\MigrationSync;
use Fyi\Cms\Modules\v4\Language;
use Fyi\Cms\Modules\v4\Type;
use Fyi\Cms\Modules\v4\Content;
use Fyi\Cms\Modules\v4\Addons;
use Fyi\Cms\Modules\v4\AddonsAttachment;
use Fyi\Cms\Modules\v4\AddonsCategory;
use Fyi\Cms\Modules\v4\AddonsEvent;
use Fyi\Cms\Modules\v4\AddonsMap;
use Fyi\Cms\Modules\v4\AddonsFields;

class Destiny extends Connection
{
	protected $migrationSync;
    protected $language;
	protected $content;
	protected $type;
	protected $addons;
	protected $addonsAttachment;
	protected $addonsCategories;
	protected $addonsMaps;
	protected $addonsFields;

    public function __construct($connection)
	{
		parent::__construct($connection);

        $this->migrationSync = new MigrationSync($this->connection);
		$this->language = new Language($this->connection);
		$this->type = new Type($this->connection);
        $this->content = new Content($this->connection);
        $this->addons = new Addons($this->connection);
        $this->addonsAttachment = new AddonsAttachment($this->connection);
        $this->addonsCategories = new AddonsCategory($this->connection);
        $this->addonsEvents = new AddonsEvent($this->connection);
        $this->addonsMaps = new AddonsMap($this->connection);
        $this->addonsFields = new AddonsFields($this->connection);
    }

    public function getMigrationSync($type, $params)
    {
        return $this->migrationSync->get($type, null, null, $params);
    }

    public function getContent($params)
    {
        return $this->content->get($params);
    }
    
    public function getContentLanguages($params)
    {
        return $this->content->getContentLanguage($params);
    }

    public function getContentAddons($params)
    {
       return  $this->content->getAddons($params);
    }

    public function updateContent($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $this->content->update($params);

            // Commit transaction
            $this->connection->commit();
        } catch(\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function moveContentsFiles($params)
    {
        $this->content->moveContentsFiles($params);
    }

    public function removeAllAlias()
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $this->content->removeAllAlias();

            // Commit transaction
            $this->connection->commit();
        } catch(\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }
   
    public function createLanguages($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $lang = $this->language->create($params);

            // Commit transaction
            $this->connection->commit();

            return $lang;
        } catch(\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function getType($params)
    {
        return $this->type->getTypeLanguage($params);
    }
    
    public function createType($params, $langMapped)
    {
        // Begin transaction
        $this->connection->beginTransaction();
        
        try {
            $type = $this->type->createType($params, $langMapped);
            
            // Commit transaction
            $this->connection->commit();

            return $type;
        } catch(\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createContent($params, $types, $defaultLanguage, $otherLanguages)
    {
        // Begin transaction
        $this->connection->beginTransaction();
        
        try {
            $content = $this->content->createContent($params, $types, $defaultLanguage, $otherLanguages);
            
            // Commit transaction
            $this->connection->commit();
            
            return $content;
        } catch(\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createAddon($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon = $this->addons->create($params);

            // Commit transaction
            $this->connection->commit();

            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }
    
    public function createAddonType($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon =  $this->addons->createAddonType($params);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createAddonAttachment($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $attach = $this->addonsAttachment->create($params);

            // Commit transaction
            $this->connection->commit();
    
            return $attach;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createAddonCategory($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon =  $this->addonsCategories->create($params);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createCategoryContent($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon = $this->addonsCategories->createCategoryContent($params);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createAddonEvent($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon = $this->addonsEvents->create($params);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createAddonMap($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon = $this->addonsMaps->create($params);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createAddonField($params, $type)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon = $this->addonsFields->create($params, $type);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function createFieldValue($params)
    {
        // Begin transaction
        $this->connection->beginTransaction();

        try {
            $addon = $this->addonsFields->createFieldValue($params);

            // Commit transaction
            $this->connection->commit();
    
            return $addon;
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollBack();

            throw $e;
        }
    }
}
