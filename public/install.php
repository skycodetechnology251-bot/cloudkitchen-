<?php
// The setup wizard is part of the app at /install (it disappears once Daily Table is installed).
$dir = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/')), '/');
header('Location: '.preg_replace('#/public$#', '', $dir).'/install', true, 302);
exit;
