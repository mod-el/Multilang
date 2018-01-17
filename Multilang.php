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
	 * @throws \Model\Core\Exception
	 */
	public function init(array $options)
	{
		$this->reloadConfig($options);

		if ($this->options['type'] == 'session') {
			if (isset($_GET['mlang']) and in_array($_GET['mlang'], $this->langs))
				$_SESSION[SESSION_ID]['zk-lang'] = $_GET['mlang'];

			if (isset($_SESSION[SESSION_ID]['zk-lang']))
				$this->setLang($_SESSION[SESSION_ID]['zk-lang']);
			else
				$this->setLang($this->options['default']);
		}

		$this->model->addPrefixMaker('Multilang');

		if (!isset(Globals::$data['adminAdditionalPages']))
			Globals::$data['adminAdditionalPages'] = [];
		Globals::$data['adminAdditionalPages'][] = [
			'name' => 'Dictionary',
			'controller' => 'ModElDictionary',
			'rule' => 'model-dictionary'
		];
	}

	/**
	 * @param array $options
	 * @return bool
	 * @throws \Model\Core\Exception
	 */
	public function reloadConfig(array $options = []): bool
	{
		parent::reloadConfig();

		$config = $this->retrieveConfig();

		$this->options = array_merge([
			'langs' => [],
			'tables' => [],
			'default' => 'it',
			'fallback' => true,
			'type' => 'url',
		], $config);

		$this->options = array_merge($this->options, $options);

		$this->langs = $this->options['langs'];
		if (empty($this->langs))
			$this->model->error('Cannot load Multilang module, at least one language has to be specified.');

		if ($this->lang === null)
			$this->lang = $this->options['default'];
		if ($this->options['fallback'] === true)
			$this->options['fallback'] = $this->options['default'];

		foreach ($this->options['tables'] as $mlt => $ml) {
			if (!isset($ml['fields']))
				$ml = ['fields' => $ml];

			$this->tables[$mlt] = array_merge([
				'keyfield' => 'parent',
				'lang' => 'lang',
				'suffix' => '_texts',
				'fields' => [],
			], $ml);
		}

		if (!in_array($this->options['type'], ['url', 'session']))
			die('Unknown type for Multilang module');

		return true;
	}

	/**
	 * @param string $l
	 * @return bool
	 */
	private function setLang(string $l): bool
	{
		if (!in_array($l, $this->langs))
			return false;
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

		if ($tags['lang'] == $this->options['default'] or !in_array($tags['lang'], $this->langs))
			return '';

		return $tags['lang'];
	}

	/**
	 * @param array $request
	 * @param string $rule
	 * @return array|bool|string
	 * @throws \Model\Core\Exception
	 */
	public function getController(array $request, string $rule)
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
	 */
	public function checkAndInsertWords(string $idx, array $words, string $accessLevel = 'root')
	{
		$this->getDictionary();

		if (!isset($this->dictionary[$idx])) {
			$this->dictionary[$idx] = [
				'words' => [],
			];
		}

		$this->dictionary[$idx]['accessLevel'] = $accessLevel;

		$words = $this->normalizeLangsInWords($words);

		foreach ($words as $w => $langs) {
			if (!isset($this->dictionary[$idx]['words'][$w]))
				$this->dictionary[$idx]['words'][$w] = $langs;
			else
				$this->dictionary[$idx]['words'][$w] = array_merge($langs, $this->dictionary[$idx]['words'][$w]);
		}

		$this->saveDictionary();
	}

	/**
	 * @param array $words
	 * @return array
	 */
	public function normalizeLangsInWords(array $words): array
	{
		foreach ($words as $w => $langs) {
			$default = $langs['en'] ?? $langs[$this->options['default']] ?? '';
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

		if (count($word) == 1) {
			foreach ($dictionary as $sectionIdx => $section) {
				if (isset($section['words'][$word[0]]))
					return $section['words'][$word[0]][$lang] ?? '';
			}

			return '';
		} else {
			if (!isset($dictionary[$word[0]]))
				$this->model->error('There is no dictionary section named "' . $word[0] . '"');

			return $dictionary[$word[0]]['words'][$word[1]][$lang] ?? '';
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
}
