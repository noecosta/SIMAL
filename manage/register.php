<?php
require_once('../includes/functions.php');

// FIELDS
$firstname = $lastname = $mail = $username = $password = '';
$isInvalid = false;

// CHECK FOR LOGIN
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sanitisedPost = sanitiseArray($_POST);
    // DEBUG OUTPUT
    // printMessage("Received a POST request with the following data:<br>" . recursive_implode($sanitisedPost));

    // CHECK FIELDS
    if(isset($sanitisedPost['firstname']) && strlen($sanitisedPost['firstname']) >= 2 && strlen($sanitisedPost['firstname']) <= 65) {
        $firstname = $sanitisedPost['firstname'];
    }
    else {
        $isInvalid = true;
        printMessage("Es wurde kein gültiger Vorname angegeben.", MSG_ERROR);
    }
    if(isset($sanitisedPost['lastname']) && strlen($sanitisedPost['lastname']) >= 2 && strlen($sanitisedPost['lastname']) <= 65) {
        $lastname = $sanitisedPost['lastname'];
    }
    else {
        $isInvalid = true;
        printMessage("Es wurde kein gültiger Nachname angegeben.", MSG_ERROR);
    }
    if(isset($sanitisedPost['mail']) && strlen($sanitisedPost['mail']) >= 8 && strlen($sanitisedPost['mail']) <= 200) {
        $mail = $sanitisedPost['mail'];
    }
    else {
        $isInvalid = true;
        printMessage("Es wurde keine gültige Mail-Adresse angegeben.", MSG_ERROR);
    }
    if(isset($sanitisedPost['username']) && strlen($sanitisedPost['username']) >= 5 && strlen($sanitisedPost['username']) <= 30) {
        $username = $sanitisedPost['username'];
    }
    else {
        $isInvalid = true;
        printMessage("Es wurde kein gültiger Benutzername angegeben.", MSG_ERROR);
    }
    if(isset($sanitisedPost['password']) && preg_match('/((?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9]).{7,})\S/', $sanitisedPost['password'])) {
        $password = $sanitisedPost['password'];
    }
    else {
        $isInvalid = true;
        printMessage("Es wurde kein gültiges Passwort angegeben.", MSG_ERROR);
    }

    // CHECK FOR VALIDITY
    if(!$isInvalid) {
        // CREATE PASSWORD HASH
        $password = password_hash($password, PASSWORD_DEFAULT);

        // CHECK FOR A UNIQUE USERNAME
        $db = getDbLink();
        if($db == Null) {
            printMessage("Keine Verbindung zur Datenbank. Bitte versuchen Sie es später erneut.", MSG_ERROR);
            $isInvalid = true;
        }
        else {
            $stmt = $db->prepare('SELECT username FROM `user` WHERE username = ?');
            if($stmt) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $rs = $stmt->get_result();
                // clean up transaction
                $stmt->free_result();
                $stmt->close();
                if($rs->num_rows > 0) {
                    printMessage("Dieser Benutzername ist bereits vergeben.", MSG_ERROR);
                    $isInvalid = true;
                    $username = ''; // clear username
                }
                else {
                    $stmt = $db->prepare('INSERT INTO `user` VALUES (null, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssss', $firstname, $lastname, $mail, $username, $password);
                    if($stmt->execute()) {
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();

                        // GET USER ID
                        $stmt = $db->prepare('SELECT id FROM `user` WHERE username = ?');
                        $stmt->bind_param('s', $username);
                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows == 1) {
                            // ASSIGN DEFAULT ROLE TO USER
                            $id = strval($rs->fetch_assoc()['id']);
                            $stmt = $db->prepare('INSERT INTO `role_management` VALUES (3, ?)');
                            $stmt->bind_param('s', $id);
                            $stmt->execute(); // ignore return statement as we can't do much for the user if the role assignment fails.
                            // clean up transaction
                            $stmt->free_result();
                            $stmt->close();
                        }
                        printMessage("Benutzerkonto erfolgreich angelegt.", MSG_SUCCESS);

                        // CHANGE SESSION ID
                        session_regenerate_id();
                    }
                    else {
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();

                        printMessage("Fehler bei der Anlage des Benutzerkontos. Bitte erneut versuchen.", MSG_ERROR);
                    }
                }
            }
            else {
                // DEBUG OUTPUT
                // printMessage("Fehler bei der Abfrage des Benutzernamens: <br>" . $db->error);
                printMessage("Fehler bei der Anlage des Benutzerkontos. Bitte später erneut versuchen.", MSG_ERROR);
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <title>SIMAL :: Registrierung</title>
    <!-- Template from: https://getbootstrap.com/docs/5.0/examples/dashboard/ -->

    <!-- Bootstrap core CSS -->
    <link href="../includes/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../includes/assets/css/remixicon.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../includes/assets/css/main.css" rel="stylesheet">
</head>
<body>

<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../index.php">SIMAL<small> (Simple Alert)</small></a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav w-100 text-end">
        <div class="nav-item text-nowrap">
            <?php
                if(isLoggedIn()) {
                    echo '<a class="nav-link px-3" href="logout.php">Abmelden</a>';
                }
                else {
                    echo '<a class="nav-link px-3" href="login.php">Anmelden</a>';
                }
            ?>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Generelles</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="../index.php">
                            <i class="ri-login-circle-line"></i> Startseite
                        </a>
                    </li>
                </ul>

                <?php
                    if(isLoggedIn()) {
                        /* standard nav entries for logged in users */
                        echo '<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                                <span>Verwaltung</span>
                            </h6>
                            <ul class="nav flex-column mb-2">
                                <li class="nav-item">
                                    <a class="nav-link" href="user.php">
                                        <i class="ri-user-search-fill"></i> Benutzerprofil
                                    </a>
                                </li>';

                        /* additional nav entry for authors and administrators */
                        if(hasRole(ROLE_AUTHOR) || hasRole(ROLE_ADMIN)) {
                            echo '<li class="nav-item">
                                    <a class="nav-link" href="alerts.php">
                                        <i class="ri-alarm-warning-line"></i> Benachrichtigungen
                                    </a>
                                </li>';
                        }

                        /* closing tag for nav entries */
                        echo '</ul>';
                    }
                    else {
                        echo '<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                                <span>Aktionen</span>
                            </h6>
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="login.php">
                                        <i class="ri-user-shared-fill"></i> Anmeldung
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link active" href="#">
                                        <i class="ri-user-add-fill"></i> Registrierung
                                    </a>
                                </li>
                            </ul>';
                    }
                ?>
            </div>
        </nav>

        <?php
            // DISPLAY MESSAGES TO THE USER (IF AVAILABLE)
            if(isset($GLOBALS['debug_msg']) && !empty($GLOBALS['debug_msg'])) {
                echo '<div class="col-md-9 ms-sm-auto col-lg-10 p-0">
                            <div class="alert alert-secondary" role="alert">
                                <b>DEBUG: </b>' . $GLOBALS['debug_msg'] . '
                            </div>
                        </div>';
            }
            if(isset($GLOBALS['error_msg']) && !empty($GLOBALS['error_msg'])) {
                echo '<div class="col-md-9 ms-sm-auto col-lg-10 p-0">
                            <div class="alert alert-danger" role="alert">
                                <b>FEHLER: </b>' . $GLOBALS['error_msg'] . '
                            </div>
                        </div>';
            }
            elseif(isset($GLOBALS['success_msg']) && !empty($GLOBALS['success_msg'])) {
                echo '<div class="col-md-9 ms-sm-auto col-lg-10 p-0">
                            <div class="alert alert-success" role="alert">
                                <b>INFO: </b>' . $GLOBALS['success_msg'] . '
                            </div>
                        </div>';
            }
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="form-signin">
                <form method="post" target="_self">
                    <h1 class="h3 mb-3 fw-normal">Registrierung</h1>
                    <div class="form-floating">
                        <input name="firstname" type="text" class="form-control" id="fieldFirstname"
                               placeholder="Max" required minlength="2" maxlength="65"
                               title="Mindestens 2, maximal 65 Zeichen" value="<?php if($isInvalid) { echo $firstname; } ?>">
                        <label for="fieldFirstname">Vorname</label>
                    </div>
                    <div class="form-floating">
                        <input name="lastname" type="text" class="form-control" id="fieldLastname"
                               placeholder="Mustermann" required minlength="2" maxlength="65"
                               title="Mindestens 2, maximal 65 Zeichen" value="<?php if($isInvalid) { echo $lastname; } ?>">
                        <label for="fieldLastname">Nachname</label>
                    </div>
                    <div class="form-floating">
                        <input name="mail" type="email" class="form-control" id="fieldMail"
                               placeholder="max@mustermann.com" required minlength="8" maxlength="200"
                               title="Gültiges Mail-Format, mindestens 8, maximal 200 Zeichen"
                               value="<?php if($isInvalid) { echo $mail; } ?>">
                        <label for="fieldMail">E-Mail</label>
                    </div>
                    <div class="form-floating">
                        <input name="username" type="text" class="form-control" id="fieldUsername"
                               placeholder="MaxMuster" required minlength="5" maxlength="30"
                               title="Mindestens 5, maximal 30 Zeichen" value="<?php if($isInvalid) { echo $username; } ?>">
                        <label for="fieldUsername">Benutzername</label>
                    </div>
                    <div class="form-floating">
                        <input name="password" type="password" class="form-control" id="fieldPassword"
                               placeholder="*****" required minlength="8"
                               pattern="((?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9]).{7,})\S"
                               title="Mindestens 8 Zeichen, ein Grossbuchstabe, ein Kleinbuchstabe, eine Zahl">
                        <label for="fieldPassword">Passwort</label>
                    </div>
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Absenden</button>
                    <small><i class="ri-information-line"></i> Bereits ein Benutzerprofil? Melde dich <a
                                href="login.php">hier</a> an.</small>
                </form>
            </div>
        </main>
    </div>
</div>


<script src="../includes/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
