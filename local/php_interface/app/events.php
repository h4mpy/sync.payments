<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once('crest.php');

if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'ONCRMDEALUPDATE')
{
	if (isset($_REQUEST['data']['FIELDS']['ID']) && $_REQUEST['data']['FIELDS']['ID'] > 0)
	{
		$dealId = $_REQUEST['data']['FIELDS']['ID'];
		if (Bitrix\Main\Loader::includeModule('inteolocal.zabava'))
		{
			\Inteolocal\Zabava\Order::saveOrderFromDeal($_REQUEST['data']['FIELDS']['ID'], false);
			$deal = \CRest::call(
				'crm.deal.get',
				[
					'id' => $dealId,
					//'fields' => ['UF_CRM_1573201313868' => $dealId]
				]
			);
			if (isset($deal['result']['UF_CRM_1573201313868']))
			{
				if ($deal['result']['UF_CRM_1573201313868'] != $dealId)
				{
					$result = \CRest::call(
						'crm.deal.update',
						[
							'id' => $dealId,
							'fields' => ['UF_CRM_1573201313868' => $dealId]
						]
					);
				}
			}
			//UF_CRM_1573201313868
		}
	}
}
if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'ONCRMDEALADD')
{

	$dealId = $_REQUEST['data']['FIELDS']['ID'];
	$deal = \CRest::call(
		'crm.deal.get',
		[
			'id' => $dealId,
			//'fields' => ['UF_CRM_1573201313868' => $dealId]
		]
	);
	if (isset($deal['result']['UF_CRM_1573201313868']))
	{
		if ($deal['result']['UF_CRM_1573201313868'] != $dealId)
		{
			$result = \CRest::call(
				'crm.deal.update',
				[
					'id' => $dealId,
					'fields' => ['UF_CRM_1573201313868' => $dealId]
				]
			);
		}
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>