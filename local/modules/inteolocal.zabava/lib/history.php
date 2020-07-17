<?php


namespace Inteolocal\Zabava;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Inteolocal\Zabava\Internals\HistoryTable;

/**
 * Класс для избежания зацикливания при сохранении сущностей в обработчиках событий
 *
 * @package Inteolocal\Zabava
 */
class History
{
	/**
	 * @var array|false
	 */
	protected $fields = array();
	/**
	 * @var array
	 */
	protected $fieldsChanged = array();
	/**
	 * @var bool
	 */
	protected $isNew = true;

	/**
	 * History constructor.
	 *
	 * @param  int  $entityId ID сущности в БУС
	 * @param  string  $entityType Класс сущности в БУС
	 *
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function __construct($entityId = 0, $entityType = '')
	{
		if (intval($entityId) <= 0)
			throw new ArgumentNullException("entityId");

		if (strlen($entityType) == 0)
			throw new ArgumentNullException("entityType");

		$filter = array(
			'filter' => array(
				'=ENTITY_ID' => intval($entityId),
				'=ENTITY_TYPE' => strval($entityType),
			),
			'select' => array('*'),
		);

		if ($historyDat = HistoryTable::getList($filter)
			->fetch()
		)
		{
			$this->fields = $historyDat;
			$this->isNew = false;
		}
		else
		{
			$this->fields['ENTITY_ID'] = intval($entityId);
			$this->fields['ENTITY_TYPE'] = strval($entityType);
		}
	}

	/**
	 * Возвращает true, если в значимых данных появились изменения
	 * @param string|array $string Строка или массив значимых данных
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function isChanged($string)
	{
		if (is_array($string))
		{
			$string = Json::encode($string);
		}
		else
		{
			$string = strval($string);
		}
		if ($this->isNew)
		{
			$this->fields['STATE'] = $string;
			return true;
		}
		else
		{
			if (hash("md5", $string) != $this->fields['HASH'])
			{
				$this->fieldsChanged['STATE'] = $string;
				return true;
			}
		}
		return false;
	}

	/**
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function save()
	{
		if ($this->fields['STATE'] != '')
		{
			if ($this->isNew)
			{
				$fields = array(
					'DATE_INSERT' => new DateTime(),
					'DATE_UPDATE' => new DateTime(),
					'ENTITY_ID' => $this->fields['ENTITY_ID'],
					'ENTITY_TYPE' => $this->fields['ENTITY_TYPE'],
					'STATE' => $this->fields['STATE'],
					'HASH' => hash("md5", $this->fields['STATE']),
				);
				HistoryTable::add($fields);

			}
			else
			{
				if (count($this->fieldsChanged) > 0)
				{
					$fields = array(
						'DATE_UPDATE' => new DateTime(),
					);
					if (isset($this->fieldsChanged['STATE']))
					{
						$fields['STATE'] = $this->fieldsChanged['STATE'];
						$fields['HASH'] = hash("md5", $this->fieldsChanged['STATE']);
						HistoryTable::update($this->fields['ID'], $fields);
					}
				}
			}
		}
	}

}