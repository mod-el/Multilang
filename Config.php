<?php namespace Model\Multilang;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	/** @var string */
	protected $name = 'Multilang';

	/**
	 * @param array $data
	 * @return bool
	 */
	public function install(array $data = []): bool
	{
		return (bool)file_put_contents(INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'dictionary.php', "<?php\n\$this->>dictionary = [];\n");
	}

	/**
	 * @return array
	 */
	public function getRules(): array
	{
		$config = $this->retrieveConfig();
		$rules = [];

		if ($config and is_array($config)) {
			$config = array_merge([
				'langs' => [],
				'tables' => [],
				'default' => 'it',
				'fallback' => true,
				'type' => 'url',
			], $config);

			if ($config['type'] == 'url') {
				foreach ($config['langs'] as $l) {
					if ($l == $config['default'])
						continue;
					$rules[$l] = $l;
				}
			}
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
		if ($this->model->isLoaded('Multilang')) {
			$this->model->_Multilang->checkAndInsertWords('multilang', [
				'dictionary' => [
					'it' => 'Dizionario',
					'en' => 'Dictionary',
				],
				'label' => [
					'it' => 'Label',
					'en' => 'Label',
				],
				'insert' => [
					'it' => 'Inserisci',
					'en' => 'Insert',
				],
			]);
		}
		return true;
	}
}
