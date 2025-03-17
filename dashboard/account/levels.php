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
foreach($levels['levels'] AS &$level) {
	$user = Library::getUserByID($level['userID']);
	
	$userAttributes = [];
	$levelLengths = ['Tiny', 'Short', 'Medium', 'Long', 'XL', 'Platformer'];
	
	$userPerson = [
		'accountID' => $user['extID'],
		'userID' => $user['userID'],
		'IP' => $user['IP'],
	];
	$iconKit = Dashboard::getUserIconKit($userID);
	$userAppearance = Library::getPersonCommentAppearance($userPerson);
	$userColor = str_replace(",", " ", $userAppearance['commentColor']);
	
	if($userColor != '255 255 255') $userAttributes[] = 'style="--href-color: rgb('.$userColor.'); --href-shadow-color: rgb('.$userColor.' / 38%)"';
	if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href"';
	
	$song = $level['songID'] ? Library::getSongByID($level['songID']) : Library::getAudioTrack($level['audioTrack']);
	
	$level['LEVEL_TITLE'] = sprintf(Dashboard::string('levelTitle'), $level['levelName'], Dashboard::getUsernameString($user['userName'], $iconKit['main'], implode(' ', $userAttributes)));
	$level['LEVEL_DESCRIPTION'] = htmlspecialchars(Escape::url_base64_decode($level['levelDesc'])) ?: "<i>".Dashboard::string('noDescription')."</i>";
	$level['LEVEL_DIFFICULTY_IMAGE'] = Library::getLevelDifficultyImage($level);
	
	$level['LEVEL_LENGTH'] = $levelLengths[$level['levelLength']];
	$level['LEVEL_LIKES'] = abs($level['likes'] - $level['dislikes']);
	$level['LEVEL_IS_DISLIKED'] = $level['dislikes'] > $level['likes'] ? 'true' : 'false';
	
	$level['LEVEL_SONG'] = $song['authorName']." - ".$song['name'].(isset($song['ID']) ? " • <text dashboard-copy>".$song['ID'].'</text>' : '');
	$level['LEVEL_SONG_AUTHOR'] = $song['authorName'];
	$level['LEVEL_SONG_TITLE'] = $song['name'];
	$level['LEVEL_SONG_URL'] = urlencode($song['download']) ?: '';
	$level['LEVEL_IS_CUSTOM_SONG'] = isset($song['ID']) ? 'true' : 'false';
	
	$page .= Dashboard::renderTemplate('components/level', $level);
}

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor($levels['count'] / 10) + 1;

$dataArray = [
	'LEVEL_PAGE' => $page,
	'LEVEL_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	'FIRST_PAGE_BUTTON' => "getPage('account/levels')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('account/levels".(($pageNumber - 1) > 1 ? "?page=".($pageNumber - 1) : '')."')",
	'NEXT_PAGE_BUTTON' => "getPage('account/levels?page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('account/levels?page=".$pageCount."')"
];
exit(Dashboard::renderPage("browse/levels", Dashboard::string("yourLevelsTitle"), "../", $dataArray));
?>