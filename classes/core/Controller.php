<?php

namespace SkyNetBack\Core;

class Controller {

	public function prepare()
	{
		// Empty for now
	}

	public function requestMethod($method)
	{
		$currentMethod = $_SERVER['REQUEST_METHOD'];

		if (!isset($method)) return $currentMethod;
		else return strtolower($currentMethod) == strtolower($method);
	}

	public function respond(array $data, $status = 200)
	{
		header('Content-Type: application/json; charset=utf-8');
		http_response_code($status);

		echo json_encode($data, JSON_PRETTY_PRINT);
	}

	public function respondSuccess($data = null)
	{
		$response = [ 'result' => 'ok' ];

		if (isset($data))
		{
			if (is_array($data)) $response = array_merge($response, $data);
			else $response['data'] = $data;
		}

		$this->respond($response);
	}

	public function respondError($error, $status = 400)
	{
		$response = [ 'result' => 'error' ];

		if (is_array($error)) $response['errors'] = [ $error ];
		else $response['errors'] = [ [ 'message' => $error ] ];

		$this->respond($response, $status);
	}

}
