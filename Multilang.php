<?php namespace Model\Multilang;

use Model\Core\Module;
use Model\Core\Globals;

class Multilang extends Module
{
	/** @var string */
	public $lang = null;
	/** @var string[] */
	public $langs;
	/** @var array[] */
	public $tables = [];
	/** @var array */
	public $options = [];
	/** @var array */
	private $dictionary;

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
			if ($this->lang === null)
				$this->setLang($this->options['default']);
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
		return true;
	}

	/**
	 * @return array
	 */
	public function getDictionary(): array
	{
		if ($this->dictionary === null) {
			$this->dictionary = [];

			$dictionaryFile = INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'dictionary.php';
			if (file_exists($dictionaryFile))
				require($dictionaryFile);
		}

		return $this->dictionary;
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

		if ($tags['lang'] == $this->getDefaultLang() or !in_array($tags['lang'], $this->langs))
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
		if ($this->options['type'] == 'url' and in_array($rule, $this->langs) and $this->options['default'] != $rule and $request[0] == $rule) {
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
		if (array_key_exists($table, $this->tables)) {
			return $table . $this->tables[$table]['suffix'];
		} else {
			return null;
		}
	}

	/**
	 * @param string $table
	 * @return mixed|null
	 */
	public function getTableOptionsFor(string $table)
	{
		if (array_key_exists($table, $this->tables)) {
			return $this->tables[$table];
		} else {
			return null;
		}
	}

	/**
	 * @param string $idx
	 * @param array $words
	 * @param string $accessLevel
	 * @return bool
	 */
	public function checkAndInsertWords(string $idx, array $words, string $accessLevel = 'root'): bool
	{
		$this->getDictionary();

		if (!isset($this->dictionary[$idx])) {
			$this->dictionary[$idx] = [
				'words' => [],
				'accessLevel' => $accessLevel,
			];
		}

		$words = $this->normalizeLangsInWords($words);

		foreach ($words as $w => $langs) {
			if (!isset($this->dictionary[$idx]['words'][$w]))
				$this->dictionary[$idx]['words'][$w] = $langs;
			else
				$this->dictionary[$idx]['words'][$w] = array_merge($langs, $this->dictionary[$idx]['words'][$w]);
		}

		return $this->saveDictionary();
	}

	/**
	 * @param array $words
	 * @return array
	 */
	public function normalizeLangsInWords(array $words): array
	{
		foreach ($words as $w => $langs) {
			$default = $langs['en'] ?? $langs[$this->getDefaultLang()] ?? '';
			foreach ($this->langs as $l) {
				if (!isset($langs[$l]))
					$words[$w][$l] = $default;
			}
			foreach ($langs as $l => $word) {
				if (!in_array($l, $this->langs))
					unset($words[$w][$l]);
			}
		}

		return $words;
	}

	/**
	 * @return bool
	 */
	public function normalizeAllLangsInWords(): bool
	{
		$this->getDictionary();

		foreach ($this->dictionary as $sectionIdx => $section) {
			$this->dictionary[$sectionIdx]['words'] = $this->normalizeLangsInWords($section['words']);
		}

		return $this->saveDictionary();
	}

	/**
	 * @return bool
	 */
	public function saveDictionary(): bool
	{
		if (!$this->dictionary === null)
			return false;

		$dictionaryFile = INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Multilang' . DIRECTORY_SEPARATOR . 'dictionary.php';
		if (!is_dir(dirname($dictionaryFile)))
			mkdir(dirname($dictionaryFile), 0777, true);

		$this->trigger('changedDictionary');

		if (!isset($this->dictionary['main'])) {
			$this->dictionary['main'] = [
				'words' => [],
				'accessLevel' => 'user',
			];
		}

		return (bool)file_put_contents($dictionaryFile, "<?php\n\$this->dictionary = " . var_export($this->dictionary, true) . ";\n");
	}

	/**
	 * @param string $section
	 * @param string $word
	 * @param string $lang
	 * @param string $value
	 * @return bool
	 * @throws \Model\Core\Exception
	 */
	public function updateWord(string $section, string $word, string $lang, string $value): bool
	{
		$this->getDictionary();
		if (!isset($this->dictionary[$section]['words'][$word]))
			$this->model->error('Word not found');

		$this->dictionary[$section]['words'][$word][$lang] = $value;
		return $this->saveDictionary();
	}

	/**
	 * @param string $word
	 * @param string|null $lang
	 * @return string
	 * @throws \Model\Core\Exception
	 */
	public function word(string $word, string $lang = null): string
	{
		if (!$lang)
			$lang = $this->lang;
		if (!in_array($lang, $this->langs))
			$this->model->error('Unsupported lang ' . $lang);

		$word = explode('.', $word);
		if (count($word) > 2)
			$this->model->error('There can\'t be more than one dot (.) character in dictionary word');

		$dictionary = $this->getDictionary();

		$word_arr = null;

		if (count($word) == 1) {
			foreach ($dictionary as $sectionIdx => $section) {
				if (isset($section['words'][$word[0]])) {
					$word_arr = $section['words'][$word[0]];
					break;
				}
			}
		} else {
			if (!isset($dictionary[$word[0]]))
				$this->model->error('There is no dictionary section named "' . $word[0] . '"');

			if (isset($dictionary[$word[0]]['words'][$word[1]]))
				$word_arr = $dictionary[$word[0]]['words'][$word[1]];
		}

		if ($word_arr) {
			$possibleLangs = [
				$lang,
			];
			foreach ($this->options['fallback'] as $l) {
				if (!in_array($l, $possibleLangs))
					$possibleLangs[] = $l;
			}

			foreach ($possibleLangs as $l) {
				if ($word_arr[$l] ?? '')
					return $word_arr[$l];
			}

			return '';
		} else {
			return '';
		}
	}

	/**
	 * @param string $section
	 * @return bool
	 */
	public function isUserAuthorized(string $section): bool
	{
		$this->getDictionary();

		if (!isset($this->dictionary[$section]))
			return false;

		return (bool)($this->dictionary[$section]['accessLevel'] === 'user' or DEBUG_MODE);
	}

	/**
	 * @param string $section
	 * @param string $word
	 * @return bool
	 */
	public function deleteWord(string $section, string $word): bool
	{
		$this->getDictionary();
		if (!isset($this->dictionary[$section]))
			return false;
		if (!isset($this->dictionary[$section]['words'][$word]))
			return false;
		unset($this->dictionary[$section]['words'][$word]);

		return $this->saveDictionary();
	}

	/**
	 * @param string $table
	 * @return bool
	 */
	public function checkAndInsertTable(string $table): bool
	{
		if (array_key_exists($table, $this->tables))
			return true;

		$config = parent::retrieveConfig();
		if (array_key_exists($table, $config['tables']) or in_array($table, $config['tables']))
			return true;
		$config['tables'][] = $table;

		$configClass = new Config($this->model);
		$configClass->saveConfig('config', $config);
		return $configClass->makeCache();
	}
}
