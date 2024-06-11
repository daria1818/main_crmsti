<?
$MESS['XGUARD_TEMPLATE_PREPARE_ITEMS_LIST_FOR_MAIL'] = '
<table border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
    <tr>
        <td valign="top" width="50%" style="border-bottom:1px solid #09b68f;border-right:1px solid #09b68f;">
            <div style="font-size:15pt;color: #09b68f;/*border:1px solid #09b68f;border-top: none;border-left: none;*/padding-left:10px;padding-top:14px;padding-bottom:14px;">#PRICE#</div>
        </td>
        <td width="50%" valign="top" style="border:none;">
            #URL_DISCOUNTS_SVG_ICONS_IMAGE#
            <!--<img width="71px" height="51px" style="margin-left: 5px;margin-top:3px;#URL_DISCOUNTS_SVG_ICONS_IMAGE_DISPLAY#" src="#HTTP##URL_DISCOUNTS_SVG_ICONS_IMAGE#">-->
            <!--<svg width="71px" height="51px" style="margin-left: 5px;margin-top:3px;">
                    <use xmlns:xlink="//www.w3.org/1999/xlink" xlink:href="#HTTP##URL_DISCOUNTS_SVG_ICONS_IMAGE#"></use>
            </svg>-->
        </td>
    </tr>
    <tr>
        <td valign="middle" align="center" colspan="2" height="220px"><a href="#HTTP##DETAIL_PAGE_URL#"><img src="#HTTP##DETAIL_PICTURE#" border="0"></td>
    </tr>
    <tr>
        <td valign="top" bgcolor="#f2f2f2" colspan="2" style="height:50px;font-size:11pt;color:#063a4b;padding:5px;">#NAME#</td>
    </tr>
    <tr>
        <td valign="top" align="center" colspan="2"><a href="#HTTP##DETAIL_PAGE_URL#"><img src="#URL_BUY_BUTTON#" border="0"></td>
    </tr>
</table>';