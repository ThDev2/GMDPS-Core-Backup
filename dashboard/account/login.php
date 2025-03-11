<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";

$person = Dashboard::loginDashboardUser();
// For testing
if(!$person['success']) exit(Dashboard::renderErrorPage(DashboardError::AlreadyLoggedIn));
?>