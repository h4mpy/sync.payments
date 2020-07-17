<?php


namespace Inteolocal\Zabava;


use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SiteTable;
use Bitrix\Sale\Payment;

class Notify
{

	/**
	 * @var string Отправлять уведомление директору
	 */
	protected $toDirector = '';

	/**
	 * @var string Отправлять уведомление бухгалтеру
	 */
	protected $toAccountant = '';

	/**
	 * @var string Тип почтового события директору
	 */
	protected $toDirectorMessageType = 'INTEO_NOTIFY_DIRECTOR';
	/**
	 * @var string Тип почтового события бухгалтеру
	 */
	protected $toAccountantMessageType = 'INTEO_NOTIFY_ACCOUNTANT';
	/**
	 * Notify constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @param  string  $toDirector
	 */
	public function setToDirector($toDirector)
	{
		if (check_email($toDirector))
		{
			$this->toDirector = $toDirector;
		}
	}

	/**
	 * @param  string  $toAccountant
	 */
	public function setToAccountant($toAccountant)
	{
		if (check_email($toAccountant))
		{
			$this->toAccountant = $toAccountant;
		}
	}

	/**
	 * Возвращает id существующего, либо созданного шаблона, удовлетворяющего условиям
	 * @param string $type ID типа почтового события
	 * @param string $siteId ID сайта
	 * @param string $siteLid LANGUAGE_ID сайта
	 * @param string $messageTitle Название типа почтовых событий
	 * @return bool|mixed
	 */
	protected static function getMessageId($type = '', $siteId = 's1', $siteLid = 'ru', $messageTitle = '')
	{
		$result = false;
		if (strlen($type) > 0)
		{
			if ($messageTitle == '')
			{
				$messageTitle = Loc::getMessage('INTEOLOCAL_MESSAGE_DEFAULT_TITLE');
			}
			$arEvent = \CEventType::GetByID($type, $siteLid)->Fetch();
			if (!is_array($arEvent))
			{
				$description = Loc::getMessage("INTEOLOCAL_SEND_MESSAGE");
				$eventType = new \CEventType;
				$arEventTypeFields = array(
					"LID" => $siteLid,
					"EVENT_NAME" => $type,
					"NAME" => $messageTitle,
					"DESCRIPTION" => $description,
				);
				$eventType->Add($arEventTypeFields);
			}

			$arMessage = \CEventMessage::GetList($by = "id", $order = "desc", array("TYPE_ID" => $type))->Fetch();
			if (!is_array($arMessage))
			{
				$eventMessage = new \CEventMessage;
				$arMessage = array();
				$arMessage["ID"] = $eventMessage->Add(array(
					"ACTIVE" => "Y",
					"EVENT_NAME" => $type,
					"LID" => array($siteId),
					"LANGUAGE_ID" => $siteLid,
					"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
					"EMAIL_TO" => "#EMAIL_TO#",
					"SUBJECT" => "#FORM_NAME#",
					"BODY_TYPE" => "html",
					"MESSAGE" => "#MESSAGE#"
				));
			}
			$result = $arMessage["ID"];
		}
		return $result;
	}

	/**
	 * Отправляет необходимые типы e-mail
	 * @param string $title Заголовок письма
	 * @param string $message Текст сообщения
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function sendEmail($title, $message)
	{
		$site = SiteTable::getList(array('filter' => array('DEF' => 'Y')))->fetch();
		if ($this->toAccountant!='')
		{
			if ($messageId = self::getMessageId($this->toAccountantMessageType, $site["LID"], $site["LANGUAGE_ID"], Loc::getMessage("INTEOLOCAL_MESSAGE_TITLE_ACCOUNTANT")))
			{
				$arMailFields = array(
					"FORM_NAME" => $title,
					"EMAIL_TO" => $this->toAccountant,
					"MESSAGE" => $message
				);
				\CEvent::SendImmediate($this->toAccountantMessageType, $site["LID"], $arMailFields, "Y", $messageId);
			}
		}
		if ($this->toDirector!='')
		{
			if ($messageId = self::getMessageId($this->toDirectorMessageType, $site["LID"], $site["LANGUAGE_ID"], Loc::getMessage("INTEOLOCAL_MESSAGE_TITLE_DIRECTOR")))
			{
				$arMailFields = array(
					"FORM_NAME" => $title,
					"EMAIL_TO" => $this->toDirector,
					"MESSAGE" => $message
				);
				\CEvent::SendImmediate($this->toDirectorMessageType, $site["LID"], $arMailFields, "Y", $messageId);
			}
		}
	}

	/**
	 * Уведомления об облате в ленте сделки, уведомление ответственному, необходимые уведомления по e-mail
	 * @param  Payment  $payment
	 *
	 * @return Result
	 */
	public function dealAboutPayment(Payment $payment)
	{
		//Loader::includeModule('currency');
		$result = new Result();
		$order = $payment->getCollection()
			->getOrder();
		if ($deal = Deal::getByOrder($order))
		{

			//\Bitrix\Currency\CurrencyLangTable::getList(array('filter' => array('CURRENCY' => 'RUB', 'LID' => 'ru')))->fetch();
			if ($payment->getField("PAID") == "Y")
			{
				$author = 1;
				if ($payment->getId() != 10)
				{
					$arUser = \CRest::call(
						'user.current',
						[]
					);

					if (isset($arUser['result']['ID']) && $arUser['result']['ID'] > 0)
					{
						$author = $arUser['result']['ID'];
					}
					AddMessage2Log($arUser);
				}
				\CRest::call(
					'crm.timeline.comment.add',
					[
						'fields' => [
							"ENTITY_ID" => $deal,
							"ENTITY_TYPE" => "deal",
							"COMMENT" => Loc::getMessage(
								'INTEOLOCAL_COMMENT_PAYMENT',
								array(
									"#SUM#" => $payment->getField("SUM"),
									"#PAYMENTTYPE#" => $payment->getField(
										"PAY_SYSTEM_NAME"
									),
								)
							),
							"AUTHOR_ID" => $author
						],
					]
				);
				$dealFields = \CRest::call(
					'crm.deal.get',
					[
						'id' => $deal,
					]
				);
				if (isset($dealFields['result']['ASSIGNED_BY_ID'])
					&& $dealFields['result']['ASSIGNED_BY_ID'] > 0
				)
				{

					\CRest::call(
						'im.notify',
						[
							'to' => $dealFields['result']['ASSIGNED_BY_ID'],
							'message' => Loc::getMessage(
								'INTEOLOCAL_COMMENT_PAYMENT_MESSAGE',
								array(
									"#SUM#" => $payment->getField("SUM"),
									"#PAYMENTTYPE#" => $payment->getField(
										"PAY_SYSTEM_NAME"
									),
									"#LINK#" => "https://parkzabava.bitrix24.ru/crm/deal/details/{$dealFields['result']['ID']}/",
									"#DEAL#" => $dealFields['result']['TITLE'],
								)
							),
							'type' => 'SYSTEM',
						]
					);
				}

				$this->sendEmail(Loc::getMessage("INTEOLOCAL_COMMENT_PAYMENT_MESSAGE_TITLE"), Loc::getMessage(
					'INTEOLOCAL_COMMENT_PAYMENT_MESSAGE_HTML',
					array(
						"#SUM#" => $payment->getField("SUM"),
						"#PAYMENTTYPE#" => $payment->getField(
							"PAY_SYSTEM_NAME"
						),
						"#LINK#" => "https://parkzabava.bitrix24.ru/crm/deal/details/{$dealFields['result']['ID']}/",
						"#DEAL#" => $dealFields['result']['TITLE'],
					)
				));

				$orderPrice = $order->getPrice();
				$orderPaid = $order->getSumPaid();
				if ($orderPaid > 0 && $orderPrice > 0)
				{
					if ($orderPrice == $orderPaid)
					{
						\CRest::call(
							'crm.automation.trigger.execute',
							[
								'CODE' => 'ORDER_PAY',
								'OWNER_TYPE_ID' => 2,
								"OWNER_ID" => $deal
							]
						);
					}
					else
					{
						\CRest::call(
							'crm.automation.trigger.execute',
							[
								'CODE' => 'ORDER_PREPAY',
								'OWNER_TYPE_ID' => 2,
								"OWNER_ID" => $deal
							]
						);
					}
				}

			}
			else
			{
				/*
				if ($payment->getField("IS_RETURN") == "Y")
				{
					\CRest::call(
						'crm.timeline.comment.add',
						[
							'fields' => [
								"ENTITY_ID" => $deal,
								"ENTITY_TYPE" => "deal",
								"COMMENT" => Loc::getMessage(
									'INTEOLOCAL_COMMENT_PAYMENT_RETURN',
									array(
										"#SUM#" => $payment->getField("SUM"),
										"#PAYMENTTYPE#" => $payment->getField(
											"PAY_SYSTEM_NAME"
										),
									)
								),
							],
						]
					);
				}
				else
				{
					\CRest::call(
						'crm.timeline.comment.add',
						[
							'fields' => [
								"ENTITY_ID" => $deal,
								"ENTITY_TYPE" => "deal",
								"COMMENT" => Loc::getMessage(
									'INTEOLOCAL_COMMENT_PAYMENT_CANCEL',
									array(
										"#SUM#" => $payment->getField("SUM"),
										"#PAYMENTTYPE#" => $payment->getField(
											"PAY_SYSTEM_NAME"
										),
									)
								),
							],
						]
					);
				}*/

			}

		}

		return $result;
	}
}