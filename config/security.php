<?php
/*
	Submissions by unregistered accounts
	
	Should unregistered accounts be able to upload levels, post comments, etc. Required for <1.9 GDPSs:
		True — unregistered accounts can interact with GDPS
		False — only registered accounts can interact with GDPS
*/
$unregisteredSubmissions = false;

/*
	Preactivate accounts
	
	Should new accounts already be registered:
		True — all new accounts are automatically registered
		False — new accounts must be activated through activate page (dashboard/login/activate.php) or email message
*/
$preactivateAccounts = true;

/*
	Debug mode

	Show errors on page if hosting supports it
		True — show errors
		False — disable errors reporting (recommended for production)
*/
$debugMode = true;

/*
	Captcha settings
	
	$enableCaptcha — should captcha be enabled:
		True — captcha is enabled, you must configure next three variables
		False — captcha is disabled
	$captchaType — captcha provider:
		1  — hCaptcha: https://www.hcaptcha.com/
		2 — reCaptcha: https://www.google.com/recaptcha/
		3 — Cloudflare Turnstile: https://www.cloudflare.com/products/turnstile/
	$CaptchaKey — public captcha key
	$CaptchaSecret — private captcha key, must not be shared with anyone
*/

$enableCaptcha = false;
$captchaType = 1;
$CaptchaKey = "";
$CaptchaSecret = "";

/*
	Block access from free proxies and common VPNs

	Below are URLs for proxies and VPSs
	Should only return list of IPs without any other HTML code

	Syntax: $proxies['NAME OF IPs'] = 'LINK';
*/

$blockFreeProxies = false; // true = check if person uses free proxy
$blockCommonVPNs = false; // true = check if person uses a common VPN
// URLs for IPs of proxies
$proxies['http'] = 'https://fhgdps.com/proxies/http.txt';
$proxies['https'] = 'https://fhgdps.com/proxies/https.txt';
$proxies['socks4'] = 'https://fhgdps.com/proxies/socks4.txt';
$proxies['socks5'] = 'https://fhgdps.com/proxies/socks5.txt';
$proxies['unknown'] = 'https://fhgdps.com/proxies/unknown.txt';
// URLs for IP ranges of VPNs
$vpns['vpn'] = 'https://raw.githubusercontent.com/X4BNet/lists_vpn/main/output/vpn/ipv4.txt';

/*
	GDPS automod config
	
	
	-- SECURITY --
	

	$rateLimitBanMultiplier — if someone reached rate limit and exceeded RATE LIMIT DELAY * Multiplier, ban their IP address
	$rateLimitBanTime — for how many seconds IP should be banned
	
	$maxLoginTries — maximum amount of login tries per hour
	
	$statsTimeCheck — time in seconds for checking stats speed
	$maxStarsPossible — maximum amount of stars user can gain before they get banned
	$maxMoonsPossible — maximum amount of moons user can gain before they get banned
	$maxUserCoinsPossible — maximum amount of user coins user can gain before they get banned
	$maxDemonsPossible — maximum amount of demons user can gain before they get banned

	
	-- ANTI-SPAM --


	$warningsPeriod — period of time in seconds, when new warnings of same type won't show to prevent warn spamming

	$levelsCountModifier — modifier to levels before count to avoid small levels increase warning
		if(Levels after > Levels before * Levels modifier) WARNING;
	$levelsDaysCheckPeriod — average levels count last X days will be used to compare to amount today
	$levelsSpamUploadDisable — for how many seconds level uploading should be automatically disabled if spamming detected
		0 — turned off

	$accountsCountModifier — modifier to accounts before count to avoid small accounts increase warning
		if(Accounts after > Accounts before * Accounts modifier) WARNING;
	$accountsDaysCheckPeriod — average levels count last X days will be used to compare to amount today
	$accountsSpamUploadDisable — for how many seconds account registering should be automatically disabled if spamming detected
		0 — turned off

	$commentsCheckPeriod — comments posted in this period of time in seconds will be checked
		600 is 10 minutes, so comments posted in last 10 minutes would be checked
	$commentsSpamUploadDisable — for how many seconds commenting should be automatically disabled if spamming detected
		0 — turned off

	$globalLevelsUploadDelay — if last level was uploaded X seconds ago, new one can't be uploaded
		0 — turned off
	$perUserLevelsUploadDelay — if last level by some user was uploaded X seconds ago, new one can't be uploaded
		0 — turned off
	$accountsRegisterDelay — if last account was registered X seconds ago, new one can't be registered
		0 — turned off
	$usersCreateDelay — if last user was created X seconds ago, new one can't be created
		0 — turned off
	
	$filterTimeCheck — if person tried to post filtered word, warnings of how many seconds old should be checked
	$filterRateLimitBan — how many filter warnings will get person banned from commenting?
		
	-- CONTENT FILTERS -- 
	
	
	Filter will disallow content, if it has banned word in it
	Whitelist will disallow content, if it has banned word, but doesn't have whitelisted word in it
	
	$filterUsernames — method of filtering usernames:
		0 — disabled
		1 — checks if username is the word
		2 — checks if username contains the word
	$bannedUsernames — list of banned words in usernames
	$whitelistedUsernames — list of whitelisted words in usernames
	
	$filterClanNames — method of filtering clan names:
		0 — disabled
		1 — checks if clan name is the word
		2 — checks if clan name contains the word
	$bannedClanNames — list of banned words in clan names
	$whitelistedClanNames — list of whitelisted words in clan names
	
	$filterClanTags — method of filtering clan tags:
		0 — disabled
		1 — checks if clan tag is the word
		2 — checks if clan tag contains the word
	$bannedClanTags — list of banned words in clan tags
	$whitelistedClanTags — list of whitelisted words in clan tags
	
	$filterCommon — method of filtering common things (level names, descriptions, comments):
		0 — disabled
		1 — checks if common thing is the word
		2 — checks if common thing contains the word
	$bannedCommon — list of banned words in common things
	$whitelistedCommon — list of whitelisted words in common things
*/

$rateLimitBanMultiplier = 2;
$rateLimitBanTime = 3600;

$maxLoginTries = 4;

$statsTimeCheck = 600;
$maxStarsPossible = 150;
$maxMoonsPossible = 150;
$maxUserCoinsPossible = 80;
$maxDemonsPossible = 30;

$warningsPeriod = 302400;

$levelsCountModifier = 1.3;
$levelsDaysCheckPeriod = 7;
$levelsSpamUploadDisable = 1200;

$accountsCountModifier = 1.3;
$accountsDaysCheckPeriod = 7;
$accountsSpamUploadDisable = 1200;

$commentsCheckPeriod = 600;
$commentsSpamUploadDisable = 600;

$globalLevelsUploadDelay = 2;
$perUserLevelsUploadDelay = 5;
$accountsRegisterDelay = 5;
$usersCreateDelay = 10;

$filterTimeCheck = 60;
$filterRateLimitBan = 10;

$filterUsernames = 2;
$bannedUsernames = [
	'RobTop',
	'nig',
	'fag'
];
$whitelistedUsernames = [
	'night'
];

$filterClanNames = 2;
$bannedClanNames = [
	'Support',
	'Administration',
	'Moderation',
	'nig',
	'fag'
];
$whitelistedClanNames = [
	'night'
];

$filterClanTags = 2;
$bannedClanTags = [
	'ADM',
	'MOD',
	'nig',
	'fag'
];
$whitelistedClanTags = [
	'night'
];

$filterCommon = 2;
$bannedCommon = [
	'nig',
	'fag'
];
$whitelistedCommon = [
	'night'
];
?>
