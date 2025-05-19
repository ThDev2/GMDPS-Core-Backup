<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("yourSongsTitle"), Dashboard::string("errorLoginRequired")));
$accountID = $person['accountID'];

$order = "reuploadTime";
$orderSorting = "DESC";
$filters = ["songs.reuploadID = '".$accountID."'"];
$pageOffset = is_numeric($_GET["page"]) ? (Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$songs = Library::getSongs($filters, $order, $orderSorting, '', $pageOffset, false);

foreach($songs['songs'] AS &$song) $page .= Dashboard::renderSongCard($song, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor($songs['count'] / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'SONG_NO_SONGS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];

$fullPage = Dashboard::renderTemplate("browse/songs", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("yourSongsTitle"), "../", $fullPage));
?>