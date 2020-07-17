<?php


namespace Inteolocal\Zabava;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

class Sms
{
	private $gate;
	private $phone = '';
	private $login = 't89201271470';
	private $password = '799994';
	private $sender = 'parkzabava';
	private $messageId = 1;
	private $text = '';

	public function __construct($gate = 'prostor')
	{
		$this->gate = $gate;
	}

	/**
	 * @param  string  $phone
	 */
	public function setPhone($phone)
	{
		$this->phone = trim($phone);
	}

	/**
	 * @param  int  $messageId
	 */
	public function setMessageId($messageId)
	{
		$this->messageId = $messageId;
	}

	/**
	 * @param  string  $text
	 */
	public function setText($text)
	{
		$this->text = $text;
	}

	public function getStatus($sms)
	{
		$result = new Result();
		if (empty($sms))
		{
			$result->addError(
				new Error(Loc::getMessage("INTEOLOCAL_WRONG_SMS_STATUS"), 'INTEOLOCAL_ZABAVA_SMS_STATUS')
			);
			return $result;
		}
		$params = array(
			'login' => $this->login,
			'password' => $this->password
		);
		$messages = array();
		if (is_array($sms))
		{
			foreach ($sms as $singleSms)
			{
				$messages[] = array('smscId' => $singleSms);
			}
		}
		else
		{
			$messages[] = array('smscId' => $sms);
		}
		$params['messages'] = $messages;
		$client = curl_init('http://api.prostor-sms.ru/messages/v2/status.json');
		curl_setopt_array($client, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HEADER => false,
			CURLOPT_POSTFIELDS => Json::encode($params),
			CURLOPT_HTTPHEADER => array('Content-Type:application/json')
		));
		$body = curl_exec($client);
		curl_close($client);
		try
		{
			$body = Json::decode($body);
			$result->setData(array('MESSAGES' => $body['messages']));
		}
		catch (ArgumentException $e)
		{
			$result->addError(
				new Error($e->getMessage(), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_CONNECTION')
			);
		}
		return $result;
	}

	public function checkBalance()
	{
		$result = new Result();
		$params = array(
			'login' => $this->login,
			'password' => $this->password
		);
		$client = curl_init('http://api.prostor-sms.ru/messages/v2/balance.json');
		curl_setopt_array($client, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HEADER => false,
			CURLOPT_POSTFIELDS => Json::encode($params),
			CURLOPT_HTTPHEADER => array('Content-Type:application/json')
		));
		$body = curl_exec($client);
		curl_close($client);
		try
		{
			$body = Json::decode($body);
			if (isset($body['status']) && $body['status'] == 'ok')
			{
				$balance = reset($body['balance']);
				if ($balance['balance'] > 0)
				{
					//$result->setData(array('BALANCE' => $balance['balance']));
				}
				else
				{
					$result->addError(
						new Error(Loc::getMessage("INTEOLOCAL_PROSTOR_MONEY"), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_MONEY')
					);
				}
			}
			else
			{
				$result->addError(
					new Error(Loc::getMessage("INTEOLOCAL_PROSTOR_CONNECTION"), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_CONNECTION')
				);
			}
		}
		catch (ArgumentException $e)
		{
			$result->addError(
				new Error($e->getMessage(), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_CONNECTION')
			);
		}
		return $result;
	}

	/**
	 * @return Result
	 */
	public function send()
	{
		$result = new Result();
		if ($this->text == '')
		{
			$result->addError(
				new Error(Loc::getMessage("INTEOLOCAL_EMPTY_SMS"), 'INTEOLOCAL_ZABAVA_SMS_EMPTY')
			);
			return $result;
		}
		if ($this->phone == '')
		{
			$result->addError(
				new Error(Loc::getMessage("INTEOLOCAL_EMPTY_PHONE"), 'INTEOLOCAL_ZABAVA_PHONE_EMPTY')
			);
			return $result;
		}
		if ($this->gate == 'prostor')
		{
			//$sender = new \Inteolocal\Zabava\Prostor($this->login, $this->password);
			$result = $this->checkBalance();
			if ($result->isSuccess())
			{
				$params = array(
					'login' => $this->login,
					'password' => $this->password,
					'sender' => $this->sender,
				);
				$params['messages'] = array();
				$params['messages'][] = array(
					"phone" => $this->phone,
					"sender" => $this->sender,
					"clientId" => $this->messageId,
					"text" => $this->text
				);
				//AddMessage2Log($params);

				$client = curl_init('http://api.prostor-sms.ru/messages/v2/send.json');
				curl_setopt_array($client, array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => true,
					CURLOPT_HEADER => false,
					CURLOPT_POSTFIELDS => Json::encode($params),
					CURLOPT_HTTPHEADER => array('Content-Type:application/json')
				));
				$body = curl_exec($client);
				curl_close($client);
				try
				{
					$body = Json::decode($body);
					if (isset($body['status']) && $body['status'] == 'ok')
					{
						$messages = reset($body['messages']);
						if ($messages['smscId'] != '')
						{
							$result->setData(array('smscId' => $messages['smscId']));
						}
						else
						{
							$result->addError(
								new Error(Loc::getMessage("INTEOLOCAL_PROSTOR_REJECTED"), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_REJECTED')
							);
						}
					}
					else
					{
						$result->addError(
							new Error(Loc::getMessage("INTEOLOCAL_PROSTOR_CONNECTION"), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_CONNECTION')
						);
					}
				}
				catch (ArgumentException $e)
				{
					$result->addError(
						new Error($e->getMessage(), 'INTEOLOCAL_ZABAVA_SMS_PROSTOR_CONNECTION')
					);
				}
			}
		}
		return $result;
	}
}