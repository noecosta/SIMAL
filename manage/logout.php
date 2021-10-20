<?php
    include_once("../includes/functions.php");
    $_SESSION = [];
    session_destroy();
    header("Location: ../index.php", true, 307);
    exit(0);
?>
