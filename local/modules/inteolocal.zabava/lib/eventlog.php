<?php


namespace Inteolocal\Zabava;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Inteolocal\Zabava\Internals\EventlogTable;

class Eventlog
{
	protected $fields = array();
	protected $isNew = true;

	public function __construct($entityId = 0, $entityType = 'payment', $event = 'sendlink', $additional = '')
	{
		if (intval($entityId) <= 0)
			throw new ArgumentNullException("entityId");

		if (strlen($entityType) == 0)
			throw new ArgumentNullException("entityType");

		if (strlen($event) == 0)
			throw new ArgumentNullException("event");

		$filter = array(
			'filter' => array(
				'=ENTITY_ID' => intval($entityId),
				'=ENTITY_TYPE' => strval($entityType),
				'=EVENT' => strval($event),
			),
			'select' => array('*'),
		);
		if ($eventRes = EventlogTable::getList($filter)
			->fetch()
		)
		{
			$this->fields = $eventRes;
			$this->isNew = false;
		}
		else
		{
			$this->fields['ENTITY_ID'] = intval($entityId);
			$this->fields['ENTITY_TYPE'] = strval($entityType);
			$this->fields['EVENT'] = strval($event);
			$this->fields['ADDITIONAL'] = strval($additional);
		}
	}

	public function getPaymentSendDate($format = '')
	{
		$result = false;
		if ($this->isNew == false && $this->fields['ENTITY_TYPE'] == 'payment' && $this->fields['EVENT'] == 'sendlink')
		{
			//$objDateTime = DateTime::createFromPhp($this->fields['DATE']);
			if ($format != '')
			{
				$result = strtolower(FormatDate(
					$format,
					$this->fields['DATE']->getTimestamp()
				));
			}
			else
			{
				$result = $this->fields['DATE']->toString();
			}
		}
		return $result;
	}

	public function setAdditional($additional)
	{
		if (is_array($additional))
		{
			$this->fields['ADDITIONAL'] = Json::encode($additional);
		}
		else
		{
			$this->fields['ADDITIONAL'] = $additional;
		}
	}

	public function getAdditional()
	{
		$result = array();
		if ($this->fields['ADDITIONAL'] != '')
		{
			try
			{
				$result = Json::decode($this->fields['ADDITIONAL']);
			}
			catch (ArgumentException $e)
			{
				//$result = $this->fields['ADDITIONAL'];
			}
		}
		return $result;
	}

	public function save()
	{
		$date = new DateTime();
		if ($this->isNew)
		{

			$fields = array(
				'DATE' => $date,
				'ENTITY_ID' => $this->fields['ENTITY_ID'],
				'ENTITY_TYPE' => $this->fields['ENTITY_TYPE'],
				'EVENT' => $this->fields['EVENT'],
				'ADDITIONAL' => $this->fields['ADDITIONAL'],
			);
			EventlogTable::add($fields);
			$this->isNew = false;
			$this->fields['DATE'] = $date;
		}
		else
		{
			$fields = array(
				'DATE' => $date,
				'ADDITIONAL' => $this->fields['ADDITIONAL'],
			);
			EventlogTable::update($this->fields['ID'], $fields);
			$this->fields['DATE'] = $date;
		}
	}

}