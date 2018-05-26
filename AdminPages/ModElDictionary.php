<?php namespace Model\Multilang\AdminPages;

use Model\Admin\AdminPage;
use Model\Core\Exception;

class ModElDictionary extends AdminPage
{
	public function viewOptions(): array
	{
		return [
			'template-module' => 'Multilang',
			'template' => 'dictionary',
		];
	}

	public function customize()
	{
		if ($this->model->_CSRF->checkCsrf() and isset($_POST['section'])) {
			try {
				if (!$this->model->_Multilang->isUserAuthorized($_POST['section']))
					$this->model->error('You are unauthorized to edit this section');

				if (isset($_POST['word'], $_POST['l'], $_POST['v'])) {
					$this->model->_Multilang->updateWord($_POST['section'], $_POST['word'], $_POST['l'], $_POST['v']);
					die('ok');
				} elseif (isset($_POST['word'], $_POST['words'])) {
					$k = trim($_POST['word']);
					$words = json_decode($_POST['words'], true);

					if ($k and $words) {
						$this->model->_Multilang->checkAndInsertWords($_POST['section'], [
							$k => $words,
						]);
					}

					die('ok');
				} elseif (isset($_POST['delete'])) {
					if ($this->model->_Multilang->deleteWord($_POST['section'], $_POST['delete']))
						die('ok');
					else
						die('Error');
				} else {
					$this->model->error('Unknown action.');
				}
			} catch (Exception $e) {
				die($e->getMessage());
			}
		}
	}
}