<?
IncludeModuleLangFile(__FILE__);
/** @global CMain $APPLICATION */
global $APPLICATION;

if($APPLICATION->GetGroupRight("xguard")!="D")
{
    $arMenu = array(
        "parent_menu"   => "global_menu_services",
        "section"       => "xguard",
        "sort"          => 100,
        "text"          => GetMessage("XGUARD_MENU"),
        "title"         => GetMessage("XGUARD_MENU_TITLE"),
        "icon"          => "search_menu_icon",
        "page_icon"     => "search_page_icon",
        "items_id"      => "menu_xguard",
        "items"         => array(
            array(
                "text"      => GetMessage("XGUARD_MENU_SITEMAP"),
                "url"       => "xguard_sitemap.php?lang=".LANGUAGE_ID,
                "more_url"  => Array("xguard_sitemap.php"),
                "title"     => GetMessage("XGUARD_MENU_SITEMAP_ALT"),
            ),
        )
    );
    return $arMenu;
}
return false;
?>
