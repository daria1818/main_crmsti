<?
global $MESS;
IncludeModuleLangFile(dirname(__FILE__).'/install.php');
 
if(class_exists("xguard_main")) return;
 
Class xguard_main extends CModule
{
    public $MODULE_ID = "xguard.main";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = "Y";
    public $PARTNER_NAME;
    public $PARTNER_URI;
 
    public function xguard_main()
    {
		$this->MODULE_VERSION		= '0.0.1';
        $this->MODULE_VERSION_DATE	= '2013-09-21 14:46:23';
        $this->MODULE_NAME			= GetMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION	= GetMessage('MODULE_DESCRIPTION');
        $this->PARTNER_NAME			= GetMessage('MODULE_PARTNER');
        $this->PARTNER_URI			= GetMessage('MODULE_PARTNER_URI');
    }
	public function __construct()
	{
		$this->xguard_main();
	}
	
    public function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage('INSTALL_TITLE'), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/step.php");        
    }
     
    public function InstallFiles($arParams = array())
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/".$this->MODULE_ID."/", true, true);
        return true;
    }
	
    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/components/".$this->MODULE_ID."/".$this->MODULE_ID."/");
        return true;
    }
     
    public function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->UnInstallFiles();
        UnRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage('UNINSTALL_TITLE'), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep.php");
    }
}
?>