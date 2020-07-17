<?php


namespace Inteolocal\Zabava;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;

/**
 * Class Deal
 * @package Inteolocal\Zabava
 */
class Deal
{
	/**
	 * Метод создает сделку в Б24 и возвращает id созданной сделки.
	 * @param array $fields Массив полей для метода crm.deal.add
	 * @param bool $addEvent Регистрировать событие в живой ленте и отправлять уведомление ответственному
	 * @link https://dev.1c-bitrix.ru/rest_help/crm/cdeals/crm_deal_add.php
	 * @return bool|int Возвращает id созданной сделки либо false
	 */
	public static function addCrmDeal($fields = array(), $addEvent = false)
	{
		$result = false;
		$params = array();
		if ($addEvent === true) $params = array('REGISTER_SONET_EVENT' => 'Y');
		$dealId = \CRest::call(
			'crm.deal.add',
			[
				'fields' => $fields,
				'params' => $params
			]
		);
		if (isset($dealId['result']) && $dealId['result'] > 0)
		{
			$result = $dealId['result'];

		}
		return $result;
	}

	/**
	 * Метод возвращает id сделки в Б24 по id заказа в БУС
	 * @param \Bitrix\Sale\Order $order
	 * @return int id сделки, либо false
	 */
	public static function getByOrder(\Bitrix\Sale\Order $order)
	{
		$result = false;
		$orderId = $order->getId();
		if ($orderId > 0)
		{
			$additional = $order->getField("ADDITIONAL_INFO");
			if ($additional != '')
			{
				try
				{
					$additionalJson = Json::decode($additional);
					if (isset($additionalJson['CRM_DEAL_ID']) && intval($additionalJson['CRM_DEAL_ID']) > 0)
					{
						$result = intval($additionalJson['CRM_DEAL_ID']);
					}
				}
				catch (ArgumentException $e)
				{
					Logger::addLogMessage(array('INTEOLOCAL_JSON_DECODE_ERROR' => $e->getMessage()));
				}
			}
			if ($result === false)
			{
				$dealList = \CRest::call(
					'crm.deal.list',
					[
						'order' => ['ID' => 'ASC'],
						'filter' => ['ORIGIN_ID' => $orderId],
						'select' => ['ID']
					]
				);
				if (isset($dealList['total']) && $dealList['total'] > 0)
				{
					$dealItem = reset($dealList['result']);
					$result = $dealItem['ID'];
				}
			}
		}
		return $result;
	}
}