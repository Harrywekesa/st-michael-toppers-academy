<?php
session_start();
session_destroy();
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];$error = '';
header("Location:{$base}/index.php");
exit();
?>