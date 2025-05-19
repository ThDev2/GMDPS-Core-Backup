<?php
/*
	Path to main directory
	
	It needs to point to main endpoint files: https://imgur.com/a/P8LdhzY
	
	Don't change this value if you don't undestand what it means!
*/
$dbPath = '../';

require_once __DIR__."/../".$dbPath."incl/lib/enums.php";

class Dashboard {
	/*
		Utils functions
	*/
	
	public static function loginDashboardUser() {
		global $dbPath;
		require __DIR__."/../".$dbPath."incl/lib/connection.php";
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		require_once __DIR__."/../".$dbPath."incl/lib/security.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		require_once __DIR__."/../".$dbPath."incl/lib/ip.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['person'])) return $GLOBALS['core_cache']['dashboard']['person'];
		
		$IP = IP::getIP();
		
		$auth = Escape::latin($_COOKIE['auth']);
		
		if(empty($auth)) {
			$GLOBALS['core_cache']['dashboard']['person'] = ["success" => false, "accountID" => "0", "IP" => $IP];
			return ["success" => false, "accountID" => "0", "IP" => $IP];
		}
		
		$checkAuth = $db->prepare("SELECT * FROM accounts WHERE auth = :auth");
		$checkAuth->execute([':auth' => $auth]);
		$checkAuth = $checkAuth->fetch();
		if(empty($checkAuth)) {
			$logPerson = [
				'accountID' => "0",
				'userID' => "0",
				'userName' => '',
				'IP' => $IP
			];
			
			setcookie('auth', '', 2147483647, '/');

			Library::logAction($logPerson, Action::FailedLogin);
			
			$GLOBALS['core_cache']['dashboard']['person'] = ["success" => false, "accountID" => "0", "IP" => $IP];
			
			return ["success" => false, "accountID" => "0", "IP" => $IP];
		}
		
		$accountID = $checkAuth['accountID'];
		$userID = Library::getUserID($checkAuth['accountID']);
		$userName = $checkAuth['userName'];
		
		if(Security::isTooManyAttempts()) {
			$logPerson = [
				'accountID' => (string)$accountID,
				'userID' => (string)$userID,
				'userName' => $userName,
				'IP' => $IP
			];
			
			setcookie('auth', '', 2147483647, '/');

			Library::logAction($logPerson, Action::FailedLogin);
			
			$GLOBALS['core_cache']['dashboard']['person'] = ["success" => false, "accountID" => (string)$accountID, "IP" => $IP];
			
			return ["success" => false, "accountID" => (string)$accountID, "IP" => $IP];
		}
		
		$GLOBALS['core_cache']['dashboard']['person'] = ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => $userName, "IP" => $IP];
		
		return ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => $userName, "IP" => $IP];
	}
	
	public static function getUserIconKit($userID) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['iconKit'][$userID])) return $GLOBALS['core_cache']['dashboard']['iconKit'][$userID];
		
		$iconTypes = ['cube', 'ship', 'ball', 'ufo', 'wave', 'robot', 'spider', 'swing', 'jetpack'];
		
		$user = Library::getUserByID($userID);
		if(!$user) {
			$iconKit = [
				"main" => $iconsRendererServer."/icon.png?type=cube&value=1&color1=0&color2=3",
				"cube" => $iconsRendererServer."/icon.png?type=cube&value=1&color1=0&color2=3",
				"ship" => $iconsRendererServer."/icon.png?type=ship&value=1&color1=0&color2=3",
				"ball" => $iconsRendererServer."/icon.png?type=ball&value=1&color1=0&color2=3",
				"ufo" => $iconsRendererServer."/icon.png?type=ufo&value=1&color1=0&color2=3",
				"wave" => $iconsRendererServer."/icon.png?type=wave&value=1&color1=0&color2=3",
				"robot" => $iconsRendererServer."/icon.png?type=robot&value=1&color1=0&color2=3",
				"spider" => $iconsRendererServer."/icon.png?type=spider&value=1&color1=0&color2=3",
				"swing" => $iconsRendererServer."/icon.png?type=swing&value=1&color1=0&color2=3",
				"jetpack" => $iconsRendererServer."/icon.png?type=jetpack&value=1&color1=0&color2=3"
			];
			
			$GLOBALS['core_cache']['dashboard']['iconKit'][$userID] = $iconKit;
			
			return $iconKit;
		}
		
		$iconKit = [
			'main' => $iconsRendererServer.'/icon.png?type='.$iconTypes[$user['iconType']].'&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'cube' => $iconsRendererServer.'/icon.png?type=cube&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'ship' => $iconsRendererServer.'/icon.png?type=ship&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'ball' => $iconsRendererServer.'/icon.png?type=ball&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'ufo' => $iconsRendererServer.'/icon.png?type=ufo&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'wave' => $iconsRendererServer.'/icon.png?type=wave&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'robot' => $iconsRendererServer.'/icon.png?type=robot&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'spider' => $iconsRendererServer.'/icon.png?type=spider&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'swing' => $iconsRendererServer.'/icon.png?type=swing&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'jetpack' => $iconsRendererServer.'/icon.png?type=jetpack&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : '')
		];
		
		$GLOBALS['core_cache']['dashboard']['iconKit'][$userID] = $iconKit;
			
		return $iconKit;
	}
	
	/*
		Translations
	*/
	
	public static function string($languageString) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['language'][$languageString])) return $GLOBALS['core_cache']['dashboard']['language'][$languageString];
		if(isset($GLOBALS['core_cache']['dashboard']['language'])) return $languageString;
		
		$language = self::allStrings();
		if(!isset($language[$languageString])) return $languageString;
		
		return $language[$languageString];
	}
	
	public static function allStrings() {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['language'])) return $GLOBALS['core_cache']['dashboard']['language'];
		
		if(!$_COOKIE['lang']) {
			//if(file_exists(__DIR__.'/langs/'.strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2))).'.php') $_COOKIE['lang'] = strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
			//else $_COOKIE['lang'] = 'EN';
			$_COOKIE['lang'] = 'EN';
				
			setcookie("lang", $_COOKIE['lang'], 2147483647, "/");
		}
		
		$userLanguage = Escape::latin_no_spaces($_COOKIE['lang'], 2);
		if(!file_exists(__DIR__."/langs/".$userLanguage.".php")) $userLanguage = 'EN';
		
		if($userLanguage != 'EN') require __DIR__."/langs/EN.php";
		require __DIR__."/langs/".$userLanguage.".php";
		
		$GLOBALS['core_cache']['dashboard']['language'] = $language;
		
		return $language;
	}
	
	public static function loadCredits() {
		if(isset($GLOBALS['core_cache']['dashboard']['languageCredits'])) return $GLOBALS['core_cache']['dashboard']['languageCredits'];
		
		$languageCredits = json_decode(file_get_contents(__DIR__."/credits.json"), true);
		
		$GLOBALS['core_cache']['dashboard']['languageCredits'] = $languageCredits;
		
		return $languageCredits;
	}
	
	/*
		Render pages
	*/
	
	public static function renderPage($template, $pageTitle, $pageBase, $dataArray) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		if(!is_array($dataArray)) $dataArray = ['PAGE' => $dataArray];
		
		$person = self::loginDashboardUser();
		$userID = $person['userID'];
		
		if(!file_exists(__DIR__."/templates/main.html") || !file_exists(__DIR__."/templates/".$template.".html") || !is_array($dataArray)) return false;
		
		$templatePage = self::renderTemplate($template, $dataArray);
		
		$iconKit = self::getUserIconKit($userID);
		
		$mainPageData = [
			'PAGE' => $templatePage,
			
			'PAGE_TITLE' => $pageTitle,
			'GDPS_NAME' => $gdps,
			'PAGE_BASE' => $pageBase,
			'DASHBOARD_FAVICON' => $dashboardFavicon,
			'DATABASE_PATH' => $dbPath,
			'STYLE_TIMESTAMP' => filemtime(__DIR__."/style.css"),
			'SCRIPT_TIMESTAMP' => filemtime(__DIR__."/script.js"),
			
			'FAILED_TO_LOAD_TEXT' => "<i class='fa-solid fa-xmark'></i>".self::string("errorFailedToLoadPage"),
			'COPIED_TEXT' => "<i class='fa-solid fa-copy'></i>".self::string("successCopiedText"),
			
			'LANGUAGE' => Escape::latin_no_spaces($_COOKIE['lang'], 2) ?: "EN",
			
			'IS_LOGGED_IN' => $person['success'] ? 'true' : 'false',
			'USERNAME' => $person['success'] ? $person['userName'] : '',
			'PROFILE_ICON' => $person['success'] ? $iconKit['main'] : '',
			
			'FOOTER' => ""
		];
		
		$personPermissions = Library::getPersonPermissions($person);
		foreach($personPermissions AS $permission => $value) $mainPageData['PERMISSION_'.$permission] = $value ? 'true' : 'false';
		
		$allStrings = self::allStrings();
		foreach($allStrings AS $string => $value) $mainPageData['TEXT_'.$string] = $value;
		
		$languageCredits = self::loadCredits();
		foreach($languageCredits['languages'] AS $string => $value) $mainPageData['LANGUAGE_'.$string] = $value;
		
		$page = self::renderTemplate('main', $mainPageData);
		
		return $page;
	}
	
	public static function renderErrorPage($pageTitle, $error, $pageBase = "../") {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		
		$dataArray = [
			'INFO_TITLE' => self::string("errorTitle"),
			'INFO_DESCRIPTION' => $error,
			'INFO_BUTTON_TEXT' => self::string("home"),
			'INFO_BUTTON_ONCLICK' => "getPage('')"
		];
		
		$page = self::renderPage("general/info", $pageTitle, $pageBase, $dataArray);
		
		return $page;
	}
	
	public static function renderToast($icon, $text, $state, $location = '') {
		return "<div id='toast' state='".$state."' location='".$location."'><i class='fa-solid fa-".$icon."'></i>".$text."</div>";
	}
	
	public static function renderTemplate($template, $dataArray) {
		if(!isset($GLOBALS['core_cache']['dashboard']['template'][$template])) {
			$templatePage = file_get_contents(__DIR__."/templates/".$template.".html");
			$GLOBALS['core_cache']['dashboard']['template'][$template] = $templatePage;
		} else $templatePage = $GLOBALS['core_cache']['dashboard']['template'][$template];
		
		if(!empty($dataArray)) foreach($dataArray AS $key => $value) $templatePage = str_replace("%".$key."%", (string)$value, $templatePage);
		
		return $templatePage;
	}
	
	public static function getUsernameString($userName, $mainIcon, $badgeNumber, $attributes = '') {
		return sprintf('<text class="username" title="'.sprintf(self::string('userProfile'), $userName).'" %3$s href="profile/%1$s">
			<text class="emptySymbol">:(</text><img src="%2$s"></img>
			%1$s
			'.($badgeNumber ? '<img src="incl/icons/badge_%4$s.png"></img>' : '').'
		</text>', $userName, $mainIcon, $attributes, $badgeNumber);
	}
	
	public static function renderLevelCard($level) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		$user = Library::getUserByID($level['userID']);
	
		$userAttributes = [];
		$levelLengths = ['Tiny', 'Short', 'Medium', 'Long', 'XL', 'Platformer'];
		
		$userPerson = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		$iconKit = self::getUserIconKit($userID);
		$userAppearance = Library::getPersonCommentAppearance($userPerson);
		$userColor = str_replace(",", " ", $userAppearance['commentColor']);
		
		if($userColor != '255 255 255') $userAttributes[] = 'style="--href-color: rgb('.$userColor.'); --href-shadow-color: rgb('.$userColor.' / 38%)"';
		if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href title"';
		
		$song = $level['songID'] ? Library::getSongByID($level['songID']) : Library::getAudioTrack($level['audioTrack']);
		
		$level['LEVEL_TITLE'] = sprintf(self::string('levelTitle'), $level['levelName'], self::getUsernameString($user['userName'], $iconKit['main'], $userAppearance['modBadgeLevel'], implode(' ', $userAttributes)));
		$level['LEVEL_DESCRIPTION'] = htmlspecialchars(Escape::url_base64_decode($level['levelDesc'])) ?: "<i>".self::string('noDescription')."</i>";
		$level['LEVEL_DIFFICULTY_IMAGE'] = Library::getLevelDifficultyImage($level);
		
		$level['LEVEL_LENGTH'] = $levelLengths[$level['levelLength']];
		$level['LEVEL_LIKES'] = abs($level['likes'] - $level['dislikes']);
		$level['LEVEL_IS_DISLIKED'] = $level['dislikes'] > $level['likes'] ? 'true' : 'false';
		
		if($song) $level['LEVEL_SONG'] = $song['authorName']." - ".$song['name'].(isset($song['ID']) ? " • <text dashboard-copy>".$song['ID'].'</text>' : '');
		else $level['LEVEL_SONG'] = self::string("unknownSong");
		$level['LEVEL_SONG_ID'] = $song['ID'] ?: '';
		$level['LEVEL_SONG_AUTHOR'] = $song['authorName'] ?: '';
		$level['LEVEL_SONG_TITLE'] = $song['name'] ?: '';
		$level['LEVEL_SONG_URL'] = urlencode(urldecode($song['download'])) ?: '';
		$level['LEVEL_IS_CUSTOM_SONG'] = isset($song['ID']) ? 'true' : 'false';
		
		$level['LEVEL_BUTTON_ONCLICK'] = 'getPage(\'browse/levels/'.$level['levelID'].'\')';
		
		return self::renderTemplate('components/level', $level);
	}
	
	public static function renderCommentCard($comment, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$user = Library::getUserByID($comment['userID']);
		
		$userAttributes = [];
		
		$userPerson = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		$iconKit = self::getUserIconKit($userID);
		$userAppearance = Library::getPersonCommentAppearance($userPerson);
		$userColor = str_replace(",", " ", $userAppearance['commentColor']);
		
		if($userColor != '255 255 255') $userAttributes[] = 'style="--href-color: rgb('.$userColor.'); --href-shadow-color: rgb('.$userColor.' / 38%)"';
		if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href title"';
		
		$comment['COMMENT_USER'] = self::getUsernameString($user['userName'], $iconKit['main'], $userAppearance['modBadgeLevel'], implode(' ', $userAttributes));
		$comment['COMMENT_CONTENT'] = htmlspecialchars(Escape::url_base64_decode($comment['comment']));
		
		$comment['COMMENT_SHOW_PERCENT'] = $comment['percent'] > 0 ? 'true' : 'false';
		
		$comment['COMMENT_CAN_DELETE'] = ($person['userID'] == $user['userID'] || Library::checkPermission($person, "actionDeleteComment")) ? 'true' : 'false';
			
		return self::renderTemplate('components/comment', $comment);
	}
	
	public static function renderScoreCard($score, $person, $levelIsPlatformer) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$user = Library::getUserByID($score['userID']);
		
		$userAttributes = [];
		
		$userPerson = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		$iconKit = self::getUserIconKit($userID);
		$userAppearance = Library::getPersonCommentAppearance($userPerson);
		$userColor = str_replace(",", " ", $userAppearance['commentColor']);
		
		if($userColor != '255 255 255') $userAttributes[] = 'style="--href-color: rgb('.$userColor.'); --href-shadow-color: rgb('.$userColor.' / 38%)"';
		if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href title"';
		
		$score['SCORE_USER'] = self::getUsernameString($user['userName'], $iconKit['main'], $userAppearance['modBadgeLevel'], implode(' ', $userAttributes));
		
		$score['SCORE_IS_LEADER'] = $score['SCORE_NUMBER'] < 4 ? 'true' : 'false';
		$score['SCORE_CAN_DELETE'] = ($person['accountID'] == $user['accountID'] || Library::checkPermission($person, "dashboardDeleteLeaderboards")) ? 'true' : 'false';
		
		$score['SCORE_CAN_SEE_HIDDEN'] = ($person['accountID'] == $user['accountID'] || Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		if($score['SCORE_CAN_SEE_HIDDEN'] == 'false') {
			$score['clicks'] = 'Smartest one here? :trollface:';
			if(!$levelIsPlatformer) $score['time'] = 'No time for ya!!!';
		}
		
		if(isset($score['uploadDate'])) $score['timestamp'] = $score['uploadDate'];
		
		if(isset($score['ID'])) $score['scoreID'] = $score['ID'];
			
		return self::renderTemplate('components/score', $score);
	}
	
	public static function renderSongCard($song, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$user = Library::getUserByAccountID($song['reuploadID']);
		
		$userAttributes = [];
		
		$userPerson = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		$iconKit = self::getUserIconKit($userID);
		$userAppearance = Library::getPersonCommentAppearance($userPerson);
		$userColor = str_replace(",", " ", $userAppearance['commentColor']);
		
		if($userColor != '255 255 255') $userAttributes[] = 'style="--href-color: rgb('.$userColor.'); --href-shadow-color: rgb('.$userColor.' / 38%)"';
		if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href title"';
		
		$downloadLink = urlencode(urldecode($song["download"]));
		
		$song['SONG_USER'] = self::getUsernameString($user['userName'], $iconKit['main'], $userAppearance['modBadgeLevel'], implode(' ', $userAttributes));
		
		$song['SONG_TITLE'] = sprintf(self::string('songTitle'), htmlspecialchars($song['authorName']), htmlspecialchars($song['name']));
		$song['SONG_AUTHOR'] = htmlspecialchars($song['authorName']);
		$song['SONG_NAME'] = htmlspecialchars($song['name']);
		$song['SONG_URL'] = htmlspecialchars($downloadLink);
		
		$song['SONG_CAN_CHANGE'] = ($person['userID'] == $user['userID'] || Library::checkPermission($person, "dashboardManageSongs")) ? 'true' : 'false';
			
		return self::renderTemplate('components/song', $song);
	}
}
?>