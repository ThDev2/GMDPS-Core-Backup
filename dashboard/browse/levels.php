<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$userID = $person['userID'];

// Level page
if($_GET['id']) {
	$parameters = explode("/", Escape::text($_GET['id']));
	
	$levelID = Escape::number($parameters[0]);
	
	$level = Library::getLevelByID($levelID);
	if(!$level || !Library::canAccountPlayLevel($person, $level)) exit(Dashboard::renderErrorPage(Dashboard::string("levelsTitle"), Dashboard::string("errorLevelNotFound"), '../../'));

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
	if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href title"';
	
	$level['LEVEL_TITLE'] = sprintf(Dashboard::string('levelTitle'), $level['levelName'], Dashboard::getUsernameString($user['userName'], $iconKit['main'], $userAppearance['modBadgeLevel'], implode(' ', $userAttributes)));
	$level['LEVEL_DESCRIPTION'] = Dashboard::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($level['levelDesc']))) ?: "<i>".Dashboard::string('noDescription')."</i>";
	$level['LEVEL_DIFFICULTY_IMAGE'] = Library::getLevelDifficultyImage($level);
	
	$level['LEVEL_LENGTH'] = $levelLengths[$level['levelLength']];
	
	$song = $level['songID'] ? Library::getSongByID($level['songID']) : Library::getAudioTrack($level['audioTrack']);
	
	if($song) $level['LEVEL_SONG'] = $song['authorName']." - ".$song['name'].(isset($song['ID']) ? " • <text dashboard-copy>".$song['ID'].'</text>' : '');
	else $level['LEVEL_SONG'] = Dashboard::string("unknownSong");
	$level['LEVEL_SONG_ID'] = $song['ID'] ?: '';
	$level['LEVEL_SONG_AUTHOR'] = $song['authorName'] ?: '';
	$level['LEVEL_SONG_TITLE'] = $song['name'] ?: '';
	$level['LEVEL_SONG_URL'] = urlencode(urldecode($song['download'])) ?: '';
	$level['LEVEL_IS_CUSTOM_SONG'] = isset($song['ID']) ? 'true' : 'false';
	
	$songIDs = $level['songIDs'] ? explode(',', $level['songIDs']) : [];
	if($song['ID']) array_unshift($songIDs, $level['songID']);
	$sfxIDs = $level['sfxIDs'] ? explode(',', $level['sfxIDs']) : [];
	
	$songIDs = array_unique($songIDs);
	$sfxIDs = array_unique($sfxIDs);
	
	$level['LEVEL_SONGS'] = count($songIDs);
	$level['LEVEL_SFXS'] = count($sfxIDs);
	
	$level['LEVEL_SONG_IDS'] = implode(',', $songIDs);
	$level['LEVEL_SFX_IDS'] = $level['sfxIDs'];
	
	$level['LEVEL_HAS_REQUESTED_STARS'] = $level['requestedStars'] ? 'true' : 'false';
	
	$levelStatsCount = Library::getLevelStatsCount($levelID);

	$level['LEVEL_COMMENTS'] = $levelStatsCount['comments'];
	$level['LEVEL_SCORES'] = $levelStatsCount['scores'];
	
	$level['LEVEL_CAN_SEE_PASSWORD'] = (Library::checkPermission($person, "dashboardModTools") && strlen($level['password']) > 1) ? 'true' : 'false';
	$level['LEVEL_PASSWORD'] = $level['LEVEL_CAN_SEE_PASSWORD'] == 'true' ? substr($level['password'], 1) : 'No password for you :)';
	
	$level['LEVEL_CAN_MANAGE'] = Library::checkPermission($person, "dashboardManageLevels") ? 'true' : 'false';
	
	$pageBase = '../../';
	$level['LEVEL_IS_NOTHING_OPENED'] = 'true';
	$level['LEVEL_ADDITIONAL_PAGE'] = '';
	
	if(isset($parameters[1])) {
		$additionalPage = '';
		$pageBase = '../../../';
		$level['LEVEL_IS_NOTHING_OPENED'] = 'false';
		
		$pageOffset = is_numeric($_GET["page"]) ? (Escape::number($_GET["page"]) - 1) * 10 : 0;
		
		switch($parameters[1]) {
			case 'comments':
				$mode = isset($_GET['mode']) ? Escape::number($_GET["mode"]) : 1;
			
				$sortMode = $mode ? "comments.likes - comments.dislikes" : "comments.timestamp";
				
				$comments = Library::getCommentsOfLevel($levelID, $sortMode, $pageOffset);
				
				foreach($comments['comments'] AS &$comment) $additionalPage .= Dashboard::renderCommentCard($comment, $person);
				
				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor(($comments['count'] - 1) / 10) + 1;
				
				if($pageCount == 0) $pageCount = 1;
				
				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'LEVEL_NO_COMMENTS' => !$comments['count'] ? 'true' : 'false',
					'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					'LEVEL_ID' => $levelID,
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
				];
				
				if(!$additionalPage) $additionalData['LEVEL_NO_COMMENTS'] = 'true';
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/comments', $additionalData);
				break;
			case 'scores':
				$levelIsPlatformer = $level['levelLength'] == 5;
			
				$type = Escape::number($_GET['type']) ?: 0;
				$mode = $_GET['mode'] == 1 ? 'points' : 'time';
				$dailyID = $_GET['isDaily'] ? 1 : 0;
				
				$scores = $levelIsPlatformer ? Library::getPlatformerLevelScores($levelID, $person, $type, $dailyID, $mode) : Library::getLevelScores($levelID, $person, $type, $dailyID);
				
				$pageNumber = $pageOffset * -1;
				$scoreNumber = $pageOffset;
				foreach($scores AS &$score) {
					$pageNumber++;
					if($pageNumber < 1) continue;
					
					$scoreNumber++;
					if($scoreNumber > $pageOffset + 10) break;
					
					$score['SCORE_NUMBER'] = $scoreNumber;
					$additionalPage .= Dashboard::renderScoreCard($score, $person, $levelIsPlatformer);
				}
				
				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor((count($scores) - 1) / 10) + 1;
				
				if($pageCount == 0) $pageCount = 1;
				
				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'LEVEL_NO_SCORES' => !count($scores) ? 'true' : 'false',
					'LEVEL_IS_PLATFORMER' => $levelIsPlatformer ? 'true' : 'false',
					'LEVEL_IS_DAILY' => $dailyID ? 'true' : 'false',
					'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
				];
				
				if(!$additionalPage) $additionalData['LEVEL_NO_SCORES'] = 'true';
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/scores', $additionalData);
				break;
			case 'songs':
				if(empty($level['LEVEL_SONG_IDS'])) {
					$pageNumber = $pageCount = 1;
				
					$additionalData = [
						'ADDITIONAL_PAGE' => '',
						'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
						'SONG_NO_SONGS' => 'true',
						
						'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
						'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
						
						'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
						'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
						'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
						'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
					];
					
					$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate("browse/songs", $additionalData);
					break;
				}
				
				$favouriteSongs = [];
				if($person['success']) {
					$favouriteSongsArray = Library::getFavouriteSongs($person, 0, false);
					
					foreach($favouriteSongsArray['songs'] AS &$favouriteSong) $favouriteSongs[] = $favouriteSong["songID"];
				}
				
				$filters = ["songs.ID IN (".Escape::multiple_ids($level['LEVEL_SONG_IDS']).")"];
				$additionalPage = '';

				$songs = Library::getSongs($filters, '', '', '', $pageOffset, 10);

				foreach($songs['songs'] AS &$song) $additionalPage .= Dashboard::renderSongCard($song, $person, $favouriteSongs);

				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor($songs['count'] / 10) + 1;

				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					'SONG_NO_SONGS' => empty($additionalPage) ? 'true' : 'false',
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
				];
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate("browse/songs", $additionalData);
				break;
			case 'sfxs':
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/manage', $additionalData);
				break;
			case 'manage':
				if(!Library::checkPermission($person, "dashboardManageLevels")) exit(Dashboard::renderErrorPage(Dashboard::string("levelsTitle"), Dashboard::string("errorNoPermission"), '../../../'));
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/manage', $additionalData);
				break;
			default:
				exit(http_response_code(404));
		}
	}
	
	exit(Dashboard::renderPage("browse/level", $level['levelName'], $pageBase, $level));
}

// Search levels
$order = "uploadDate";
$orderSorting = "DESC";
$filters = ["(unlisted = 0 AND unlisted2 = 0)"];
$pageOffset = is_numeric($_GET["page"]) ? (Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$levels = Library::getLevels($filters, $order, $orderSorting, '', $pageOffset, false);

foreach($levels['levels'] AS &$level) $page .= Dashboard::renderLevelCard($level, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($levels['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'LEVEL_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'LEVEL_NO_LEVELS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];

$fullPage = Dashboard::renderTemplate("browse/levels", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("levelsTitle"), "../", $fullPage));
?>