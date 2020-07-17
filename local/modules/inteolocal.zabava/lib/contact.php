<?php


namespace Inteolocal\Zabava;

use Bitrix\Main\PhoneNumber\Formatter;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\PhoneNumber\Format;

/**
 * Class Contact
 * @package Inteolocal\Zabava
 */
class Contact
{

	/**
	 * Метод возвращает id контакта по строгому соответствию фамилии, имени, телефона, e-mail
	 * @param array $fields
	 * @return bool|int
	 */
	private static function guessContactId($fields = array())
	{
		$contactId = false;
		if (is_array($fields) && count(array_filter(array_keys($fields), 'is_string')) > 0)
		{
			if (isset($fields['PHONE']))
			{
				$fields['PHONE'] = Formatter::format(Parser::getInstance()->parse($fields['PHONE'], 'RU'),Format::E164);
			}

			$firstTryKeys = array('LAST_NAME', 'NAME', 'EMAIL');
			$firstTryFields = array();
			foreach ($fields as $key => $field)
			{
				if (in_array(strtoupper($key), $firstTryKeys))
				{
					$firstTryFields[strtoupper($key)] = $field;
				}
			}
			if (count($firstTryFields) > 0)
			{
				$arContacts = \CRest::call(
					'crm.contact.list',
					[
						'order' => [
							"DATE_CREATE" => "ASC"
						],
						'filter' => $firstTryFields,
						'select' => [
							"ID"
						]
					]
				);
				if (is_array($arContacts['result']) && count($arContacts['result']) > 0)
				{
					$foundContact = reset($arContacts['result']);
					if (isset($fields['PHONE']) && $fields['PHONE']!='')
					{
						$arCheckPhone = \CRest::call(
							'crm.duplicate.findbycomm',
							[
								'entity_type' => "CONTACT",
								'type' => "PHONE",
								'values' => [
									$fields['PHONE']
								]
							]
						);
						if (is_array($arCheckPhone['result']) && count($arCheckPhone['result']) > 0)
						{
							if (is_array($arCheckPhone['result']['CONTACT']) && count($arCheckPhone['result']['CONTACT']) > 0)
							{
								if (reset($arCheckPhone['result']['CONTACT']) == $foundContact['ID'])
									return $foundContact['ID'];
							}
						}
					}
					else
					{
						return $foundContact['ID'];
					}
				}
			}
		}
		return $contactId;
	}

	/**
	 * Метод создает контакт в Б24
	 * @param $fields
	 * @return bool|int
	 */
	private static function addCrmContact($fields)
	{
		//TODO add type error
		Logger::addLogMessage("ADD_CRM_CONTACT:START");
		if (is_array($fields) && count(array_filter(array_keys($fields), 'is_string')) > 0)
		{
			$arFields = array(
				'TYPE_ID' => 'CLIENT',
				'OPENED' => 'Y',
				'SOURCE_ID' => 'WEB'
			);

			$addKeys = array('LAST_NAME', 'NAME', 'SECOND_NAME', 'EMAIL', 'PHONE', 'COMPANY');

			foreach ($fields as $key => $field)
			{
				if (in_array(strtoupper($key), $addKeys))
				{
					if (strtoupper($key) == 'PHONE')
					{
						$arFields['PHONE'][] = array(
							"VALUE" => Formatter::format(Parser::getInstance()->parse($field, 'RU'),Format::E164),
							"VALUE_TYPE" => "WORK"
						);
					}
					elseif (strtoupper($key) == 'EMAIL')
					{
						$arFields['EMAIL'][] = array(
							"VALUE" => $field,
							"VALUE_TYPE" => "WORK"
						);
					}
					elseif (strtoupper($key) == 'COMPANY')
					{
						$arFields['UF_CRM_1567254718656'] = $field;
					}
					else
					{
						$arFields[strtoupper($key)] = $field;
					}
				}
			}
			Logger::addLogMessage(array("CREATING_CONTACT" => $arFields));
			$contactId = \CRest::call(
				'crm.contact.add',
				[
					'fields' => $arFields,
					'params' => array()
				]
			);
			//TODO Errors
			if (isset($contactId['result']) && $contactId['result'] > 0)
			{
				Logger::addLogMessage("CREATED_CONTACT: {$contactId['result']}");
				return $contactId['result'];
			}
			else
			{
				Logger::addLogMessage(
					array(
						"FAILED_TO_CREATE_CONTACT" => $arFields,
						"FAILED_TO_CREATE_CONTACT_RESULT" => $contactId
					)
				);
				return false;
			}

		}
		else
		{
			return false;
		}

	}

	/**
	 * Метод возвращает id созданного или существующего контакта в Б24 для заказа в БУС
	 * @param \Bitrix\Sale\Order $order
	 * @return bool|int
	 */
	public static function getOrderContact(\Bitrix\Sale\Order $order)
	{
		Logger::addLogMessage('SAVE_CONTACT_FROM_ORDER:START');
		$propertyCollection = $order->getPropertyCollection();
		$contactName = explode(' ', $propertyCollection->getPayerName()->getValue());
		$contactsFields = array(
			'LAST_NAME' => $contactName[0],
			'NAME' => (isset($contactName[1]) && $contactName[1] != '') ? $contactName[1] : $contactName[1],
			'SECOND_NAME' => (isset($contactName[2]) && $contactName[2] != '') ? $contactName[2] : $contactName[2],
			'PHONE' => $propertyCollection->getPhone()->getValue(),
			'EMAIL' => $propertyCollection->getUserEmail()->getValue()
		);
		foreach ($propertyCollection as $propertyValue)
		{
			$property = $propertyValue->getProperty();
			if ($property['CODE'] == 'COMPANY')
			{
				$contactsFields['COMPANY'] = $propertyValue->getValue();
			}
		}
		$contactId = Contact::guessContactId($contactsFields);
		if ($contactId && $contactId > 0)
		{
			Logger::addLogMessage("FOUND DUPLICATE_CONTACT: {$contactId}");
		}
		else
		{
			$contactId = Contact::addCrmContact($contactsFields);
			if ($contactId && $contactId > 0)
				Logger::addLogMessage("CREATED_NEW_CONTACT: {$contactId}");
		}
		return $contactId;
	}
}