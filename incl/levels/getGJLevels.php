<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person["accountID"];
$userID = $person["userID"];

$time = time();
$echoString = $userString = $songsString = $queryJoin = '';
$levelsStatsArray = $epicParams = [];
$order = "uploadDate";
$orderSorting = "DESC";
$orderEnabled = $isIDSearch = $noLimit = false;
$filters = ["(unlisted = 0 AND unlisted2 = 0)"];

$str = Escape::text($_POST["str"]) ?: '';
$gameVersion = Escape::number($_POST["gameVersion"]) ?: 18;
$binaryVersion = Escape::number($_POST["binaryVersion"]) ?: 0;
$type = Escape::number($_POST["type"]) ?: 0;
$diff = Escape::multiple_ids($_POST["diff"]) ?: '-';

$pageOffset = is_numeric($_POST["page"]) ? Escape::number($_POST["page"]) * 10 : 0;

// Additional search parameters

if(!$showAllLevels) $filters[] = "levels.gameVersion <= '".$gameVersion."'";

if(isset($_POST["original"]) && $_POST["original"] == 1) $filters[] = "original = 0";
if(isset($_POST["coins"]) && $_POST["coins"] == 1) $filters[] = "starCoins = 1 AND NOT levels.coins = 0";
if((isset($_POST["uncompleted"]) || isset($_POST["onlyCompleted"])) && ($_POST["uncompleted"] == 1 || $_POST["onlyCompleted"] == 1)) {
	$completedLevels = Escape::multiple_ids($_POST["completedLevels"]);
	$filters[] = ($_POST['uncompleted'] == 1 ? 'NOT ' : '')."levelID IN (".$completedLevels.")";
}
if(isset($_POST["song"]) && $_POST["song"] > 0) {
	$song = Escape::number($_POST["song"]);
	if(!isset($_POST["customSong"])) {
		$song = $song - 1;
		$filters[] = "audioTrack = '".$song."' AND songID = 0";
	} else $filters[] = "songID = '".$song."'";
}
if(isset($_POST["twoPlayer"]) && $_POST["twoPlayer"] == 1) $filters[] = "twoPlayer = 1";
if(isset($_POST["star"]) && $_POST["star"] == 1) $filters[] = "NOT starStars = 0";
if(isset($_POST["noStar"]) && $_POST["noStar"] == 1) $filters[] = "starStars = 0";
if(isset($_POST["gauntlet"]) && $_POST["gauntlet"] != 0) {
	$gauntletID = Escape::number($_POST["gauntlet"]);
	
	$gauntlet = Library::getGauntletByID($gauntletID);
	$str = $gauntlet["level1"].",".$gauntlet["level2"].",".$gauntlet["level3"].",".$gauntlet["level4"].",".$gauntlet["level5"];

	$type = 10;
}
$len = Escape::multiple_ids($_POST["len"]) ?: '-';
if($len != "-" AND !empty($len)) $filters[] = "levelLength IN (".$len.")";
if(isset($_POST["featured"]) && $_POST["featured"] == 1) $epicParams[] = "starFeatured > 0";
if(isset($_POST["epic"]) && $_POST["epic"] == 1) $epicParams[] = "starEpic = 1";
if(isset($_POST["mythic"]) && $_POST["mythic"] == 1) $epicParams[] = "starEpic = 2"; // The reason why Mythic and Legendary ratings are swapped: RobTop accidentally swapped them in-game
if(isset($_POST["legendary"]) && $_POST["legendary"] == 1) $epicParams[] = "starEpic = 3";
$epicFilter = implode(" OR ", $epicParams);
if(!empty($epicFilter)) $filters[] = $epicFilter;

// Difficulty filters
switch($diff) {
	case -1:
		$filters[] = "starDifficulty = '0'";
		break;
	case -3:
		$filters[] = "starAuto = '1'";
		break;
	case -2:
		$demonFilter = Escape::number($_POST["demonFilter"]) ?: 0;
		$filters[] = "starDemon = 1";
		switch($demonFilter) {
			case 1:
				$filters[] = "starDemonDiff = '3'";
				break;
			case 2:
				$filters[] = "starDemonDiff = '4'";
				break;
			case 3:
				$filters[] = "starDemonDiff = '0'";
				break;
			case 4:
				$filters[] = "starDemonDiff = '5'";
				break;
			case 5:
				$filters[] = "starDemonDiff = '6'";
				break;
		}
		break;
	case "-";
		break;
	default:
		if($diff) {
			$diff = str_replace(",", "0,", $diff)."0";
			$filters[] = "starDifficulty IN (".$diff.") AND starAuto = '0' AND starDemon = '0'";
		}
		break;
}

// Type detection
switch($type) {
	case 0: // Search
	case 15: // Most liked, changed to 15 in GDW for whatever reason
		$order = "likes";
		if(!empty($str)) {
			if(is_numeric($str)) {
				$friendsArray = Library::getFriends($accountID);
				$friendsArray[] = $accountID;
				$friendsString = "'".implode("','", $friendsArray)."'";
				$filters = ["levelID = ".$str." AND (
					unlisted != 1 OR
					(unlisted = 1 AND (extID IN (".$friendsString.")))
				)"];
				$isIDSearch = true;
			} else {
				$firstCharacter = $enableUserLevelsSearching ? substr($str, 0, 1) : 'd';
				switch($firstCharacter) {
					case 'u':
						$potentialUserID = substr($str, 1);
						if(is_numeric($potentialUserID)) {
							$filters[] = "userID = ".$potentialUserID;
							break;
						}
					case 'a':
						$potentialAccountID = substr($str, 1);
						if(is_numeric($potentialAccountID)) {
							$filters[] = "extID = ".$potentialAccountID;
							break;
						}
					default:
						$filters[] = "levelName LIKE '%".$str."%'";
						break;
				}
			}
		}
		break;
	case 1: // Most downloaded
		$order = "downloads";
		break;
	case 2: // Most liked
		$order = "likes";
		break;
	case 3: // Trending
		$uploadDate = $time - (7 * 24 * 60 * 60);
		$filters[] = "uploadDate > ".$uploadDate;
		$order = "likes";
		break;
	case 5: // Levels per user
		if($userID == $str) $filters = [];
		$filters[] = "levels.userID = '".$str."'";
		break;
	case 6: // Featured
	case 17: // Featured in GDW
		if($gameVersion > 21) $filters[] = "NOT starFeatured = 0 OR NOT starEpic = 0";
		else $filters[] = "NOT starFeatured = 0";
		$order = "starFeatured DESC, rateDate DESC, uploadDate";
		break;
	case 16: // Hall of Fame
		$filters[] = "NOT starEpic = 0";
		$order = "starFeatured DESC, rateDate DESC, uploadDate";
		break;
	case 7: // Magic
        $filters[] = "objects > 9999"; // L
		break;
	case 10: // Map Packs
	case 19: // Unknown, but same as Map Packs (on real GD type 10 has star rated filter and 19 doesn't)
		$levelsArray = explode(',', $str);
		$levelsText = '';
		
		foreach($levelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
		
		$order = 'CASE
			'.$levelsText.'
		END';
		$orderSorting = 'ASC';
		
		$friendsArray = Library::getFriends($accountID);
		$friendsArray[] = $accountID;
		$friendsString = "'".implode("','", $friendsArray)."'";
		
		$filters[] = "levelID IN (".$str.") AND (
				unlisted != 1 OR
				(unlisted = 1 AND (extID IN (".$friendsString.")))
			)";
			
		$noLimit = true;
		break;
	case 11: // Awarded
		$filters[] = "NOT starStars = 0";
		$order = "rateDate DESC, uploadDate";
		break;
	case 12: // Followed
		$followed = Escape::multiple_ids($_POST["followed"]);
		$filters[] = $followed ? "extID IN (".$followed.")" : "1 != 1";
		break;
	case 13: // Friends
		$friendsArray = Library::getFriends($accountID);
		$friendsString = "'".implode("','", $friendsArray)."'";
		$filters[] = $friendsString ? "extID IN (".$friendsString.")" : "1 != 1";
		break;
	case 21: // Daily safe
		$queryJoin = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
		$filters[] = "dailyfeatures.type = 0 AND timestamp < ".$time;
		$order = "dailyfeatures.feaID";
		break;
	case 22: // Weekly safe
		$queryJoin = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
		$filters[] = "dailyfeatures.type = 1 AND timestamp < ".$time;
		$order = "dailyfeatures.feaID";
		break;
	case 23: // Event safe
		$queryJoin = "INNER JOIN events ON levels.levelID = events.levelID";
		$filters[] = "timestamp < ".$time;
		$order = "events.feaID";
		break;
	case 25: // List levels
		$listLevels = Library::getListLevels($str);
		$friendsArray = Library::getFriends($accountID);
		$friendsArray[] = $accountID;
		$friendsString = "'".implode("','", $friendsArray)."'";
		
		$filters = ["levelID IN (".$listLevels.") AND (
				unlisted != 1 OR
				(unlisted = 1 AND (extID IN (".$friendsString.")))
			)"];
		$noLimit = true;
		break;
	case 27: // Sent levels
		$queryJoin = "JOIN (SELECT suggestLevelId AS levelID, MAX(suggest.timestamp) AS timestamp FROM suggest GROUP BY levelID) suggest ON levels.levelID = suggest.levelID";
		$filters[] = "suggest.levelID > 0";
		if(!$ratedLevelsInSent) $filters[] = "starStars = 0";
		$order = 'suggest.timestamp';
		break;
}

$levels = Library::getLevels($filters, $order, $orderSorting, $queryJoin, $pageOffset, $noLimit);

foreach($levels['levels'] as &$level) {
	if(empty($level["levelID"])) continue;
	if($isIDSearch && !Library::canAccountPlayLevel($person, $level)) break;
	
	if($gameVersion < 20) $level['levelDesc'] = Escape::gd(Escape::url_base64_decode($level['levelDesc']));
	$levelsStatsArray[] = ["levelID" => $level["levelID"], "stars" => $level["starStars"], 'coins' => $level["starCoins"]];
	if(isset($gauntlet)) $echoString .= "44:1:";
	$echoString .= "1:".$level["levelID"].":2:".Escape::translit($level["levelName"]).":5:".$level["levelVersion"].":6:".$level["userID"].":8:".$level["difficultyDenominator"].":9:".$level["starDifficulty"].":10:".$level["downloads"].":12:".$level["audioTrack"].":13:".$level["gameVersion"].":14:".$level["likes"].":16:".$level["dislikes"].":17:".$level["starDemon"].":43:".$level["starDemonDiff"].":25:".$level["starAuto"].":18:".$level["starStars"].":19:".$level["starFeatured"].":42:".$level["starEpic"].":45:".$level["objects"].":3:".Escape::translit($level["levelDesc"]).":15:".$level["levelLength"].":28:".Library::makeTime($level['uploadDate']).($level['updateDate'] ? ":29:".Library::makeTime($level['updateDate']) : "").":30:".$level["original"].":31:".$level['twoPlayer'].":37:".$level["coins"].":38:".$level["starCoins"].":39:".$level["requestedStars"].":46:".$level["wt"].":47:".$level["wt2"].":40:".$level["isLDM"].":35:".$level["songID"]."|";
	if($level["songID"] != 0) {
		$song = Library::getSongString($level["songID"]);
		if($song) $songsString .= $song."~:~";
	}
	$userString .= Library::getUserString($level)."|";
}
$echoString = rtrim($echoString, "|");
$userString = rtrim($userString, "|");
$songsString = rtrim($songsString, "~:~");
exit($echoString."#".$userString.($gameVersion > 18 ? "#".$songsString : '')."#".$levels['count'].":".$pageOffset.":10"."#".Security::generateLevelsHash($levelsStatsArray));
?>