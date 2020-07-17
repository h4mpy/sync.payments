<?php
require_once('crest.php');

$result = CRest::installApp();
$newTrigger = CRest::call(
	'crm.automation.trigger.add',
	[
		'CODE' => 'ORDER_PAY',
		'NAME' => 'Заказ оплачен полностью'
	]
);

$newTriggerPre = CRest::call(
	'crm.automation.trigger.add',
	[
		'CODE' => 'ORDER_PREPAY',
		'NAME' => 'Заказ оплачен частично'
	]
);

CRest::call(
	'event.bind',
	[
		'EVENT' => 'onCrmDealUpdate',
		'HANDLER' => 'https://www.parkzabava.ru/local/php_interface/app/events.php'
	]
);
/*
$result = restCommand('event.bind', Array(
	'EVENT' => 'OnAppUpdate',
	'HANDLER' => $handlerBackUrl
), $_REQUEST["auth"]);
*/
if ($result['rest_only'] === false):?>
	<head>
		<script src="//api.bitrix24.com/api/v1/"></script>
		<?
		if ($result['install'] == true):?>
			<script>
				BX24.init(function () {
					BX24.installFinish();
				});
			</script>
		<?endif; ?>
	</head>
	<body>
	<?
	if ($result['install'] == true):?>
		installation has been finished
	<? else:?>
		installation error
	<?endif; ?>
	</body>
<?endif;

