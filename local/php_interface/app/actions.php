<?

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Cashbox\CheckManager;
use Bitrix\Sale\PaySystem\Manager;
use Inteolocal\Zabava\Order;
use Inteolocal\Zabava\Payment;
use Inteolocal\Zabava\User;

Header('Access-Control-Allow-Origin: *');

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once('crest.php');

$result = array();

if (Bitrix\Main\Loader::includeModule('inteolocal.zabava')
	&& isset($_REQUEST['type'])
)
{
	$bitrixUser = new User();
	$bitrixRights = $bitrixUser->getUserRight('sale');
	if (in_array(
		$bitrixRights,
		array(
			'W',
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
		if ($bitrixUser->isAccessAllowed())
		{
			$bitrixUser->Authorize();
			$hasAccess = true;
		}
	}
	if ($hasAccess)
	{
		//Отправка сообщения
		if ($_REQUEST['type'] == 'sendpaymentmessage' && isset($_REQUEST['id'])
			&& $_REQUEST['id'] > 0
		)
		{
			$getPayment = new Payment(intval($_REQUEST['id']));
			if (isset($_REQUEST['recipient']))
			{
				$recipient = Json::decode($_REQUEST['recipient']);
				if (isset($recipient['method']))
				{
					if ($recipient['method'] == 'phone')
					{
						//Устанавливаем телефон
						if (isset($recipient['contact']))
						{
							$getPayment->setPhone(strval($recipient['contact']));
						}
					}
					if ($recipient['method'] == 'email')
					{
						//Устанавливаем email
						if (isset($recipient['contact']))
						{
							$getPayment->setEmail(strval($recipient['contact']));
						}
					}
				}
				if (isset($recipient['appeal']))
				{
					$getPayment->setAppeal(strval($recipient['appeal']));
				}
				if (isset($recipient['person']))
				{
					$getPayment->setPerson(strval($recipient['person']));
				}
			}
			$sendResult = $getPayment->sendMessage();
			if ($sendResult->isSuccess())
			{
				$result = $sendResult->getData();
			}
			else
			{
				$result['errors'] = $sendResult->getErrorMessages();
			}
		}
		//Создание оплаты
		if ($_REQUEST['type'] == 'addpayment' && isset($_REQUEST['deal'])
			&& intval($_REQUEST['deal']) > 0
		)
		{
			$payments = array(
				'sberbank' => 10,
				'cash' => 1,
				'cashless' => 11,
				'acquiring' => 12
			);
			$addResult = Order::saveOrderFromDeal(intval($_REQUEST['deal']), true, false);
			if ($addResult->isSuccess())
			{

				$arAddResult = $addResult->getData();
				if (isset($arAddResult['ORDER_ID']) && $arAddResult['ORDER_ID'] > 0)
				{
					$order = \Bitrix\Sale\Order::load(intval($arAddResult['ORDER_ID']));
					if (isset($_REQUEST['payment']) && isset($payments[$_REQUEST['payment']]))
					{
						$paymentCollection = $order->getPaymentCollection();
						$payment = $paymentCollection->createItem(
							Manager::getObjectById($payments[$_REQUEST['payment']]) // ID платежной системы
						);

						$payment->setField("SUM", intval($_REQUEST['sum']));
						$payment->setField("CURRENCY", $order->getCurrency());
						if (isset($_REQUEST['paid']) && $_REQUEST['paid'] === 'true' && $_REQUEST['payment'] != 'sberbank')
						{
							$payment->setPaid("Y");
						}
						else
						{
							$payment->setPaid("N");
						}
						$r = $order->save();

						if ($r->isSuccess())
						{
							$result['payment'] = $payment->getId();
						}

						if (isset($_REQUEST['sendpayment']) && $_REQUEST['sendpayment'] === 'true' && $_REQUEST['payment'] == 'sberbank')
						{
							$getPayment = new Payment($payment->getId());

							if (isset($_REQUEST['recipient']))
							{
								$recipient = Json::decode($_REQUEST['recipient']);
								if (isset($recipient['method']))
								{
									if ($recipient['method'] == 'phone')
									{
										//Устанавливаем телефон
										if (isset($recipient['contact']))
										{
											$getPayment->setPhone(strval($recipient['contact']));
										}
									}
									if ($recipient['method'] == 'email')
									{
										//Устанавливаем email
										if (isset($recipient['contact']))
										{
											$getPayment->setEmail(strval($recipient['contact']));
										}
									}
								}
								if (isset($recipient['appeal']))
								{
									$getPayment->setAppeal(strval($recipient['appeal']));
								}
								if (isset($recipient['person']))
								{
									$getPayment->setPerson(strval($recipient['person']));
								}
							}

							$sendResult = $getPayment->sendMessage();
							if ($sendResult->isSuccess())
							{
								$result = $sendResult->getData();
							}
							else
							{
								$result['errors'] = $sendResult->getErrorMessages();
							}

						}
					}
					else
					{
						$result['errors'][] = 'Неизвестная ошибка. Сообщите администратору номер сделки '.$_REQUEST['deal'];
					}
				}
				else
				{
					$result['errors'][] = 'Неизвестная ошибка. Сообщите администратору номер сделки '.$_REQUEST['deal'];
				}
			}
			else
			{
				$result['errors'] = $addResult->getErrorMessages();
			}
		}
		//Удаление оплаты
		if ($_REQUEST['type'] == 'deletepayment' && isset($_REQUEST['id'])
			&& intval($_REQUEST['id']) > 0
		)
		{
			if (isset($_REQUEST['deal']) && intval($_REQUEST['deal']) > 0)
			{

				$addResult = Order::saveOrderFromDeal(intval($_REQUEST['deal']), true, false);
				if ($addResult->isSuccess())
				{
					$arAddResult = $addResult->getData();
					if (isset($arAddResult['ORDER_ID']) && $arAddResult['ORDER_ID'] > 0)
					{
						$order = \Bitrix\Sale\Order::load(intval($arAddResult['ORDER_ID']));
						$collection = $order->getPaymentCollection();
						$payment = $collection->getItemById(intval($_REQUEST['id']));
						if ($payment->getPaymentSystemId() != 10)
						{
							$payment->setPaid("N");
						}
						$r = $payment->delete();
						if ($r->isSuccess())
						{
							$order->save();
							$result = $r->getData();
						}
						else
						{
							$result['errors'] = $r->getErrorMessages();
						}
					}
					else
					{
						$result['errors'][] = 'Неизвестная ошибка. Сообщите администратору что вы делали и номер сделки '.$_REQUEST['deal'];
					}
				}
			}
			else
			{
				$result['errors'][] = 'Неизвестная ошибка';
			}
		}
		//Редактирование оплаты
		if ($_REQUEST['type'] == 'editpayment' && isset($_REQUEST['id'])
			&& intval($_REQUEST['id']) > 0
		)
		{
			if (isset($_REQUEST['deal']) && intval($_REQUEST['deal']) > 0)
			{

				$addResult = Order::saveOrderFromDeal(intval($_REQUEST['deal']), true, false);
				if ($addResult->isSuccess())
				{
					$arAddResult = $addResult->getData();
					if (isset($arAddResult['ORDER_ID']) && $arAddResult['ORDER_ID'] > 0)
					{
						$order = \Bitrix\Sale\Order::load(intval($arAddResult['ORDER_ID']));
						$collection = $order->getPaymentCollection();
						$payment = $collection->getItemById(intval($_REQUEST['id']));
						$payment->setPaid("N");
						if (isset($_REQUEST['sum']) && intval($_REQUEST['sum']) > 0)
						{
							try
							{
								$payment->setField("SUM", intval($_REQUEST['sum']));
							}
							catch (\Bitrix\Main\ArgumentOutOfRangeException $e)
							{
								$result['errors'][] = $e->getMessage();
							}
							catch (\Bitrix\Main\NotImplementedException $e)
							{
								$result['errors'][] = $e->getMessage();
							}
						}
						if ($payment->getPaymentSystemId() != 10 && isset($_REQUEST['paid']))
						{
							if ($_REQUEST['paid'] === 'true')
							{
								$result['paid'] = 'Y';
								$payment->setPaid("Y");
							}
							if ($_REQUEST['paid'] === 'false')
							{
								$result['paid'] = 'N';
								$payment->setPaid("N");
							}
						}
						$r = $order->save();
						if (!$r->isSuccess())
						{
							$result['errors'] = $r->getErrorMessages();
						}
					}
					else
					{
						$result['errors'][] = 'Неизвестная ошибка. Сообщите администратору что вы делали и номер сделки '.$_REQUEST['deal'];
					}
				}
			}
			else
			{
				$result['errors'][] = 'Неизвестная ошибка';
			}
		}
	}
}
echo Bitrix\Main\Web\Json::encode($result);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>