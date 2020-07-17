<?php


namespace Inteolocal\Zabava;


class Logger
{

	public static function isDebugEnabled()
	{
		return \Bitrix\Main\Config\Option::get('inteolocal.zabava', 'debug', 'N') === 'Y';
	}

	public static function addLogMessage($message)
	{
		if (Logger::isDebugEnabled())
		{
			AddMessage2Log($message, "INTEOLOCAL_ZABAVA", 1);
		}
	}
}