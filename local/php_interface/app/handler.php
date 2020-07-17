<?php

use Bitrix\Main\SiteTable;
use Inteolocal\Zabava\Order;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
require_once('crest.php');

?><div class="empty-order"><?

if (Bitrix\Main\Loader::includeModule('inteolocal.zabava') && Bitrix\Main\Loader::includeModule('sale'))
{

	if (isset($_POST['ADD_ORDER']) && intval($_POST['ADD_ORDER']))
	{
		$site = SiteTable::getList(array('filter' => array('DEF' => 'Y')))->fetch();
		Bitrix\Main\Loader::includeModule('inteo.corporation');
		$result = Order::saveOrderFromDeal(intval($_POST['ADD_ORDER']), true);
		if ($result->isSuccess())
		{
			$resultData = $result->getData();
			header('Location: https://www.parkzabava.ru/bitrix/admin/sale_order_view.php?ID=' . $resultData['ORDER_ID'] . '&filter=Y&set_filter=Y&lang=ru&IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER');
		}
		else
		{
			?><div class="empty-order__item">
				<div class="ui-alert ui-alert-warning">
					<span class="ui-alert-message"><strong>Ошибка создания заказа.</strong> Администратор уведомлен об ошибке</span>
				</div>
			</div><?
			$eventTypeName = "INTEOLOCAL_ERROR";
			$arEvent = \CEventType::GetByID($eventTypeName, $site["LANGUAGE_ID"])->Fetch();
			if (!is_array($arEvent))
			{
				$eventType = new \CEventType;
				$arEventTypeFields = array(
					"LID" => $site["LANGUAGE_ID"],
					"EVENT_NAME" => "Ошибки модуля обмена с Б24",
					"NAME" => "Ошибки модуля обмена с Б24",
					"DESCRIPTION" => "#MESSAGE#",
				);
				$eventType->Add($arEventTypeFields);
			}

			$arMessage = \CEventMessage::GetList($site["LID"], $order = "desc", array("TYPE_ID" => $eventTypeName))->Fetch();
			if (!is_array($arMessage))
			{
				$eventMessage = new \CEventMessage;
				$arMessage = array();
				$arMessage["ID"] = $eventMessage->Add(array(
					"ACTIVE" => "Y",
					"EVENT_NAME" => $eventTypeName,
					"LID" => array($site["LID"]),
					"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
					"EMAIL_TO" => "support@2horizon.ru",
					"CC" => "",
					"BCC" => "",
					"SUBJECT" => "Ошибка создания заказа из сделки",
					"BODY_TYPE" => "html",
					"REPLY_TO" => "",
					"MESSAGE" => "#MESSAGE#"
				));
			}

			$arMailFields = array(
				"CC" => "",
				"BCC" => "",
				"REPLY_TO" => "",
				"FILES" => "",
			);
			$arMailFields["MESSAGE"] = "Сделка {$_POST['ADD_ORDER']} <br>";
			$arMailFields["MESSAGE"] .= implode(',',$result->getErrorMessages());
			\CEvent::SendImmediate($eventTypeName, $site["LID"], $arMailFields, "Y", $arMessage["ID"]);
		}
		die;
	}
	if (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] == 'CRM_DEAL_DETAIL_ACTIVITY')
	{
		if (isset($_REQUEST['PLACEMENT_OPTIONS']))
		{
			$arOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS']);
			if (intval($arOptions->ID) > 0)
			{

				$bitrixUser = new \Inteolocal\Zabava\User(CRest::call(
					'user.current',
					[]
				));

				//Группы из Б24, которые имеют доступ к заказам
				$bitrixUser->setAllowedGroups(array(
					1,    //Директор
					5,    //Отдел продаж
					7    //IT-отдел
				));

				if ($bitrixUser->isAccessAllowed())
				{
					//Группы в БУС, в которых должен состоять пользователь из Б24
					$bitrixUser->setUserDefaultGroups(array(5, 6, 7));
					$bitrixUser->Authorize();

					$arDeal = CRest::call(
						'crm.deal.get',
						[
							'id' => $arOptions->ID
						]
					);

					if ($arDeal['result']['ORIGIN_ID'] > 0 && $arDeal['result']['ORIGINATOR_ID'] == 's1' && $arOrder = \Bitrix\Sale\Order::load($arDeal['result']['ORIGIN_ID']))
					{
						header('Location: https://www.parkzabava.ru/bitrix/admin/sale_order_view.php?ID=' . $arDeal['result']['ORIGIN_ID'] . '&filter=Y&set_filter=Y&lang=ru&IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER');
						die;
					}
					else
					{
						?><div class="empty-order__item">Заказ не найден</div>
						<div class="empty-order__item">
							<span data-js-sendform class="ui-btn ui-btn-primary<?/*ui-btn-clock*/?>">Создать заказ</span>
						</div>
						<form action="" method="post" name="sendForm">
							<input type="hidden" name="ADD_ORDER" value="<?echo $arOptions->ID?>">
						</form>
						<script>
							var elem = document.querySelector("[data-js-sendform]");
							elem.onclick = function() {
								if (elem.classList.contains('ui-btn-clock')) return false;
								elem.classList.add('ui-btn-clock');
								document.sendForm.submit();
								return false;
							};
						</script><?

					}
				}
				else
				{
					?><div class="empty-order__item">
						<div class="ui-alert ui-alert-warning">
							<span class="ui-alert-message"><strong>У вас нет доступа к заказам.</strong> Если это произошло по ошибке, обратитесь к администратору</span>
						</div>
					</div><?
				}
			}
		}
	}
}
?>
</div><?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>