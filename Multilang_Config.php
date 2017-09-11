<?php
namespace Model;

class Multilang_Config extends Module_Config {
	protected $name = 'Multilang';

	public function install(array $data=[]){
		return $this->model->_Db->query('CREATE TABLE IF NOT EXISTS `zk_dictionary` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `lang` char(2) NOT NULL,
		  `k` varchar(100) NOT NULL,
		  `v` text NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
	}

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
			'rules'=>$rules,
			'controllers'=>[],
		];
	}
}
