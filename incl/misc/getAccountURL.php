<?php
if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")) $https = 'https';
else $https = 'http';
echo dirname($https."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
?>
