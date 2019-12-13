<?php

namespace SkyNetBack\Core\HTTP\Exception;

use SkyNetBack\Core\HTTP\HTTPException;

class Exception404 extends HTTPException {

    public function __construct(\Throwable $previous = null)
    {
        parent::__construct(404, $previous);

        $this->response = [
            'result' => 'error',
            'errors' => [
                [ 'message' => 'Endpoint not found' ],
            ],
        ];
    }

}
