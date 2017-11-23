<?php namespace Model\Multilang;

use Model\Core\Module;

class Multilang extends Module {
	public $lang;
	public $langs;
	public $tables = array();
	public $options = array();
	private $dictionary = false;

	public function init($options = []){
		$config = $this->retrieveConfig();

		$this->options = array_merge([
			'langs' => [],
			'tables'=> [],
			'default' =>'it',
			'fallback' => true,
			'type' => 'url',
		], $config);

		$this->options = array_merge($this->options, $options);

		$this->langs = $this->options['langs'];
		if(empty($this->langs))
			$this->model->error('Cannot load Multilang module, at least one language has to be specified.');

		$this->lang = $this->options['default'];
		if($this->options['fallback']===true)
			$this->options['fallback'] = $this->options['default'];

		foreach($this->options['tables'] as $mlt=>$ml){
			if(!isset($ml['fields']))
				$ml = array('fields'=>$ml);

			$this->tables[$mlt] = array_merge(array(
				'keyfield' => 'parent',
				'lang' => 'lang',
				'suffix' => '_texts',
				'fields' => [],
			), $ml);
		}

		if(!in_array($this->options['type'], ['url', 'session']))
			die('Unknown type for Multilang module');

		if($this->options['type']=='session'){
			if(isset($_GET['mlang']) and in_array($_GET['mlang'], $this->langs))
				$_SESSION[SESSION_ID]['zk-lang'] = $_GET['mlang'];

			if(isset($_SESSION[SESSION_ID]['zk-lang']))
				$this->setLang($_SESSION[SESSION_ID]['zk-lang']);
			else
				$this->setLang($this->options['default']);
		}

		$this->model->addPrefixMaker('Multilang');

		if(!isset(\Model\Core\Globals::$data['adminAdditionalPages']))
			\Model\Core\Globals::$data['adminAdditionalPages'] = [];
		\Model\Core\Globals::$data['adminAdditionalPages'][] = [
			'name' => 'Dictionary',
			'controller' => 'ModElDictionary',
			'rule' => 'model-dictionary'
		];
	}

	private function setLang($l){
		if(!in_array($l, $this->langs))
			return false;
		$this->lang = $l;
		return true;
	}

	public function getDictionary(){
		if($this->dictionary===false){
			$this->dictionary = [];
			$dataset = $this->model->_Db->query('SELECT * FROM zk_dictionary WHERE lang = '.$this->model->_Db->quote($this->lang));
			foreach($dataset as $d)
				$this->dictionary[$d['k']] = $d['v'];
		}

		return $this->dictionary;
	}

	public function getPrefix(array $tags = [], array $opt = []){
		if(!isset($tags['lang']) or $this->options['type']!='url')
			return '';

		if($tags['lang']==$this->options['default'] or !in_array($tags['lang'], $this->langs))
			return '';

		return $tags['lang'];
	}

	public function getController(array $request, $rule){
		if($this->options['type']=='url' and in_array($rule, $this->langs) and $this->options['default']!=$rule and $request[0]==$rule){
			$this->setLang($rule);
			array_shift($request);
			return [
				'controller'=>false,
				'prefix'=>$rule,
				'redirect'=>$request,
			];
		}else{
			$this->model->error('Language not recognized.');
		}
	}

	public function getTableFor($table){
		if(array_key_exists($table, $this->tables)){
			return $table.$this->tables[$table]['suffix'];
		}else{
			return null;
		}
	}

	public function getTableOptionsFor($table){
		if(array_key_exists($table, $this->tables)){
			return $this->tables[$table];
		}else{
			return null;
		}
	}
}
