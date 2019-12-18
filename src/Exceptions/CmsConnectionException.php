<?php

namespace Fyi\Cms\Exceptions;

use Exception;

class CmsConnectionException extends Exception
{
  protected $type = "CmsConnectionException";
  protected $body;
  protected $status = 400;
  protected $message = "CMS Connection Error";

  public function __construct($body = [])
  {
    if (isset($body->message)) {
      $this->message = $body->message;
    }

    if (isset($body->type)) {
      $this->type = $body->type;
    }

    if (isset($body->status)) {
      $this->status = $body->status;
    }

    parent::__construct($this->message, $this->status, null);
    $this->body = $body;
  }

  public function getType()
  {
    return "CmsConnectionException";
  }

  public function getBody()
  {
    return $this->body;
  }
}
