<?php namespace Model\Multilang;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	/** @var bool */
	public $configurable = true;

	/**
	 * @throws \Model\Core\Exception
	 */
	protected function assetsList()
	{
		$this->addAsset('config', 'dictionary.php', function () {
			return "<?php\n\$this->dictionary = ['main'=>['words'=>[],'accessLevel'=>'user']];\n";
		});

		$this->addAsset('config', 'config.php', function () {
			return "<?php\n\$config = ['langs'=>['it','en'],'tables'=>[],'default'=>'it','type'=>'url','hide-dictionary'=>false];\n";
		});
	}

	/**
	 * Returns the config template
	 *
	 * @param string $type
	 * @return string
	 */
	public function getTemplate(string $type): ?string
	{
		return 'config';
	}

	/**
	 * @return array
	 * @throws \Exception
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
				foreach ($config['langs'] as $l)
					$rules[$l] = $l;
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
			'new' => [
				'it' => 'Nuovo termine',
				'en' => 'New word',
			],
			'admin-lang' => [
				'it' => 'Lingua pannello:',
				'en' => 'Admin language:',
			],
		]);

		$config = array_merge([
			'langs' => [],
			'tables' => [],
			'default' => 'it',
			'fallback' => ['en'],
			'type' => 'url',
		], $this->retrieveConfig());

		if (!in_array($config['type'], ['url', 'session']))
			$this->model->error('Unknown type for Multilang module');

		if ($config['fallback'] and is_string($config['fallback']))
			$config['fallback'] = [$config['fallback']];

		if (!$config['fallback'])
			$config['fallback'] = [$config['default']];

		$newTablesArray = [];
		foreach ($config['tables'] as $table => $tableData) {
			if (is_numeric($table) and is_string($tableData)) {
				$table = $tableData;
				$tableData = [];
			}
			if (!isset($tableData['fields']))
				$tableData = ['fields' => $tableData];

			$tableData = array_merge([
				'keyfield' => 'parent',
				'lang' => 'lang',
				'suffix' => '_texts',
				'fields' => [],
			], $tableData);

			if (count($tableData['fields']) === 0) {
				try {
					$tableModel = $this->model->_Db->getTable($table . $tableData['suffix']);
					foreach ($tableModel->columns as $columnName => $column) {
						if ($columnName === $tableModel->primary or $columnName === $tableData['keyfield'] or $columnName === $tableData['lang'])
							continue;
						$tableData['fields'][] = $columnName;
					}
				} catch (\Exception $e) {
				}
			}

			$newTablesArray[$table] = $tableData;
		}
		$config['tables'] = $newTablesArray;

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

	/**
	 * Save the configuration
	 *
	 * @param string $type
	 * @param array $data
	 * @return bool
	 * @throws \Exception
	 */
	public function saveConfig(string $type, array $data): bool
	{
		$config = $this->retrieveConfig();

		$originalLanguages = $config['langs'];

		if (!is_array($data['langs']))
			$data['langs'] = explode(',', $data['langs']);

		foreach ($data['langs'] as &$l) {
			if (strlen($l) !== 2)
				$this->model->error($l . ' is not a valid language');

			$l = strtolower($l);
		}
		unset($l);

		if (!in_array($data['default'], $data['langs']))
			$this->model->error($data['default'] . ' is not among chosen languages');

		$config = array_merge($config, $data);

		if (!isset($config['tables']))
			$config['tables'] = [];
		if (!isset($config['type']))
			$config['type'] = 'url';

		$w = file_put_contents(INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'config.php', '<?php
$config = ' . var_export($config, true) . ';
');
		if ($w) {
			$this->model->_Multilang->reloadConfig();
			if ($this->model->_Multilang->normalizeAllLangsInWords()) {
				foreach ($this->model->_Multilang->langs as $l) {
					if (!in_array($l, $originalLanguages)) {
						foreach ($this->model->_Multilang->tables as $t => $opt) {
							$fields = [];
							$fields_prefixed = [];
							foreach ($opt['fields'] as $f) {
								$fields[] = '`' . $f . '`';
								$fields_prefixed[] = 'l.`' . $f . '`';
							}
							$fields = implode(',', $fields);
							$fields = $fields ? ',' . $fields : '';
							$fields_prefixed = implode(',', $fields_prefixed);
							$fields_prefixed = $fields_prefixed ? ',' . $fields_prefixed : '';

							$this->model->_Db->query('INSERT INTO `' . $t . $opt['suffix'] . '`(`' . $opt['keyfield'] . '`,`' . $opt['lang'] . '`' . $fields . ') SELECT t.id,\'' . $l . '\'' . $fields_prefixed . ' FROM `' . $t . '` t LEFT JOIN `' . $t . $opt['suffix'] . '` l ON (l.`' . $opt['keyfield'] . '` = t.id AND l.`' . $opt['lang'] . '` = \'' . $this->model->_Multilang->options['default'] . '\') WHERE t.id NOT IN (SELECT l.`' . $opt['keyfield'] . '` FROM `' . $t . $opt['suffix'] . '` l WHERE l.`' . $opt['lang'] . '` = \'' . $l . '\')');
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
