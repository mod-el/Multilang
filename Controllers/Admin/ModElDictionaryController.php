<?php namespace Model\Multilang\Controllers\Admin;

use Model\Admin\Controllers\AdminController;
use Model\Core\Exception;

class ModElDictionaryController extends AdminController
{
	public function customize()
	{
		$this->viewOptions['template-module'] = 'Multilang';
		$this->viewOptions['template'] = 'dictionary';
	}

	public function post()
	{
		if (checkCsrf() and isset($_POST['section'], $_POST['word'], $_POST['l'], $_POST['v'])) {
			try {
				if (!$this->model->_Multilang->isUserAuthorized($_POST['section']))
					$this->model->error('Unauthorized');

				$this->model->_Multilang->updateWord($_POST['section'], $_POST['word'], $_POST['l'], $_POST['v']);
				die('ok');
			} catch (Exception $e) {
				die($e->getMessage());
			}
		} elseif (checkCsrf() and isset($_POST['section'], $_POST['word'], $_POST['words'])) {
			try {
				if (!$this->model->_Multilang->isUserAuthorized($_POST['section']))
					$this->model->error('Unauthorized');

				$k = trim($_POST['word']);
				$words = json_decode($_POST['words'], true);

				if ($k and $words) {
					$this->model->_Multilang->checkAndInsertWords($_POST['section'], [
						$k => $words,
					]);
				}

				die('ok');
			} catch (Exception $e) {
				die($e->getMessage());
			}
		} else {
			die('Error.');
		}
	}
}
