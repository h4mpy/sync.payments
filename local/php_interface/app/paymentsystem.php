<?php

use Bitrix\Main\Web\Json;
use Bitrix\Sale\Order;
use Inteolocal\Zabava\User;

Header('Access-Control-Allow-Origin: *');
//header("HTTP/1.0 404 Not Found");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once('crest.php');

$hasAccess = false;
$bitrixRights = 'N';
$payments = array();
$deal = array();
$contacts = array();
$order = array();

if (Bitrix\Main\Loader::includeModule('inteolocal.zabava'))
{
	$bitrixUser = new User();
	$bitrixRights = $bitrixUser->getUserRight('sale');
	if (in_array(
		$bitrixRights,
		array(
			'W',
			'R',
		)
	)
	)
	{
		$hasAccess = true;
	}
	else
	{
		$bitrixUser->setCurrentUser(
			CRest::call(
				'user.current',
				[]
			)
		);
		//Группы из Б24, которые имеют доступ к заказам
		$bitrixUser->setAllowedGroups(
			array(
				1,
				//Директор
				5,
				//Отдел продаж
				7
				//IT-отдел
			)
		);

		if ($bitrixUser->isAccessAllowed())
		{
			//Группы в БУС, в которых должен состоять пользователь из Б24
			$bitrixUser->setUserDefaultGroups(
				array(
					5,
					6,
					7,
				)
			);
			$bitrixUser->Authorize();
			$hasAccess = true;
			$bitrixRights = 'W';
		}
	}

	if ($hasAccess && Bitrix\Main\Loader::includeModule('sale'))
	{
		$orderId = 0;
		$dateFormat = 'j F Y, H:i';
		if (isset($_GET['DEAL']) && intval($_GET['DEAL']) > 0)
		{
			$arDeal = CRest::call(
				'crm.deal.get',
				[
					'id' => intval($_GET['DEAL']),
				]
			);
			if (isset($arDeal['result']))
			{
				$deal = $arDeal['result'];
			}
			if ($arDeal['result']['ORIGIN_ID'] > 0
				&& $arDeal['result']['ORIGINATOR_ID'] == 's1'
			)
			{
				$orderId = $arDeal['result']['ORIGIN_ID'];
			}
		}
		if (isset($_GET['ORDER']) && intval($_GET['ORDER']) > 0)
		{
			$orderId = intval($_GET['ORDER']);
		}
		if (isset($_GET['CONTACTS']) && intval($_GET['CONTACTS']) > 0)
		{
			$arContacts = CRest::call(
				'crm.deal.contact.items.get',
				[
					'id' => intval($_GET['CONTACTS']),
				]
			);
			if (isset($arContacts['result']) && is_array($arContacts['result']))
			{
				foreach ($arContacts['result'] as $contact)
				{
					$contacts[$contact['CONTACT_ID']] = array();
				}
				if (count($contacts) > 0)
				{
					$arContactList = CRest::call(
						'crm.contact.list',
						[
							'filter' => array("ID" => array_keys($contacts)),
							'select' => array(
								"NAME",
								"SECOND_NAME",
								"LAST_NAME",
								"PHONE",
								"EMAIL",
							),
						]
					);
					if (isset($arContactList['result']) && is_array($arContactList['result']))
					{
						foreach ($arContactList['result'] as $contactlistitem)
						{
							$values = array(
								'id' => $contactlistitem['ID'],
								'name' => (isset($contactlistitem['NAME']) && $contactlistitem['NAME'] != '')
									? $contactlistitem['NAME']
									: '',
								'second_name' => (isset($contactlistitem['SECOND_NAME']) && $contactlistitem['SECOND_NAME'] != '')
									? $contactlistitem['SECOND_NAME']
									: '',
								'last_name' => (isset($contactlistitem['LAST_NAME']) && $contactlistitem['LAST_NAME'] != '')
									? $contactlistitem['LAST_NAME']
									: '',
							);
							if (is_array($contactlistitem["PHONE"]))
							{
								$values['phone'] = array_column($contactlistitem["PHONE"], 'VALUE');
							}
							if (is_array($contactlistitem["EMAIL"]))
							{
								$values['email'] = array_column($contactlistitem["EMAIL"], 'VALUE');
							}
							$contacts[$contactlistitem['ID']] = $values;
						}
					}
					$contacts = (object)array_values($contacts);
				}
			}
		}
		if ($orderId > 0 && $arOrder = Order::load($orderId))
		{
			//$arOrder->getDateInsert();
			$checks = $arOrder->getPrintedChecks();

			$paymentChecks = array();
			foreach ($checks as $check)
			{
				if ($check->getField('CASHBOX_ID') > 0)
				{
					$cashbox = \Bitrix\Sale\Cashbox\Manager::getObjectById($check->getField('CASHBOX_ID'));
					if ($cashbox)
					{
						if (is_array($check->getField('LINK_PARAMS')))
						{
							$link = $cashbox->getCheckLink($check->getField('LINK_PARAMS'));
							$paymentChecks[$check->getField('PAYMENT_ID')][] = array(
								'id' => $check->getField('ID'),
								'link' => ($link)
									? $link
									: false,
							);
						}
						else
						{
							$paymentChecks[$check->getField('PAYMENT_ID')][] = array(
								'id' => $check->getField('ID'),
								'link' => false,
							);
						}
					}
				}
				//print_r($check->getCheckLink());
				//$checks[$check->getField('PAYMENT_ID')][] = array('id' => $check->getId(), 'name' =>);
			}
			$paymentCollection = $arOrder->getPaymentCollection();
			foreach ($paymentCollection as $payment)
			{
				$type = '';
				if (in_array($payment->getPaymentSystemId(), array(10)))
					$type = 'sberbank';
				if (in_array($payment->getPaymentSystemId(), array(11)))
					$type = 'cashless';
				if (in_array($payment->getPaymentSystemId(), array(1)))
					$type = 'cash';
				if (in_array($payment->getPaymentSystemId(), array(12)))
					$type = 'acquiring';
				$checkSended = new \Inteolocal\Zabava\Eventlog($payment->getId());
				$payments[$payment->getId()] = array(
					"id" => $payment->getId(),
					"name" => $payment->getPaymentSystemName(),
					"paymentid" => $payment->getPaymentSystemId(),
					"sum" => $payment->getSum(),
					"paid" => $payment->isPaid(),
					"paiddate" => ($payment->isPaid())
						? strtolower(
							FormatDate(
								$dateFormat,
								$payment->getField('DATE_PAID')
									->getTimestamp()
							)
						)
						: false,
					"return" => $payment->isReturn(),
					"inner" => $payment->isInner(),
					"type" => $type,
					"sended" => $checkSended->getPaymentSendDate($dateFormat),
					"additional" => $checkSended->getAdditional(),
					"check" => (isset($paymentChecks[$payment->getId()]))
						? $paymentChecks[$payment->getId()]
						: array(),
				);
			}
			$propertyCollection = $arOrder->getPropertyCollection();
			if ($orderUser = $propertyCollection->getPayerName())
			{
				$order['person'] = $orderUser->getValue();
			}
			if ($orderPhone = $propertyCollection->getPhone())
			{
				$order['phone'] = $orderPhone->getValue();
			}
			if ($orderMail = $propertyCollection->getUserEmail())
			{
				$order['email'] = $orderMail->getValue();
			}
			$sms = array();
			foreach ($payments as $key => $payment)
			{
				if (isset($payment['additional']['smscId']))
				{
					$sms[$key] = $payment['additional']['smscId'];
				}
			}
			//Статусы SMS
			if (count($sms) > 0)
			{
				$gate = new \Inteolocal\Zabava\Sms();
				$resultStatus = $gate->getStatus(array_values($sms));
				if ($resultStatus->isSuccess())
				{
					$smsResult = $resultStatus->getData();
					$smsStatus = array();
					foreach ($smsResult['MESSAGES'] as $smsResultItem)
					{
						$smsStatus[$smsResultItem['smscId']] = $smsResultItem['status'];
					}
					foreach ($sms as $key => $smsItem)
					{
						$payments[$key]["additional"]["status"] = $smsStatus[$smsItem];
					}
				}
			}
		}
	}
}
$return = array(
	'access' => $bitrixRights,
);
if (count($payments) > 0)
{
	$return['payments'] = $payments;
}
if (count($deal) > 0)
{
	$return['deal'] = $deal;
}
if (count($contacts) > 0)
{
	$return['contacts'] = $contacts;
}
if (count($order) > 0)
{
	$return['order'] = $order;
}
echo Json::encode($return);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>