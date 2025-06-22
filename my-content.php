<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-content.php');
    exit;
}

// User panel'e yönlendir
header('Location: user-panel/content.php');
exit;
?> 