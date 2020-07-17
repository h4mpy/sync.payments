<?php
namespace Inteolocal\Zabava\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EventlogTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'inteolocal_zabava_eventlog';
	}

	public static function getMap()
	{
		global $DB;
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				)
			),
			new Entity\IntegerField('ENTITY_ID', array(
				'required' => true,
				)
			),
			new Entity\StringField(
				'ENTITY_TYPE',
				array(
					'required' => true,
				)
			),
			new Entity\StringField(
				'EVENT',
				array(
					'required' => true,
				)
			),
			new Entity\DatetimeField(
				'DATE',
				array(
					'required' => true,
				)
			),
			new Entity\TextField(
				'ADDITIONAL'
			),
		);
	}
}