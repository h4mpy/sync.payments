<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;

Loc::loadMessages(__FILE__);

Class inteolocal_zabava extends CModule
{
	var $MODULE_ID = "inteolocal.zabava";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";
	var $PARTNER_NAME;
	var $PARTNER_URI;

	function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("INTEOLOCAL_ZABAVA_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("INTEOLOCAL_ZABAVA_MODULE_DESC");
		$this->MODULE_GROUP_RIGHTS = 'N';
		$this->PARTNER_NAME = Loc::getMessage("INTEOLOCAL_ZABAVA_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("INTEOLOCAL_ZABAVA_PARTNER_URI");
	}

	public function GetPath($notDocumentRoot=false)
	{
		if ($notDocumentRoot)
			return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
		else
			return dirname(__DIR__);
	}

	public function isVersionD7()
	{
		return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
	}

	function InstallDB($arParams = array())
	{
		Loader::includeModule($this->MODULE_ID);

		if(!Application::getConnection(\Inteolocal\Zabava\Internals\HistoryTable::getConnectionName())->isTableExists(
				Base::getInstance('\Inteolocal\Zabava\Internals\HistoryTable')->getDBTableName()
			)
		)
		{
			Base::getInstance('\Inteolocal\Zabava\Internals\HistoryTable')->createDbTable();
		}

		if(!Application::getConnection(\Inteolocal\Zabava\Internals\EventlogTable::getConnectionName())->isTableExists(
			Base::getInstance('\Inteolocal\Zabava\Internals\EventlogTable')->getDBTableName()
		)
		)
		{
			Base::getInstance('\Inteolocal\Zabava\Internals\EventlogTable')->createDbTable();
		}

	}

	function UnInstallDB($arParams = array())
	{
		Loader::includeModule($this->MODULE_ID);

		Application::getConnection(\Inteolocal\Zabava\Internals\HistoryTable::getConnectionName())->
			queryExecute('drop table if exists '.Base::getInstance('\Inteolocal\Zabava\Internals\HistoryTable')->getDBTableName());

		Application::getConnection(\Inteolocal\Zabava\Internals\EventlogTable::getConnectionName())->
		queryExecute('drop table if exists '.Base::getInstance('\Inteolocal\Zabava\Internals\EventlogTable')->getDBTableName());

		Option::delete($this->MODULE_ID);
		CAgent::RemoveModuleAgents("inteolocal.zabava");
	}

	function InstallEvents()
	{
		EventManager::getInstance()->registerEventHandler('sale', 'OnSaleOrderBeforeSaved', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnSaleOrderBeforeSaved');
		EventManager::getInstance()->registerEventHandler('sale', 'OnSaleOrderSaved', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnSaleOrderSaved');
		EventManager::getInstance()->registerEventHandler('sale', 'OnSalePaymentEntitySaved', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnSalePaymentEntitySaved');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementAdd');


		/*
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockAdd');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockUpdate');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnBeforeIBlockDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnBeforeIBlockDelete');

		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockPropertyAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockPropertyAdd');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockPropertyUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockPropertyUpdate');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnBeforeIBlockPropertyDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnBeforeIBlockPropertyDelete');

		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementAdd');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementUpdate');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockElementDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementDelete');

		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockSectionAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockSectionAdd');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockSectionUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockSectionUpdate');
		EventManager::getInstance()->registerEventHandler('iblock', 'OnBeforeIBlockSectionDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnBeforeIBlockSectionDelete');
		*/
	}

	function UnInstallEvents()
	{
		EventManager::getInstance()->unRegisterEventHandler('sale', 'OnSaleOrderBeforeSaved', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnSaleOrderBeforeSaved');
		EventManager::getInstance()->unRegisterEventHandler('sale', 'OnSaleOrderSaved', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnSaleOrderSaved');
		EventManager::getInstance()->unRegisterEventHandler('sale', 'OnSalePaymentEntitySaved', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnSalePaymentEntitySaved');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementAdd');


		/*
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockAdd');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockUpdate');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnBeforeIBlockDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnBeforeIBlockDelete');

		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockPropertyAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockPropertyAdd');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockPropertyUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockPropertyUpdate');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnBeforeIBlockPropertyDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnBeforeIBlockPropertyDelete');

		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementAdd');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementUpdate');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockElementDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockElementDelete');

		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionAdd', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockSectionAdd');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionUpdate', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnAfterIBlockSectionUpdate');
		EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionDelete', $this->MODULE_ID, '\Inteolocal\Zabava\Event', 'OnBeforeIBlockSectionDelete');
		*/
	}

	function InstallFiles($arParams = array())
	{
		/*
		CopyDirFiles(
			$this->GetPath()."/install/components/",
			$_SERVER["DOCUMENT_ROOT"]."/local/components",
			true, true
		);
		*/
		return true;
	}

	function UnInstallFiles()
	{
		if (\Bitrix\Main\IO\Directory::isDirectoryExists($p = $this->GetPath() . '/install/components'))
		{
			if ($dir = opendir($p))
			{
				while (false !== $item = readdir($dir))
				{
					if ($item == '..' || $item == '.' || !is_dir($p0 = $p.'/'.$item))
						continue;
					$dir0 = opendir($p0);
					while (false !== $item0 = readdir($dir0))
					{
						if ($item0 == '..' || $item0 == '.')
							continue;
						\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/local/components/'.$item.'/'.$item0);
					}
					closedir($dir0);
				}
				closedir($dir);
			}
		}
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/cache/Inteolocal');
		
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION;
		if ($this->isVersionD7())
		{
			\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

			$this->InstallDB();
			$this->InstallEvents();
			$this->InstallFiles();
			$APPLICATION->IncludeAdminFile(GetMessage("INTEOLOCAL_ZABAVA_INSTALL_TITLE"), $this->GetPath()."/install/step.php");
		}
		else
		{
			throw new \Bitrix\Main\SystemException(Loc::getMessage("INTEOLOCAL_ZABAVA_INSTALL_ERROR_VERSION"));
		}
	}

	function DoUninstall()
	{
		global $APPLICATION;
		$this->UnInstallDB();
		$this->UnInstallEvents();
		$this->UnInstallFiles();
		$_SESSION['INTEO_USER_ID'] = 0;
		ModuleManager::unRegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile(GetMessage("INTEOLOCAL_ZABAVA_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep.php");
	}
}
?>