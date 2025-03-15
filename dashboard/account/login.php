<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if($person['success']) exit(Dashboard::renderErrorPage(DashboardError::AlreadyLoggedIn));

if(isset($_POST['userName']) && isset($_POST['password'])) {
	$person = $sec->loginPlayer();
	if(!$person['success']) exit(Dashboard::renderToast("xmark", "Неверный пароль!", "error"));
	
	setcookie('auth', $person['auth'], 2147483647, '/');

	Library::logAction($person, Action::SuccessfulLogin);
	
	exit(Dashboard::renderToast("check", "Вы успешно вошли в аккаунт!", "success", "./"));
}

$dataArray = [
	'LOGIN_USERNAME_TEXT' => 'Никнейм',
	'LOGIN_PASSWORD_TEXT' => 'Пароль',
	'LOGIN_BUTTON_TEXT' => 'Войти в аккаунт',
	'LOGIN_BUTTON_ONCLICK' => "postPage('account/login', 'loginForm')"
];

exit(Dashboard::renderPage("account/login", "Войти в аккаунт", "../", $dataArray));
?>