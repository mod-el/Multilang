<?php namespace Model\Multilang\AdminPages;

use Model\Admin\AdminPage;
use Model\Multilang\Dictionary;

class ModElDictionary extends AdminPage
{
	public function customize()
	{
		$this->model->viewOptions['template-module'] = 'Multilang';
		$this->model->viewOptions['template'] = 'dictionary';
		$this->model->viewOptions['cache'] = false;
	}

	public function editWord(array $payload)
	{
		if (empty($payload['section']) or empty($payload['word']) or empty($payload['l']))
			$this->model->error('Bad data', ['code' => 400]);

		if (!Dictionary::isUserAuthorized($payload['section']))
			$this->model->error('You are unauthorized to edit this section', ['code' => 401]);

		Dictionary::set($payload['section'], $payload['word'], [$payload['l'] => $payload['v']]);

		return ['success' => true];
	}

	public function newWord(array $payload)
	{
		if (empty($payload['section']) or empty($payload['word']) or empty($payload['words']) or !is_array($payload['words']))
			$this->model->error('Bad data', ['code' => 400]);

		$k = trim($payload['word']);

		if ($k and $payload['words'])
			Dictionary::set($payload['section'], $k, $payload['words']);

		return ['success' => true];
	}

	public function deleteWord(array $payload)
	{
		if (empty($payload['section']) or empty($payload['word']))
			$this->model->error('Bad data', ['code' => 400]);

		if (!Dictionary::isUserAuthorized($payload['section']))
			$this->model->error('You are unauthorized to edit this section', ['code' => 401]);

		Dictionary::delete($payload['section'], $payload['word']);

		return ['success' => true];
	}

	public function changeAdminLang(array $payload)
	{
		if (empty($payload['lang']) or !in_array($payload['lang'], $this->model->_Multilang->langs))
			$this->model->error('Invalid lang');

		setcookie('admin-lang', $payload['lang'], time() + 60 * 60 * 24 * 365 * 10, $this->model->_AdminFront->getUrlPrefix());

		return ['success' => true];
	}
}
