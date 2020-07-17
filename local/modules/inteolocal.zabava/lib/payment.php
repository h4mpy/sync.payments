<?php


namespace Inteolocal\Zabava;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Mail\Event;

class Payment
{
	private $id;
	private $phone = '';
	private $email = '';
	private $appeal = '';
	private $person = '';

	public function __construct($id = 0)
	{
		if (intval($id) <= 0)
			throw new ArgumentNullException("id");

		$this->id = $id;
	}

	/**
	 * @param  mixed  $phone
	 */
	public function setPhone($phone)
	{
		$this->phone = $phone;
	}

	/**
	 * @param  mixed  $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @param  mixed  $appeal
	 */
	public function setAppeal($appeal)
	{
		$this->appeal = $appeal;
	}

	/**
	 * @param  mixed  $appeal
	 */
	public function setPerson($person)
	{
		$this->person = $person;
	}

	public function sendMessage()
	{
		$result = new Result();
		$paymentId = $this->id;
		if (\Bitrix\Main\Loader::includeModule("sale"))
		{
			if ($paymentData = \Bitrix\Sale\Payment::getList(
				array(
					'filter' => array('ID' => intval($paymentId)),
				)
			)
				->fetch()
			)
			{
				$order = \Bitrix\Sale\Order::load($paymentData['ORDER_ID']);
				$arMeasureList = array();
				if (\Bitrix\Main\Loader::includeModule("catalog"))
				{
					$resMeasureList = \CCatalogMeasure::getList();
					while ($arMeasureListItem = $resMeasureList->GetNext())
					{
						$arMeasureList[$arMeasureListItem['CODE']] = $arMeasureListItem;
					}
				}
				$userPhone = $this->phone;
				$userEmail = $this->email;
				$orderID = $order->getId();
				$orderLID = $order->getSiteId();
				$fUserId = null;
				$userName = $this->appeal;
				$eventDetails = array();
				$save = false;

				$propertyCollection = $order->getPropertyCollection();

				if ($this->phone != '')
				{
					if ($orderPhone = $propertyCollection->getPhone())
					{
						if ($this->phone != $orderPhone->getValue())
						{
							$orderPhone->setValue($this->phone);
						}

					}
					if ($orderMail = $propertyCollection->getUserEmail())
					{
						$orderMail->setValue('');
					}
					$eventDetails['phone'] = $this->phone;
					$save = true;
				}
				else
				{
					if ($this->email != '')
					{
						if ($orderMail = $propertyCollection->getUserEmail())
						{
							if ($this->email != $orderMail->getValue())
							{
								$orderMail->setValue($this->email);
								$save = true;
							}
						}
						$eventDetails['email'] = $this->email;
					}
				}
				if ($this->person != '')
				{
					if ($orderPerson = $propertyCollection->getPayerName())
					{
						if ($this->person != $orderPerson->getValue())
						{
							$orderPerson->setValue($this->person);
							$save = true;
						}
					}
					$eventDetails['person'] = $this->person;
				}

				if ($order->getUserId() > 0 && $userName == '')
				{
					/*$fUserId = Fuser::getIdByUserId($order->getUserId());*/
					$arUserName = array();
					$rsUser = \CUser::GetByID($order->getUserId());
					$arUser = $rsUser->Fetch();
					if ($arUser['NAME'] != '')
						$arUserName[] = $arUser['NAME'];
					if ($arUser['SECOND_NAME'] != '')
						$arUserName[] = $arUser['SECOND_NAME'];
					if (count($arUserName) > 0)
					{
						$userName = implode(' ', $arUserName);
					}
				}
				$eventDetails['appeal'] = $userName;
				/*				$connection = Bitrix\Main\Application::getConnection();
				$connection->queryExecute("UPDATE b_sale_order SET EXTERNAL_ORDER = 'N' WHERE b_sale_order.ID = ".$orderID);*/

				if ($userEmail == '')
				{
					if ($orderMail = $propertyCollection->getUserEmail())
					{
						$userEmail = $orderMail->getValue();
						$eventDetails['email'] = $userEmail;
					}
				}

				$arProperties = $propertyCollection->getArray();
				$adminArriveDate = '';
				if (is_array($arProperties['properties']))
				{
					foreach ($arProperties['properties'] as $arProperty)
					{
						if ($arProperty['CODE'] == 'ARRIVE')
						{
							$adminArriveDateProperty = $propertyCollection->getItemByOrderPropertyId($arProperty['ID']);
							$adminArriveDate = $adminArriveDateProperty->getValue();
						}
					}
				}

				$paymentCollection = $order->getPaymentCollection();
				foreach ($paymentCollection as $payment)
				{
					//$checkSended = new \Inteolocal\Zabava\Eventlog($payment->getId());
					//if (!$checkSended->getPaymentSendDate())
					if ($payment->getId() == $paymentId)
					{
						$psSum = $payment->getSum();
						$isPaid = $payment->isPaid();
						$psID = $payment->getPaymentSystemId();
						if ($psID == 10 && $isPaid === false)
						{
							if ($userPhone != '')
							{
								$phoneString = preg_replace("/[^0-9]/", '', $userPhone);
								//AddMessage2Log($phoneString);
								if (preg_match("/^[7-8][0-9]{10}$/", $phoneString) === 1)
								{
									$phoneString = '7'.substr($phoneString, 1);
									$sms = new Sms();
									$sms->setPhone($phoneString);

									$uri = 'https://www.parkzabava.ru/personal/order/payment/?ORDER_ID='.$orderID.'&PAYMENT_ID='
										.$payment->getId().'&HASH='.$order->getHash().'&C='.hash('md5', 'phone:'.$phoneString).'&ACCEPT=Y';
									$shortUri = '';
									$rsData = \CBXShortUri::GetList(Array(), Array('URI' => $uri));
									while ($arRes = $rsData->Fetch())
									{
										$shortUri = $arRes["SHORT_URI"];
									}
									if ($shortUri === '')
									{
										$shortUri = \CBXShortUri::GenerateShortUri();
										$arFields = Array(
											"URI" => $uri,
											"SHORT_URI" => $shortUri,
											"STATUS" => "301",
										);
										\CBXShortUri::Add($arFields);
									}
									$shortUri = 'https://www.parkzabava.ru/'.$shortUri;
									$sms->setText(
										Loc::getMessage(
											"INTEOLOCAL_SMS_TEXT",
											array(
												"#SUM#" => $payment->getField("SUM"),
												"#LINK#" => $shortUri,
											)
										)
									);
									$sms->setMessageId($payment->getId());
									$result = $sms->send();
									if ($result->isSuccess())
									{
										$smsDetails = $result->getData();
										if (isset($smsDetails['smscId']))
										{
											$eventDetails['smscId'] = $smsDetails['smscId'];
										}
										$checkSended = new Eventlog($payment->getId());
										$checkSended->setAdditional($eventDetails);
										$checkSended->save();
										if ($order->getField('STATUS_ID') == 'N')
										{
											$order->setField('STATUS_ID', 'PS');
											$save = true;
										}
										\CRest::call(
											'crm.timeline.comment.add',
											[
												'fields' => [
													"ENTITY_ID" => Deal::getByOrder($order),
													"ENTITY_TYPE" => "deal",
													"COMMENT" => Loc::getMessage(
														'INTEOLOCAL_SEND_LINK_PHONE',
														array(
															"#SUM#" => $payment->getField("SUM"),
															"#CONTACT#" => '+'.substr($phoneString, 0, 1).' ('.substr($phoneString, 1, 3)
																.') '.substr($phoneString, 4, 3).'-'.substr($phoneString, 7, 2).'-'.substr(
																	$phoneString,
																	9,
																	2
																),
														)
													),
												],
											]
										);
									}
									else
									{
										$save = false;
									}
								}
								else
								{
									$result->addError(
										new Error(Loc::getMessage("INTEOLOCAL_PAYMENT_WRONG_PHONE"), 'INTEOLOCAL_ZABAVA_SEND_MESSAGE_PHONE')
									);

									return $result;
								}
							}
							else
							{
								$messageHtml
									= '<h2 style="Margin:0;padding-bottom:20px;line-height:36px;mso-line-height-rule:exactly;font-family:\'open sans\', \'helvetica neue\', helvetica, arial, sans-serif;font-size:30px;font-style:normal;font-weight:normal;color:#333333;"><strong>Заказ в Парке Забава</strong></h2>';
								$messageHtml .= '<p>Добрый день';
								if ($userName != '')
								{
									$messageHtml .= ', '.$userName;
								}
								$messageHtml .= '<br>Вы оформили заказ на организацию отдыха в&nbsp;Парке активного и&nbsp;семейного отдыха «Забава».</p>';
								$messageHtml .= '<p>';
								if ($adminArriveDate != '')
								{
									$messageHtml .= 'Дата поездки: <b style="text-transform:lowercase">'.FormatDate(
											"j F Y",
											MakeTimeStamp($adminArriveDate, "DD.MM.YYYY")
										).'</b><br>';
								}
								$messageHtml .= 'Сумма заказа: <b>'.$order->getPrice().' руб.</b></p>';
								$basket = \Bitrix\Sale\Basket::loadItemsForOrder($order);
								$arBasketItems = array();
								foreach ($basket as $basketItem)
								{
									$strBasketItems = $basketItem->getField('NAME').' – '.$basketItem->getPrice().' руб. &times; '
										.$basketItem->getQuantity();
									if (intval($basketItem->getField('MEASURE_CODE')) > 0
										&& isset(
											$arMeasureList[$basketItem->getField(
												'MEASURE_CODE'
											)]['~SYMBOL_RUS']
										)
									)
									{
										$strBasketItems .= ' '.$arMeasureList[$basketItem->getField('MEASURE_CODE')]['~SYMBOL_RUS'];
									}
									$arBasketItems[] = $strBasketItems;
								}
								if (count($arBasketItems) > 0)
								{
									$messageHtml .= '<p><b>Состав заказа</b></p><p>'.implode('<br>', $arBasketItems).'</p>';
								}
								$messageHtml .= '<p>Для подтверждения заказа необходимо внести оплату в&nbsp;размере <b>'.$psSum
									.'&nbsp;руб.</b></p>';
								//$messageHtml.= '<p>Оплачивая заказ, Вы соглашаетесь с <a href="#" target="_blank">условиями оказания услуг ООО «Парк Забава»</a>, а также <a href="http://www.parkzabava.ru/winter/pravila/" target="_blank">правилами посещения Парка Забава</a> и&nbsp;подтверждаете их&nbsp;соблюдение при нахождении в&nbsp;Парке Забава.</p>';
								$messageHtml.= '<p>Оплачивая заказ, Вы соглашаетесь:</p><ul style="padding-left:10px">';
								$messageHtml.= '<li>с <a href="https://www.parkzabava.ru/rules/" target="_blank">правилами посещения Парка Забава</a> и&nbsp;подтверждаете их&nbsp;соблюдение при нахождении в&nbsp;Парке Забава.</li>';
								$messageHtml.= '<li>с условиями <a href="https://www.parkzabava.ru/oferta/" target="_blank">оферты</a>;</li>';
								$messageHtml.= '<li><p style="margin-top:0">с существенными условиями заказа:</p><ol style="padding-left:0px"><li style="margin-bottom:10px">Заказчик может находиться на территории Парка только в часы его работы (с 10:00 до 20:00), а также пользоваться услугами аттракционов в часы их работы (с 10:00 до 18:00), если иное не обговорено дополнительно.</li><li style="margin-bottom:10px">Распитие алкогольных напитков строго в арендуемых местах отдыха. Курение в специально отведенных местах. Запрещен пронос, хранение и использование кальянов на территории Парка.</li><li>В случае отмены Заказа, Заказчик обязан уведомить Исполнителя не менее, чем за день до даты поездки письмом на эл. почту zakaz@parkzabava.ru. В этом случае ООО «Парк Забава» удерживает 50% от полной суммы Заказа. Если Заказчик не уведомил Исполнителя об отмене поездки, Заказ считается исполненным и ООО «Парк Забава» удерживает 100% суммы Заказа;</li></ol></li></ul>';

								$messageHtml .= '<p style="padding-top:25px;margin-bottom:50px"><a class="btn" style="background:#f34841;color:#fff!important;text-decoration:none;font-size:16px;display:inline-block;font-weight:bold;text-align:center;white-space:nowrap;vertical-align:middle;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;padding:15px 30px;border-radius:30px;" href="https://www.parkzabava.ru/personal/order/payment/?ORDER_ID='
									.$orderID.'&PAYMENT_ID='.$payment->getId().'&HASH='.$order->getHash().'&C='.hash(
										'md5',
										'email:'.$userEmail
									).'" target="_blank">Перейти к оплате</a></p>';
								$messageHtml .= '<p>По приезде в Парк необходимо обратиться в сувенирную лавку, там находится служба приема, размещения и&nbsp;консультирования клиентов.</p>';
								$messageHtml .= '<p>Если у вас возникли вопросы – звоните или пишите нам, мы всегда с радостью Вас проконсультируем и поможем организовать активный отдых в Парке «Забава».</p>';
								//$messageHtml.= '<p style="margin-bottom:0">Дополнительно Вы можете ознакомиться с <a href="https://www.parkzabava.ru/" target="_blank">ценами на услуги Парка</a>.</p>';

								if (check_email($userEmail))
								{
									Event::sendImmediate(
										array(
											"EVENT_NAME" => "SEND_PAYMENT_LINK",
											"LID" => $orderLID,
											"C_FIELDS" => array(
												"EMAIL_TO" => $userEmail,
												"MESSAGE" => $messageHtml,
											),
										)
									);
									$checkSended = new Eventlog($payment->getId());
									$checkSended->setAdditional($eventDetails);
									$checkSended->save();

									$propertyCollection = $order->getPropertyCollection();
									$arProperties = $propertyCollection->getArray();
									if (is_array($arProperties['properties']))
									{
										foreach ($arProperties['properties'] as $arProperty)
										{
											if ($arProperty['CODE'] == 'MAIL_SENT')
											{
												$sentProperty = $propertyCollection->getItemByOrderPropertyId($arProperty['ID']);
												$sentProperty->setValue("Y");
												$sentProperty->save();
											}
										}
									}
									if ($order->getField('STATUS_ID') == 'N')
									{
										$order->setField('STATUS_ID', 'PS');
									}
									\CRest::call(
										'crm.timeline.comment.add',
										[
											'fields' => [
												"ENTITY_ID" => Deal::getByOrder($order),
												"ENTITY_TYPE" => "deal",
												"COMMENT" => Loc::getMessage(
													'INTEOLOCAL_SEND_LINK_EMAIL',
													array(
														"#SUM#" => $payment->getField("SUM"),
														"#CONTACT#" => $userEmail,
													)
												),
											],
										]
									);
									$result->setData(array('result' => $checkSended->getPaymentSendDate('j F Y, H:i')));
									$save = true;
								}
								else
								{
									$result->addError(
										new Error(Loc::getMessage("INTEOLOCAL_PAYMENT_WRONG_EMAIL"), 'INTEOLOCAL_ZABAVA_SEND_MESSAGE_EMAIL')
									);

									return $result;
								}
							}
						}
					}
				}
				if ($save)
				{
					$order->save();
				}
			}
		}

		return $result;
	}
}