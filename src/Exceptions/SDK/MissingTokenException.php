<?php

namespace Fyi\Cms\Exceptions\SDK;
use Fyi\Cms\Exceptions\CmsSDKException;

class MissingTokenException extends CmsSDKException
{
  protected $message = "Missing Authorization token.";
  protected $code = 400;
  protected $type = "MissingTokenException";

  public function __construct()
  {
      parent::__construct("Missing Authorization token.", $this->code, null);
  }

  public function getType() {
      return $this->type;
  }
}
