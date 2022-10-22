<?php namespace Model\Multilang;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getRules(): array
	{
		$config = Ml::getConfig();
		$rules = [];

		if ($config and ($config['type'] ?? 'url') === 'url') {
			foreach ($config['langs'] as $l)
				$rules[$l] = $l;
		}

		return [
			'rules' => $rules,
			'controllers' => [],
		];
	}

	/**
	 * @return bool
	 */
	public function makeCache(): bool
	{
		$config = Ml::getConfig();

		if (!in_array($config['type'], ['url', 'session']))
			$this->model->error('Unknown type for Multilang module');

		if ($config['fallback'] and is_string($config['fallback']))
			$config['fallback'] = [$config['fallback']];

		if (!$config['fallback'])
			$config['fallback'] = [$config['default']];

		return (bool)file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config.php', '<?php
$config = ' . var_export($config, true) . ';
');
	}

	/**
	 * Must return a list of modules on which this module depends on, in order to build a correct internal cache
	 *
	 * @return array
	 */
	public function cacheDependencies(): array
	{
		return ['Db'];
	}

	public function getConfigData(): ?array
	{
		return [];
	}
}
