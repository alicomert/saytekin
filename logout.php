<?php
require_once 'includes/config.php';

// Session'i temizle
session_unset();
session_destroy();

// Login sayfasına yönlendir
header('Location: login.php');
exit;
