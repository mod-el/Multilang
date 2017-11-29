<?php
class ModElDictionaryController extends \Model\Admin\AdminController{
	function customize(){
		$this->viewOptions['template'] = INCLUDE_PATH.'model/Multilang/templates/dictionary';
	}

	function post(){
		if(checkCsrf() and isset($_POST['k'], $_POST['l'], $_POST['v'])){
			if($this->model->_Db->update('zk_dictionary', [
				'k'=>$_POST['k'],
				'lang'=>$_POST['l'],
			], [
				'v'=>$_POST['v'],
			])){
				die('ok');
			}else{
				die('Error in updating');
			}
		}elseif (checkCsrf() and isset($_POST['k'], $_POST['words'])){
			$words = json_decode($_POST['words'], true);
			$k = trim($_POST['k']);
			if($k){
				foreach($words as $lang => $v){
					$this->model->_Db->insert("zk_dictionary", [
						'k'=> $k,
						'v' => $v,
						'lang' => $lang,
					]);
				}
			}
		}else {
			die('Error.');
		}
	}
}
