<?php
$host = "localhost";
$user = "ferosite_ferosite_paste_url";
$pass = "aV$?RU#JJa1ME]I#";
$db   = "ferosite_paste_url";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed");
}
?>
