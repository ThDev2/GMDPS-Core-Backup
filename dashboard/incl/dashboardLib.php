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
		
		$userLanguage = Escape::latin_no_spaces($_COOKIE['lang'], 2);
		if(!file_exists(__DIR__."/langs/".$userLanguage.".php")) $userLanguage = 'EN';
		
		if($userLanguage != 'EN') require __DIR__."/langs/EN.php";
		require __DIR__."/langs/".$userLanguage.".php";
		
		$GLOBALS['core_cache']['dashboard']['language'] = $language;
		
		return $language;
	}
	
	/*
		Render pages
	*/
	
	public static function renderPage($template, $pageTitle, $pageBase, $dataArray) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		
		$person = self::loginDashboardUser();
		$userID = $person['userID'];
		
		if(!file_exists(__DIR__."/templates/main.html") || !file_exists(__DIR__."/templates/".$template.".html") || !is_array($dataArray)) return false;
		
		$templatePage = self::renderTemplate($template, $dataArray);
		
		$iconKit = self::getUserIconKit($userID);
		
		$mainPageData = [
			'PAGE_TITLE' => $pageTitle,
			'GDPS_NAME' => $gdps,
			'PAGE_BASE' => $pageBase,
			'DASHBOARD_FAVICON' => $dashboardFavicon,
			'DATABASE_PATH' => $dbPath,
			'STYLE_TIMESTAMP' => filemtime(__DIR__."/style.css"),
			
			'FAILED_TO_LOAD_TEXT' => "<i class='fa-solid fa-xmark'></i>".self::string("errorFailedToLoadPage"),
			'COPIED_TEXT' => "<i class='fa-solid fa-copy'></i>".self::string("successCopiedText"),
			
			'IS_LOGGED_IN' => $person['success'] ? 'true' : 'false',
			'USERNAME' => $person['success'] ? $person['userName'] : '',
			'PROFILE_ICON' => $person['success'] ? $iconKit['main'] : '',
			
			'PAGE' => $templatePage,
			'FOOTER' => ""
		];
		
		$personPermissions = Library::getPersonPermissions($person);
		foreach($personPermissions AS $permission => $value) $mainPageData['PERMISSION_'.$permission] = $value ? 'true' : 'false';
		
		$allStrings = self::allStrings();
		foreach($allStrings AS $string => $value) $mainPageData['TEXT_'.$string] = $value;
		
		$page = self::renderTemplate('main', $mainPageData);
		
		// Debug line, report if i forget to remove it in release lol
		echo '<script>console.log('.json_encode($mainPageData, true).');</script>';
		
		return $page;
	}
	
	public static function renderErrorPage($pageTitle, $error) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		$pageBase = "../";
		
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
	
	public static function getUsernameString($userName, $mainIcon, $attributes = '') {
		return sprintf('<text class="username" title="'.sprintf(self::string('userProfile'), $userName).'" %3$s href="profile/%1$s">%1$s<img src="%2$s"></img></text>', $userName, $mainIcon, $attributes);
	}
}
?>