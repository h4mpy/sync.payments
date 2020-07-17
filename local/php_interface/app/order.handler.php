<?

use Bitrix\Main;
use Bitrix\Main\Application;

/*
use Bitrix\Sale\Compatible\OrderCompatibility;

require_once('crest.php');

Main\EventManager::getInstance()->addEventHandler(
	'sale',
	'OnSaleOrderSaved',
	'OnSaleOrderSavedCustomHandler'
)
;
Main\EventManager::getInstance()->addEventHandler(
	'sale',
	'OnSalePayOrder',
	'OnSalePayOrderCustomHandler'
)
;
function OnSalePayOrderCustomHandler($id, $pay)
{
	if ($pay == 'Y')
	{
		$res = \Bitrix\Sale\Order::getList(
			[
				'filter' => [
					'ID' => $id
				],
				'select' => [
					'ID',
					'XML_ID',
					'PAYED'
				]
			]
		);
		if ($order = $res->Fetch())
		{
			if ($order['PAYED'] == 'Y')
			{
				list($type, $dealID) = explode('_', $order['XML_ID']);
				if ($type == 'deal')
				{
					CRest::call(
						'crm.automation.trigger.execute',
						[
							'CODE' => 'ORDER_PAY',
							'OWNER_TYPE_ID' => 2,
							'OWNER_ID' => intVal($dealID)
						]
					);

				}
			}
		}
	}

}

function OnSaleOrderSavedCustomHandler(Main\Event $event)
{


	$order = $event->getParameter("ENTITY");
	$isNew = $event->getParameter("IS_NEW");
	if ($isNew)
	{
		$id = $order->getId();
		$orderFields = null;

		$resultOrderFields = OrderCompatibility::getOrderFields($order);
		if ($resultOrderFields->isSuccess())
		{
			if ($orderFieldsResultData = $resultOrderFields->getData())
			{

				if (!empty($orderFieldsResultData['ORDER_FIELDS']) && is_array($orderFieldsResultData['ORDER_FIELDS']))
				{
					$orderFields = $orderFieldsResultData['ORDER_FIELDS'];
					$resultContact = CRest::call(
						'crm.contact.add',
						[
							'fields' => [
								'NAME' => $orderFields['PAYER_NAME'],
								'EMAIL' => [
									[
										'VALUE' => $orderFields['USER_EMAIL']
									]
								]
							]
						]
					);
					if (!empty($resultContact['result']))
					{
						$resultDeal = CRest::call(
							'crm.deal.add',
							[
								'fields' => [
									'TITLE' => 'New order №' . $id,
									'ORIGIN_ID' => $id,
									'CONTACT_IDS' => [
										$resultContact['result']
									],

								]
							]
						);
						if ($resultDeal['result'])
						{
							//set deal as xml_id order
							$order->setFieldNoDemand('XML_ID', 'deal_' . $resultDeal['result']);
							$order->save();
							$context = Application::getInstance()->getContext();
							$server = $context->getServer();
							$isHttps = $context->getRequest()->isHttps();
							$orderUrl = ($isHttps ? 'https://' : 'http://') . $server->getHttpHost();
							$orderUrl .= '/bitrix/admin/sale_order_view.php?ID=' . $id;
							CRest::call(
								'crm.activity.add',
								[
									'fields' => [
										"OWNER_TYPE_ID" => 2,
										"OWNER_ID" => $resultDeal['result'],
										"PROVIDER_ID" => 'REST_APP',
										"PROVIDER_TYPE_ID" => 'LINK',
										"SUBJECT" => "new order detail",
										"COMPLETED" => "N",
										"RESPONSIBLE_ID" => 1,
										"PROVIDER_PARAMS" => [
											'URL' => $orderUrl
										]
									]
								]
							);
						}
					}
				}

			}
		}
	}
}
*/