<?php

namespace Fyi\Cms\Exceptions;
use Exception; 

class CmsSDKException extends Exception
{
  protected $type = "CmsSDKException";

  public function __construct($message = "SDK Error", $code = 500, $prev = null)
  {
    parent::__construct($message, $code, $prev);
  }
  
  public function getType()
  {
    return "CmsSDKException";
  }
}
