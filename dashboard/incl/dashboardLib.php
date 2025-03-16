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
		Accounts-related functions
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
	
	/*
		Render pages
	*/
	
	public static function renderTemplate($template, $pageTitle, $pageBase, $dataArray) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		
		$person = self::loginDashboardUser();
		
		if(!file_exists(__DIR__."/templates/main.html") || !file_exists(__DIR__."/templates/".$template.".html") || !is_array($dataArray)) return false;
		
		$templatePage = file_get_contents(__DIR__."/templates/".$template.".html");
		
		if(!empty($dataArray)) foreach($dataArray AS $key => $value) $templatePage = str_replace("%".$key."%", $value, $templatePage);
		
		$mainPageData = [
			'PAGE_TITLE' => $pageTitle,
			'PAGE_BASE' => $pageBase,
			'DASHBOARD_FAVICON' => $dashboardFavicon,
			'DATABASE_PATH' => $dbPath,
			'FAILED_TO_LOAD_TEXT' => "<i class='fa-solid fa-xmark'></i>".self::string("errorFailedToLoadPage"),
			'STYLE_TIMESTAMP' => filemtime(__DIR__."/style.css"),
			
			'IS_LOGGED_IN' => $person['success'] ? 'true' : 'false',
			'USERNAME' => $person['success'] ? $person['userName'] : '',
			'PROFILE_ICON' => $person['success'] ? 'https://icons.gcs.icu/icon.png?type=cube&value=379&color1=0&color2=3' : '',
			
			'PAGE' => $templatePage,
			'FOOTER' => ""
		];
		
		$personPermissions = Library::getPersonPermissions($person);
		foreach($personPermissions AS $permission => $value) $mainPageData['PERMISSION_'.$permission] = $value ? 'true' : 'false';
		
		$allStrings = self::allStrings();
		foreach($allStrings AS $string => $value) $mainPageData['TEXT_'.$string] = $value;
		
		$page = file_get_contents(__DIR__."/templates/main.html");
		
		foreach($mainPageData AS $key => $value) $page = str_replace("%".$key."%", $value, $page);
		
		// Debug line, report if i forget to remove it in release lol
		echo '<script>console.log('.json_encode($mainPageData, true).');</script>';
		
		return $page;
	}
	
	public static function renderErrorPage($error) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		$pageTitle = $error." • ".$gdps;
		$pageBase = "../";
		
		$dataArray = [
			'INFO_TITLE' => self::string("errorTitle"),
			'INFO_DESCRIPTION' => $error,
			'INFO_BUTTON_TEXT' => self::string("home"),
			'INFO_BUTTON_ONCLICK' => "getPage('')"
		];
		
		$page = self::renderTemplate("general/info", $pageTitle, $pageBase, $dataArray);
		
		return $page;
	}
	
	public static function renderPage($template, $title, $base, $dataArray) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		$pageTitle = $title." • ".$gdps;
		$pageBase = $base;
		
		$page = self::renderTemplate($template, $pageTitle, $pageBase, $dataArray);
		
		return $page;
	}
	
	public static function renderToast($icon, $text, $state, $location = '') {
		return "<div id='toast' state='".$state."' location='".$location."'><i class='fa-solid fa-".$icon."'></i>".$text."</div>";
	}
	
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
}
?>