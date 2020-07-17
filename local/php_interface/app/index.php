Здесь будут настройки взаимодействия с сайтом
<?
require_once('crest.php');

/*
$result3 = CRest::call('event.unbind',
	array(
		'event' => 'onCrmDealAdd',
		'handler' => 'https://www.parkzabava.ru/local/php_interface/app/events.php'
	)
);

CRest::call(
	'event.bind',
	[
		'EVENT' => 'onCrmDealUpdate',
		'HANDLER' => 'https://www.parkzabava.ru/local/php_interface/app/events.php'
	]
);
*/
/*
if (!empty($_REQUEST['PLACEMENT_OPTIONS']))
{
	$data = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
	$activityId = intVal($data['activity_id']);
	if ($data['action'] == 'view_activity' && $activityId > 0)
	{
		$result = CRest::call(
			'crm.activity.list',
			[
				'filter' => [
					'ID' => $activityId
				]
			]
		);

		if (!empty($result['result']['0']['PROVIDER_PARAMS']['URL']))
		{
			header("Location: " . $result['result']['0']['PROVIDER_PARAMS']['URL'] . '&IFRAME=Y');
			exit();
		}
	}
	else
	{
		echo 'order page';
	}
}*/
/*
		$result3 = CRest::call('placement.bind',
			array(
				'PLACEMENT' => 'CRM_DEAL_DETAIL_ACTIVITY',
				'HANDLER' => 'https://www.parkzabava.ru/local/php_interface/app/handler.php',
				'TITLE' => 'Заказ на сайте'
			)
		);
*/
/*
		$result3 = CRest::call('event.bind',
			array(
				'event' => 'onCrmDealAdd',
				'handler' => 'https://www.parkzabava.ru/local/php_interface/app/events.php'
			)
		);
print_r($result3);*/
/*
		$result3 = CRest::call('userfieldtype.add',
			array(
				'USER_TYPE_ID' => 'sitepayment',
				'HANDLER' => 'https://www.parkzabava.ru/local/php_interface/app/orderaction.php',
				'TITLE' => 'Оплата через Сбербанк.Эквайринг',
				'DESCRIPTION' => 'Поле позволяет взаимодействовать с сайтом для оплаты/предоплаты сделок'
			)
);*/
/*
$result = CRest::call('userfieldtype.delete',
	array('USER_TYPE_ID' => 'sitepayment')
);*/
/*CRest::call(
	'event.bind',
	[
		'EVENT' => 'onCrmDealAdd',
		'HANDLER' => 'https://www.parkzabava.ru/local/php_interface/app/events.php'
	]
);*/

$result = CRest::call('userfieldtype.list',
	array()
);
if (isset($result['result']))
{
	$needPaymentHandler = true;
	foreach ($result['result'] as $item)
	{
		if (isset($item['USER_TYPE_ID']) && $item['USER_TYPE_ID'] == 'sitepayment')
		{
			$needPaymentHandler = false;
		}
	}
	if ($needPaymentHandler)
	{
		CRest::call('userfieldtype.add',
			array(
				'USER_TYPE_ID' => 'sitepayment',
				//'HANDLER' => 'https://www.parkzabava.ru/local/payment-sync/compiled/',
				'HANDLER' => 'https://www.parkzabava.ru/local/php_interface/app/paymentmanager.php',
				'TITLE' => 'Управление оплатами',
				'DESCRIPTION' => 'Поле позволяет взаимодействовать с сайтом для оплаты/предоплаты сделок'
			)
		);
	}
}