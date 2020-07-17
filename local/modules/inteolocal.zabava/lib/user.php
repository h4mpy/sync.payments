<?php
namespace Inteolocal\Zabava;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use Bitrix\Main\PhoneNumber\Formatter;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\PhoneNumber\Format;

/**
 * Class User
 *
 * Control and authorize users from bitrix24
 *
 * @package Inteolocal\Zabava
 */
class User
{
	/**
	 * Группы в БУС, в которых должен состоять пользователь из Б24
	 * @var array
	 */
	private $allowedGroups = array(1, 5, 7);
	/**
	 * @var
	 */
	private $currentUser;
	/**
	 * Группы в БУС, в которых должен состоять пользователь из Б24
	 * @var array
	 */
	private $userDefaultGroups = array(5, 6, 7);

	/**
	 * User constructor.
	 * @param $currentUser
	 */
	function __construct($currentUser = 0)
	{
		if ($currentUser > 0)
		{
			$this->currentUser = $currentUser;
		}
	}

	/**
	 * c
	 * @param $allowedGroups
	 */
	function setAllowedGroups($allowedGroups)
	{
		$this->allowedGroups = $allowedGroups;
	}

	function setCurrentUser($currentUser)
	{
		$this->currentUser = $currentUser;
	}

	function getUserRight($moduleId)
	{
		$result = false;
		global $USER;
		if ($USER && $USER->IsAuthorized())
		{
			return \CMain::GetUserRight($moduleId);
		}
		return false;
	}

	/**
	 * Группы в БУС, в которых должен состоять пользователь из Б24
	 * @param array $userDefaultGroups
	 */
	public function setUserDefaultGroups($userDefaultGroups)
	{
		$this->userDefaultGroups = $userDefaultGroups;
	}

	/**
	 * @return array
	 */
	public function getUserDefaultGroups()
	{
		return $this->userDefaultGroups;
	}

	/**
	 * @return bool
	 */
	public function isAccessAllowed()
	{
		$arUser = $this->currentUser;
		$arCurrentGroups = $arUser['result']['UF_DEPARTMENT'];
		$arAllowedGroups = $this->allowedGroups;

		if (is_array($arAllowedGroups))
		{
			foreach ($arCurrentGroups as $key => $singleGroup)
			{
				if (in_array($singleGroup, $arAllowedGroups))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return bool|mixed|string
	 */
	public function Authorize()
	{
		global $USER;
		if ($USER && $USER->IsAuthorized())
		{
			return true;
		}
		$arCurrentUser = $this->currentUser;
		$rsUsers = \CUser::GetList(($by = "id"), ($order = "asc"), array("EMAIL" => $arCurrentUser['result']['EMAIL']));
		if ($arUser = $rsUsers->Fetch())
		{
			$arUserGroups = \CUser::GetUserGroup($arUser['ID']);
			$arGroupsNotIn = array_diff($this->userDefaultGroups, $arUserGroups);
			if (count($arGroupsNotIn) > 0)
			{
				\CUser::SetUserGroup($arUser['ID'], array_unique(array_merge($this->userDefaultGroups, $arUserGroups), SORT_REGULAR));
			}

			$USER->Authorize($arUser['ID'], true);
		}
		else
		{
			$newUser = new \CUser;
			$setPass = randString(10);
			$newUserFields = array(
				'LOGIN' => $arCurrentUser['result']['EMAIL'],
				'NAME' => $arCurrentUser['result']['NAME'],
				'LAST_NAME' => $arCurrentUser['result']['LAST_NAME'],
				'EMAIL' => $arCurrentUser['result']['EMAIL'],
				'PASSWORD' => $setPass,
				'CONFIRM_PASSWORD' => $setPass,
				'GROUP_ID' => $this->userDefaultGroups,
				'ACTIVE' => 'Y'
			);

			//TODO Add XML_ID, EXTERNAL_AUTH_ID from bitrix 24
			$newUserID = $newUser->Add($newUserFields);
			if (intval($newUserID) > 0)
			{
				$USER->Authorize($newUserID, true);
				return $newUserID;
			}
			else
			{
				return $newUser->LAST_ERROR;
			}
		}
	}

	public static function getUserFromContact($contactId = 0)
	{
		//Moving to result()
		$result = new Result();
		if ($contactId == 0)
		{
			$result->addError(
				new Error(Loc::getMessage("INTEOLOCAL_USER_EMPTY_ID"), 'INTEOLOCAL_ZABAVA_USER_GET_EMPTY_ID')
			);
			return $result;
		}
		$contact = \CRest::call(
			'crm.contact.get',
			[
				'id' => $contactId
			]
		);
		if (isset($contact['error']))
		{
			$result->addError(
				new Error(Loc::getMessage("INTEOLOCAL_USER_GET_ERROR", array(
					'#CONTACT_ID#' => $contactId,
					'#ERROR_DESCRIPTION#' => $contact['error_description']
				)), 'INTEOLOCAL_ZABAVA_USER_GET_CRM_ERROR')
			);
			return $result;
		}
		else
		{
			$arResult = array(
				'ID' => 1,
				'FIELDS' => array(
					'NAME' => $contact['result']['NAME'],
					'SECOND_NAME' => $contact['result']['SECOND_NAME'],
					'LAST_NAME' => $contact['result']['LAST_NAME'],
					'PERSONAL_PHONE' => (isset($contact['result']['PHONE'][0]['VALUE']))?Formatter::format(Parser::getInstance()->parse($contact['result']['PHONE'][0]['VALUE'], 'RU'),Format::E164):'',
				)
			);
			if (is_array($contact['result']['EMAIL']))
			{
				$contactEmail = reset($contact['result']['EMAIL']);
				if (check_email($contactEmail['VALUE']))
				{
					$arResult['FIELDS']['EMAIL'] = $contactEmail['VALUE'];
					$rsUsers = \CUser::GetList(($by = "id"), ($order = "asc"), array("EMAIL" => $contactEmail['VALUE']));
					if ($arUser = $rsUsers->Fetch())
					{
						$arResult['ID'] = $arUser['ID'];
					}
					else
					{
						$site = SiteTable::getList(array('filter' => array('DEF' => 'Y')))->fetch();
						$newUser = new \CUser;
						$setPass = randString(10);
						$newUserFields = array(
							'LOGIN' => $arResult['FIELDS']['EMAIL'],
							'NAME' => $arResult['FIELDS']['NAME'],
							'SECOND_NAME' => $arResult['FIELDS']['SECOND_NAME'],
							'LAST_NAME' => $arResult['FIELDS']['LAST_NAME'],
							'EMAIL' => $arResult['FIELDS']['EMAIL'],
							'PERSONAL_PHONE' => $arResult['FIELDS']['PERSONAL_PHONE'],
							'PASSWORD' => $setPass,
							'CONFIRM_PASSWORD' => $setPass,
							'ACTIVE' => 'Y',
							'LID' => $site['LID'],
							'XML_ID' => $contactId
						);

						$newUserID = $newUser->Add($newUserFields);
						if (intval($newUserID) > 0)
						{
							$arResult['ID'] = $newUserID;
						}
						else
						{
							$result->addError(
								new Error(Loc::getMessage("INTEOLOCAL_USER_REGISTER", array(
									'#CONTACT_ID#' => $contactId,
									'#ERROR_DESCRIPTION#' => $newUser->LAST_ERROR
								)), 'INTEOLOCAL_ZABAVA_USER_GET_REGISTER')
							);
							return $result;
						}
					}
				}
				else
				{
					$result->addError(
						new Error(Loc::getMessage("INTEOLOCAL_USER_EMAIL_FORMAT", array(
							'#CONTACT_ID#' => $contactId
						)), 'INTEOLOCAL_ZABAVA_USER_GET_EMAIL_FORMAT')
					);
					return $result;
				}
			}
			$result->setData($arResult);
			return $result;
		}
	}
}