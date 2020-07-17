<?
namespace Inteolocal\Zabava;

use Bitrix\Main\Localization;
use Bitrix\Main\UI\Uploader\Log;

Localization\Loc::loadMessages(__FILE__);

class Event
{

	public static function OnAfterIBlockElementAdd($arFields)
	{

	}

	public static function OnSalePaymentEntitySaved(\Bitrix\Main\Event $event)
	{
		$payment = $event->getParameter("ENTITY");
		$oldValues = $event->getParameter("VALUES");
		$changedValues = $payment->getFields()->getChangedValues();
		if (array_key_exists("PAID", $changedValues))
		{

			$checkFields = array(
				'PAID' => $payment->getField("PAID")
			);
			$history = new History($payment->getId(), $payment::getClassName());
			if ($history->isChanged($checkFields))
			{
				$history->save();
				//Logger::addLogMessage('START:OnSalePaymentEntitySaved');
				$notify = new Notify();
				$notify->setToAccountant('kruglova@parkzabava.ru');
				$notify->setToDirector('molvinskih@parkzabava.ru');
				$notify->dealAboutPayment($payment);
			}

		}
	}

	public static function OnSaleOrderSaved(\Bitrix\Main\Event $event)
	{
		$order = $event->getParameter("ENTITY");
		$oldValues = $event->getParameter("VALUES");
		$isNew = $event->getParameter("IS_NEW");

		if ($isNew)
		{
			Order::saveDealFromOrder($order);
		}
	}

	public static function OnSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
	{
		/*
		$order = $event->getParameter("ENTITY");
		$oldValues = $event->getParameter("VALUES");
		$orderPrice = $order->getPrice();
		$orderPaid = $order->getSumPaid();
		if ($orderPaid > 0 && $orderPrice > 0 && $order->getField('STATUS_ID')!='F')
		{
			if ($orderPrice == $orderPaid)
			{
				$order->setField('STATUS_ID', 'P');
			}
			else
			{
				$order->setField('STATUS_ID', 'PP');
			}
}*/
	}


	public static function OnAfterIBlockElementUpdate($arFields)
	{
		/*
		if ($arFields["IBLOCK_ID"] && \Bitrix\Main\Loader::IncludeModule("iblock"))
		{
			$updateValues = array();

			$arSelect = Array("ID", "IBLOCK_ID", "NAME", "DETAIL_PICTURE", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "PROPERTY_PRICE", "PROPERTY_OLD_PRICE", "PROPERTY_MORE_PHOTO");
			$res = \CIBlockElement::GetList(Array(), Array("IBLOCK_ID" => $arFields['IBLOCK_ID'], "ID" => $arFields["ID"]), false, false, $arSelect);
			while ($product = $res->GetNext())
			{
				$fields = array(
					'DETAIL_PAGE_URL' => $product["DETAIL_PAGE_URL"],
					'NAME' => $product["NAME"],
				);
				$entity = \Inteolocal\Zabava\Internals\DemoTable::getEntity();

				$connection = Main\Application::getConnection();
				$tableName = $entity->getDBTableName();
				foreach ($fields as $key => $value)
				{
					$updateValues[] = $key."='".$value."'";
				}
				$query = "UPDATE ".$tableName." SET ".implode(', ',$updateValues)." WHERE ITEM_ID=".$arFields["ID"];
				$connection->queryExecute($query);
			}

		}
		*/
	}
}
?>