<?php
/*
	Path to main directory
	
	It needs to point to main endpoint files: https://imgur.com/a/P8LdhzY
	
	Don't change this value if you don't undestand what it means!
*/
$dbPath = '../';

require __DIR__."/../".$dbPath."config/dashboard.php";
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
		
		$IP = IP::getIP();
		
		$auth = Escape::latin($_COOKIE['auth']);
		
		if(empty($auth)) return ["success" => false, "accountID" => "0", "IP" => $IP];
		
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
			
			$_COOKIE['auth'] = '';

			Library::logAction($logPerson, Action::FailedLogin);
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
			
			$_COOKIE['auth'] = '';

			Library::logAction($logPerson, Action::FailedLogin);
			return ["success" => false, "accountID" => (string)$accountID, "IP" => $IP];
		}
		
		return ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => $userName, "IP" => $IP];
	}
	
	/*
		Render pages
	*/
	
	public static function renderTemplate($template, $pageTitle, $pageBase, $dataArray) {
		if(!file_exists(__DIR__."/templates/main.html") || !file_exists(__DIR__."/templates/".$template.".html") || !is_array($dataArray)) return false;
		
		$templatePage = file_get_contents(__DIR__."/templates/".$template.".html");
		
		foreach($dataArray AS $key => $value) {
			$templatePage = str_replace("%".$key."%", $value, $templatePage);
		}
		
		$mainPageData = [
			'PAGE_TITLE' => $pageTitle,
			'PAGE_BASE' => $pageBase,
			'STYLE_TIMESTAMP' => filemtime(__DIR__."/style.css"),
			'PAGE' => $templatePage,
			'FOOTER' => ""
		];
		
		$page = file_get_contents(__DIR__."/templates/main.html");
		
		foreach($mainPageData AS $key => $value) {
			$page = str_replace("%".$key."%", $value, $page);
		}
		
		return $page;
	}
	
	public static function renderErrorPage($error) {
		$pageTitle = "Главная • GreenCatsServer";
		$pageBase = "../";
		
		$dataArray = [
			'INFO_TITLE' => 'Произошла ошибка',
			'INFO_DESCRIPTION' => $error,
			'INFO_BUTTON' => 'Вернуться назад',
			'INFO_BUTTON_ONCLICK' => "goToPage('./')"
		];
		
		$page = self::renderTemplate("general/info", $pageTitle, $pageBase, $dataArray);
		
		return $page;
	}
}
?>