<?php

namespace Fyi\Cms\Tests;

class Config
{
  private $source;
  private $destiny;
  private $connections;
  private $cms;

  public function __construct($source = "source", $destiny = "destiny")
  {
    $this->source = $source;
    $this->destiny = $destiny;

    $this->cms = new \Fyi\Cms\Cms($this->source, $this->destiny);
    $response = $this->cms->init();
    if ($response) {
      if (isset($response["connections"])) {
        $this->connections = $response["connections"];
      } else {
        throw new \Exception("Invalid config.", 400);
      }
    } else {
      throw new \Exception("Connection Error", 500);
    }
  }

  public function getCms()
  {
    return $this->cms;
  }
}
