<?
/**
* $USER->Authorize(1) - 1 is ID of USER
*/

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;
$USER->Authorize(1);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");  
?>
