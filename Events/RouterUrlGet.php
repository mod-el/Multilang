<?php namespace Model\Multilang\Events;

use Model\Events\AbstractEvent;

class ChangedDictionary extends AbstractEvent
{
	public function getData(): array
	{
		return [];
	}
}
