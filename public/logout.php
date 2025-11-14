<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
logout_user();
header('Location: login.php');
exit;
