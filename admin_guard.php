<?php
session_start();

if (empty($_SESSION['is_admin'])) {
    header("Location: admin_login.php");
    exit;
}