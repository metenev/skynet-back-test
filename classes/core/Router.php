<?php

namespace SkyNetBack\Core;

use SkyNetBack\Core\HTTP\Exception\Exception404;

class Router {

	protected $rules;

	protected $paramsRegex;

	public function __construct()
	{
		$this->rules = [];
		$this->paramsRegex = '/{(.*?)}/i';
	}

	public function rule($pattern, array $config)
	{
		$pattern = trim(trim($pattern), '/');

		if (isset($this->rules[ $pattern ]))
		{
			throw new \Exception("Overriding of the rule '{$pattern}'");
		}

		$originalPattern = $pattern;
		$pathParams = [];

		preg_match_all($this->paramsRegex, $pattern, $matches, PREG_SET_ORDER);

		if (isset($matches) && !empty($matches))
		{
			$replacePairs = [];

			foreach ($matches as $match)
			{
				$pathParams[] = $match[1];
				$replacePairs[ $match[0] ] = '([^/]+)';
			}

			$pattern = strtr($pattern, $replacePairs);
		}

		$this->rules[ $originalPattern ] = [
			'pattern' => '#' . $pattern . '#i',
			'params' => $pathParams,
			'config' => $config,
		];
	}

	public function hasRule($path, $trim = true)
	{
		if ($trim)
		{
			$path = trim(trim($path), '/');
		}

		return isset($this->rules[ $path ]);
	}

	public function getRule($path)
	{
		$path = trim(trim($path), '/');

		return $this->hasRule($path, false)
			? $this->rules[ $path ]
			: null;
	}

	public function process()
	{
		$routeConfig = [
			'controller' => 'Index',
			'action' => 'index'
		];

		$path = trim($_SERVER['REQUEST_URI'], '/');

		$query = null;
		$question = strpos($path, '?');

		if ($question !== false)
		{
			$query = substr($path, $question + 1);
			$path = trim(substr($path, 0, $question), '/');
		}

		$root = getenv('ROOT_PATH');

		if (!empty($root))
		{
			$path = preg_replace('#^' . getenv('ROOT_PATH') . '#', '', $path);
			$path = trim($path, '/');
		}

		// Check if whole path is defined as rule

		if ($this->hasRule($path))
		{
			$routeConfig = array_merge($routeConfig, $this->getRule($path));
			$this->processRoute($routeConfig, $path);
			return;
		}

		// Then try to find it

		$targetRule = null;
		$targetRuleParams = null;

		foreach ($this->rules as $rule)
		{
			if (preg_match_all($rule['pattern'], $path, $targetRuleParams, PREG_SET_ORDER))
			{
				$targetRule = $rule;
				break;
			}
		}

		if (!isset($targetRule))
		{
			throw new Exception404();
		}

		$params = [];

		if (isset($targetRuleParams) && !empty($targetRuleParams))
		{
			foreach ($targetRuleParams[0] as $idx => $param)
			{
				if ($idx == 0) continue;
				if (!isset($targetRule['params'][ $idx - 1 ])) continue;

				$paramName = $targetRule['params'][ $idx - 1 ];
				$params[ $paramName ] = $param;
			}
		}

		// Update route config and process it

		if (isset($targetRule['config']['controller']))
		{
			$routeConfig['controller'] = $targetRule['config']['controller'];
		}

		if (isset($targetRule['config']['action']))
		{
			$routeConfig['action'] = $targetRule['config']['action'];
		}

		if (isset($params['action']))
		{
			$routeConfig['action'] = $params['action'];
			unset($params['action']);
		}

		$routeConfig['params'] = $params;

		$this->processRoute($routeConfig, $path);
	}

	protected function processRoute(array $config, $path)
	{
		$controllerName = $config['controller'];
		$actionName = $config['action'];
		$params = isset($config['params']) ? $config['params'] : [];

		// Include controller

		$controllerParts = $this->makeUnifiedPathArray($controllerName);
		$controllerPath = $this->makeUnifiedPathFromUnifiedArray($controllerParts, PATH_APP . 'controller/');

		if (!file_exists($controllerPath))
		{
			throw new Exception404();
		}

		// Create controller

		$controllerName = $this->makeNameFromUnifiedArray($controllerParts, 'Controller');
		$controller = new $controllerName($session);

		// Prepare

		$controller->prepare();

		// Execute action

		$actionName = 'action_' . $actionName;

		if (method_exists($controller, $actionName))
		{
			$controller->$actionName($params);
		}
		else
		{
			throw new Exception404();
		}
	}

	protected function makeUnifiedPathArray($path)
	{
		$parts = explode('/', $path);

		return array_map('ucfirst', $parts);
	}

	protected function makeUnifiedPathFromUnifiedArray(array $parts, $root = PATH_ROOT)
	{
		return $root . implode('/', $parts) . '.php';
	}

	protected function makeNameFromUnifiedArray(array $parts, $namespace = null)
	{
		return '\\SkyNetBack\\' . (isset($namespace) ? $namespace . '\\' : '') . implode('\\', $parts);
	}
}
