<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

$module_id = 'inteolocal.zabava';

CModule::IncludeModule($module_id);

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

$rights = $APPLICATION->GetGroupRight($module_id);

if ($rights >= "R")
{
	$arModuleOptions = array(
		Loc::getMessage("INTEOLOCAL_ZABAVA_DEBUG"),
		array("debug", Loc::getMessage("INTEOLOCAL_ZABAVA_DEBUG_LOG"), "N", Array("checkbox")),
	);

	$arMainTabs = array(
		array(
			"DIV" => "edit1", "TAB" => Loc::getMessage("INTEOLOCAL_ZABAVA_TAB"), "ICON" => "im_path", "TITLE" => Loc::getMessage("INTEOLOCAL_ZABAVA_TITLE"),
		),
	);

	$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();


	if ($request->isPost() && $request['Apply'] && $rights >= "W" && check_bitrix_sessid())
	{
		foreach ($arModuleOptions as $Option)
		{
			if (!is_array($Option))
			{
				continue;
			}

			if ($Option['note'])
			{
				continue;
			}

			$optionName = $Option[0];
			$optionValue = $request->getPost($optionName);

			Option::set($module_id, $optionName, is_array($optionValue) ? implode(",", $optionValue):$optionValue);
		}
	}

	$tabMainControl = new CAdminTabControl("tabControl", $arMainTabs);

	?><form name="inteolocal_zabava_options" method="post" action="<?
echo $APPLICATION->GetCurPage();
?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&amp;lang=<?=$request['lang']?>">
	<?=bitrix_sessid_post();?>
	<?$tabMainControl->Begin();
	$tabMainControl->BeginNextTab();

	foreach ($arModuleOptions as $Option)
	{
		__AdmSettingsDrawRow($module_id, $Option);
	}
	$tabMainControl->Buttons();
	?><input <?
	if ($rights < "W")
	{
		echo "disabled";
	}
	?> type="submit" name="Apply" class="submit-btn" value="<?=Loc::getMessage("MAIN_OPT_APPLY")?>" title="<?=Loc::getMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?$tabMainControl->End();?>
	</form><?
}
else
{
	CAdminMessage::ShowMessage(Loc::getMessage('INTEO_CORPORATION_RIGHTS_ERROR'));
}
?>