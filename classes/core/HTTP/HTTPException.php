<?php

namespace SkyNetBack\Core\HTTP;

use SkyNetBack\Core\View;

class HTTPException extends \Exception {

    protected $response;

    public function __construct($code = 500, \Throwable $previous = null)
    {
        parent::__construct('', $code, $previous);
    }

    public function hasResponse()
    {
        return isset($this->response);
    }

    public function getResponse()
    {
        return $this->response;
    }

}
