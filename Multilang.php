<?php namespace Model\Multilang;

use Model\Core\Module;
use Model\Core\Globals;

class Multilang extends Module
{
	public string $lang;
	public array $langs;
	public array $tables = [];
	public array $options = [];

	/**
	 * @param array $options
	 * @throws \Exception
	 */
	public function init(array $options)
	{
		$this->reloadConfig($options);

		$this->model->addPrefixMaker('Multilang');

		if (!($this->options['hide-dictionary'] ?? false) or DEBUG_MODE) {
			if (!isset(Globals::$data['adminAdditionalPages']))
				Globals::$data['adminAdditionalPages'] = [];

			Globals::$data['adminAdditionalPages'][] = [
				'name' => 'Dictionary',
				'page' => 'ModElDictionary',
				'rule' => 'model-dictionary',
			];
		}
	}

	/**
	 * @param array $options
	 * @return bool
	 * @throws \Exception
	 */
	public function reloadConfig(array $options = []): bool
	{
		parent::reloadConfig();

		$config = array_merge([
			'langs' => [],
			'tables' => [],
			'default' => 'it',
			'fallback' => ['en'],
			'type' => 'url',
		], $this->retrieveConfig());

		$this->options = array_merge($config, $options);

		$this->langs = $this->options['langs'];
		$this->tables = $this->options['tables'];

		if ($this->options['type'] == 'session') {
			if (isset($_GET['mlang']) and in_array($_GET['mlang'], $this->langs))
				$_SESSION['zk-lang'] = $_GET['mlang'];

			if (!isset($_SESSION['zk-lang'])) {
				if (isset($_COOKIE['mlang']))
					$_SESSION['zk-lang'] = $_COOKIE['mlang'];
				else
					$_SESSION['zk-lang'] = $this->getDefaultLang();

				setcookie('mlang', $_SESSION['zk-lang'], time() + 60 * 60 * 24 * 90, PATH);
			}

			$this->setLang($_SESSION['zk-lang']);
		} else {
			if (!isset($this->lang))
				$this->setLang($this->getDefaultLang());
		}

		return true;
	}

	/**
	 * Overrides standard behaviour: for this module, config data comes from cache
	 *
	 * @return array
	 */
	public function retrieveConfig(): array
	{
		if (file_exists(INCLUDE_PATH . 'model' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config.php')) {
			require(INCLUDE_PATH . 'model' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config.php');
			if (!isset($config) or !is_array($config))
				return [];

			return $config;
		} else {
			return [];
		}
	}

	/**
	 * @return string
	 */
	private function getDefaultLang(): string
	{
		$browserLang = null;
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$browserLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
			$this->trigger('browserLanguage', ['lang' => $browserLang]);
		}

		if ($browserLang and in_array($browserLang, $this->langs))
			return $browserLang;
		else
			return $this->options['default'];
	}

	/**
	 * @param string $l
	 * @return bool
	 */
	public function setLang(string $l): bool
	{
		if (!in_array($l, $this->langs))
			return false;
		$this->trigger('setLang', ['lang' => $l]);
		$this->lang = $l;
		Ml::setLang($l);
		return true;
	}

	/**
	 * @param array $tags
	 * @param array $opt
	 * @return mixed|string
	 */
	public function getPrefix(array $tags = [], array $opt = [])
	{
		if (!isset($tags['lang']) or $this->options['type'] != 'url')
			return '';

		if ($tags['lang'] === $this->getDefaultLang() or !in_array($tags['lang'], $this->langs))
			return '';

		return $tags['lang'];
	}

	/**
	 * @param array $request
	 * @param string $rule
	 * @return array|null
	 */
	public function getController(array $request, string $rule): ?array
	{
		if ($this->options['type'] === 'url' and in_array($rule, $this->langs) and $request[0] === $rule) {
			$this->setLang($rule);
			array_shift($request);

			return [
				'controller' => false,
				'prefix' => $rule,
				'redirect' => $request,
			];
		} else {
			$this->model->error('Language not recognized.');
		}
	}

	/**
	 * @param string $table
	 * @return null|string
	 */
	public function getTableFor(string $table)
	{
		if (array_key_exists($table, $this->tables))
			return $table . $this->tables[$table]['suffix'];
		else
			return null;
	}

	/**
	 * @param string $table
	 * @return mixed|null
	 */
	public function getTableOptionsFor(string $table)
	{
		if (array_key_exists($table, $this->tables))
			return $this->tables[$table];
		else
			return null;
	}

	/**
	 * @param string $word
	 * @param string|null $lang
	 * @return string
	 */
	public function word(string $word, ?string $lang = null): string
	{
		return Dictionary::get($word, $lang);
	}

	/**
	 * @param string $table
	 * @return bool
	 */
	public function checkAndInsertTable(string $table): bool
	{
		if (array_key_exists($table, $this->tables))
			return true;

		$config = Ml::getConfig();
		if (array_key_exists($table, $config['tables']) or in_array($table, $config['tables']))
			return true;

		$config['tables'][] = $table;

		\Model\Config\Config::set('multilang', $config);

		$configClass = new Config($this->model);
		return $configClass->makeCache();
	}
}
