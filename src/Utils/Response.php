<?php

namespace Fyi\Cms\Utils;

class Response
{
  protected $body;
  protected $success;

  public function __construct($body = [], $success = true)
  {
    $this->body = $body;
    $this->success = $success;
  }

  public function json()
  {
    $data = [];

    if ($this->success)
      return $data;
    else
      return ["error" => $this->body];
  }
}
