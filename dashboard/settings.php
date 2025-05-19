<?php
require_once __DIR__."/incl/dashboardLib.php";
require_once __DIR__."/".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/".$dbPath."incl/lib/security.php";
require_once __DIR__."/".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];

$personAppearance = Library::getPersonCommentAppearance($person);
$accountClan = Library::getAccountClan($accountID);

$dataArray = [
	'ACCOUNT_COLOR' => "color: rgb(".str_replace(",", " ", $personAppearance['commentColor']).")",
	'CLAN_NAME' => $accountClan ? $accountClan['clan'] : Dashboard::string('notInClan'),
	'CLAN_COLOR' => $accountClan ? "color: #".$accountClan['color']."" : '',
	
	'CLAN_TITLE' => $accountClan ? sprintf(Dashboard::string("clanProfile"), $accountClan['clan']) : '',
	'PROFILE_TITLE' => $person['accountID'] ? sprintf(Dashboard::string("userProfile"), $person['userName']) : '',
	
	'DASHBOARD_SETTINGS_BUTTON_ONCLICK' => "postPage('settings', 'dashboardSettingsForm')"
];

exit(Dashboard::renderPage("settings", Dashboard::string("dashboardSettingsTitle"), ".", $dataArray));
?>