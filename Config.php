<?php namespace Model\Multilang;

use Model\Core\Module_Config;

class Config extends Module_Config {
	/** @var string */
	protected $name = 'Multilang';

	/**
	 * @param array $data
	 * @return bool
	 */
	public function install(array $data = []){
		return $this->model->_Db->query('CREATE TABLE IF NOT EXISTS `zk_dictionary` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `lang` char(2) NOT NULL,
		  `k` varchar(100) NOT NULL,
		  `v` text NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
	}

	/**
	 * @return array
	 */
	public function getRules(){
		$config = $this->retrieveConfig();

		$rules = [];
		if($config){
			if($config['type']=='url'){
				foreach($config['langs'] as $l){
					if($l==$config['default'])
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
}
