<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("yourLevelsTitle"), Dashboard::string("errorLoginRequired")));
$userID = $person['userID'];

$order = "uploadDate";
$orderSorting = "DESC";
$filters = ["levels.userID = '".$userID."'"];
$pageOffset = is_numeric($_GET["page"]) ? (Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$levels = Library::getLevels($filters, $order, $orderSorting, '', $pageOffset, false);

foreach($levels['levels'] AS &$level) $page .= Dashboard::renderLevelCard($level);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor($levels['count'] / 10) + 1;

$dataArray = [
	'LEVEL_PAGE' => $page,
	'LEVEL_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];
exit(Dashboard::renderPage("browse/levels", Dashboard::string("yourLevelsTitle"), "../", $dataArray));
?>