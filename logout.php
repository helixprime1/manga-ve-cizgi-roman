<?php
require_once 'includes/config.php';
session_start();

// Oturumu sonlandır
session_unset();
session_destroy();

// Ana sayfaya yönlendir
header('Location: index.php');
exit;
?> 