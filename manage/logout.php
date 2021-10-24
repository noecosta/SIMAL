<?php
include_once('../includes/functions.php');

// CHECK IF USER IS NOT LOGGED IN
if(!isLoggedIn()) {
    showErrorPage("Sie müssen angemeldet sein um diese Funktion nutzen zu können.", "index.php");
}

$_SESSION = [];
session_destroy();
header('Location: ../index.php', true, 307);
exit(0);
?>
