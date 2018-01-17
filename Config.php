<?php namespace Model\Multilang;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	/** @var bool */
	public $configurable = true;

	/**
	 * @param array $data
	 * @return bool
	 */
	public function install(array $data = []): bool
	{
		return (bool)file_put_contents(INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'dictionary.php', "<?php\n\$this->>dictionary = [];\n");
	}

	/**
	 * Returns the config template
	 *
	 * @param array $request
	 * @return string
	 */
	public function getTemplate(array $request)
	{
		return 'config';
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
				'delete_confirmation' => [
					'it' => 'Sicuro di voler eliminare?',
					'en' => 'Are you sure?',
				],
			]);
		}
		return true;
	}

	/**
	 * Save the configuration
	 *
	 * @param string $type
	 * @param array $data
	 * @return bool
	 * @throws \Model\Core\Exception
	 */
	public function saveConfig(string $type, array $data): bool
	{
		$config = $this->retrieveConfig();

		$originalLanguages = $config['langs'];

		$langs = explode(',', $data['langs']);
		foreach ($langs as &$l) {
			if (strlen($l) !== 2)
				$this->model->error($l . ' is not a valid language');

			$l = strtolower($l);
		}
		unset($l);

		$defaultLang = $data['default'];
		if (!in_array($defaultLang, $langs))
			$this->model->error($defaultLang . ' is not among chosen languages');

		$config['langs'] = $langs;
		$config['default'] = $defaultLang;

		if (!isset($config['tables']))
			$config['tables'] = [];
		if (!isset($config['type']))
			$config['type'] = 'url';

		$configFileDir = INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Multilang';
		if (!is_dir($configFileDir))
			mkdir($configFileDir, 0777, true);

		$w = file_put_contents($configFileDir . DIRECTORY_SEPARATOR . 'config.php', '<?php
$config = ' . var_export($config, true) . ';
');
		if ($w) {
			$this->model->_Multilang->reloadConfig();
			if ($this->model->_Multilang->normalizeAllLangsInWords()) {
				foreach ($this->model->_Multilang->langs as $l) {
					if (!in_array($l, $originalLanguages)) {
						foreach($this->model->_Multilang->tables as $t => $opt){
							$fields = [];
							$fields_prefixed = [];
							foreach($opt['fields'] as $f){
								$fields[] = '`'.$f.'`';
								$fields_prefixed[] = 'l.`'.$f.'`';
							}
							$fields = implode(',', $fields);
							$fields = $fields ? ','.$fields : '';
							$fields_prefixed = implode(',', $fields_prefixed);
							$fields_prefixed = $fields_prefixed ? ','.$fields_prefixed : '';

							$this->model->_Db->query('INSERT INTO `'.$t.$opt['suffix'].'`(`'.$opt['keyfield'].'`,`'.$opt['lang'].'`'.$fields.') SELECT t.id,\''.$l.'\''.$fields_prefixed.' FROM `'.$t.'` t LEFT JOIN `'.$t.$opt['suffix'].'` l ON (l.`'.$opt['keyfield'].'` = t.id AND l.`'.$opt['lang'].'` = \''.$this->model->_Multilang->options['default'].'\') WHERE t.id NOT IN (SELECT l.`'.$opt['keyfield'].'` FROM `'.$t.$opt['suffix'].'` l WHERE l.`'.$opt['lang'].'` = \''.$l.'\')');
						}
					}
				}

				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
