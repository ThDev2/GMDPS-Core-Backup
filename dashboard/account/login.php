<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if($person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("loginToAccountTitle"), Dashboard::string("errorAlreadyLoggedIn")));

if(isset($_POST['userName']) && isset($_POST['password'])) {
	$person = $sec->loginPlayer();
	if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorWrongLoginOrPassword"), "error"));
	
	setcookie('auth', $person['auth'], 2147483647, '/');

	Library::logAction($person, Action::SuccessfulLogin);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successLoggedIn"), "success", "./"));
}

$dataArray = ['LOGIN_BUTTON_ONCLICK' => "postPage('account/login', 'loginForm')"];

exit(Dashboard::renderPage("account/login", Dashboard::string("loginToAccountTitle"), "../", $dataArray));
?>