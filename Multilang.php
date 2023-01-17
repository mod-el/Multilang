<?php namespace Model\Multilang;

use Model\Core\Module;
use Model\Core\Globals;

class Multilang extends Module
{
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

		if ($this->options['type'] == 'session') {
			if (isset($_GET['mlang']) and in_array($_GET['mlang'], Ml::getLangs()))
				$_SESSION['zk-lang'] = $_GET['mlang'];

			if (!isset($_SESSION['zk-lang'])) {
				if (isset($_COOKIE['mlang']))
					$_SESSION['zk-lang'] = $_COOKIE['mlang'];
				else
					$_SESSION['zk-lang'] = Ml::getDefaultLang();

				setcookie('mlang', $_SESSION['zk-lang'], time() + 60 * 60 * 24 * 90, PATH);
			}

			Ml::setLang($_SESSION['zk-lang']);
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
	 * @param array $tags
	 * @param array $opt
	 * @return mixed|string
	 */
	public function getPrefix(array $tags = [], array $opt = [])
	{
		if (!isset($tags['lang']) or $this->options['type'] != 'url')
			return '';

		if ($tags['lang'] === Ml::getDefaultLang() or !in_array($tags['lang'], Ml::getLangs()))
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
		if ($this->options['type'] === 'url' and in_array($rule, Ml::getLangs()) and $request[0] === $rule) {
			Ml::setLang($rule);
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
	 * @param string $word
	 * @param string|null $lang
	 * @return string
	 */
	public function word(string $word, ?string $lang = null): string
	{
		return Dictionary::get($word, $lang);
	}
}
