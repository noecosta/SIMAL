<?php
require_once('../includes/functions.php');

// FIELDS
$username = $password = '';
$isInvalid = false;

// CHECK FOR LOGIN
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sanitisedPost = sanitiseArray($_POST);
    // DEBUG OUTPUT
    // printMessage("Received a POST request with the following data:<br>" . recursive_implode($sanitisedPost));

    // CHECK FIELDS
    if(isset($sanitisedPost['username']) && strlen($sanitisedPost['username']) >= 5 && strlen($sanitisedPost['username']) <= 30 && isset($sanitisedPost['password']) && preg_match('/((?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9]).{7,})\S/', $sanitisedPost['password'])) {
        $username = $sanitisedPost['username'];
        $password = $sanitisedPost['password'];
    }
    else {
        $isInvalid = true;
        printMessage("Ungültige Benutzerdaten. Bitte erneut versuchen.", MSG_ERROR);
    }

    // CHECK FOR VALIDITY
    if(!$isInvalid) {
        // CHECK LOGIN
        $db = getDbLink();
        if($db == Null) {
            printMessage("Keine Verbindung zur Datenbank. Bitte versuchen Sie es später erneut.", MSG_ERROR);
            $isInvalid = true;
        }
        else {
            $stmt = $db->prepare('SELECT * FROM `user` WHERE username = ?');
            if($stmt) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $rs = $stmt->get_result();
                // clean up transaction
                $stmt->free_result();
                $stmt->close();
                if($rs->num_rows == 1) {
                    $result = $rs->fetch_assoc();
                    if(password_verify($password, $result['password'])) {
                        // GET ROLES
                        $roles = [];
                        $id = strval($result['id']);
                        $stmt = $db->prepare('SELECT role_id FROM `role_management` WHERE user_id = ?');
                        $stmt->bind_param('s', $id);
                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows > 0) {
                            while($row = $rs->fetch_row()) {
                                $roles[] = $row[0]; // APPEND role_id TO $roles array
                            }
                        }

                        // CREATE SESSION DATA
                        $_SESSION['id'] = $result['id'];
                        $_SESSION['username'] = $result['username'];
                        $_SESSION['firstname'] = $result['firstname'];
                        $_SESSION['lastname'] = $result['lastname'];
                        $_SESSION['loggedIn'] = true;
                        $_SESSION['roles'] = $roles;

                        // CHANGE SESSION ID
                        session_regenerate_id(true);

                        printMessage("Willkommen " . $_SESSION['firstname'] . " " . $_SESSION['lastname'] . " - Du wurdest erfolgreich angemeldet.", MSG_SUCCESS);
                    }
                    else {
                        $isInvalid = true;
                        printMessage("Ungültige Benutzerdaten. Bitte erneut versuchen.", MSG_ERROR);
                    }
                }
                else {
                    $isInvalid = true;
                    printMessage("Ungültige Benutzerdaten. Bitte erneut versuchen.", MSG_ERROR);
                }
            }
            else {
                // DEBUG OUTPUT
                // printMessage("Fehler bei der Abfrage der Benutzerdaten: <br>" . $db->error);
                printMessage("Fehler bei der Abfrage der Benutzerdaten. Bitte später erneut versuchen.", MSG_ERROR);
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
    <title>SIMAL :: Anmeldung</title>
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
                                    <a class="nav-link active" href="#">
                                        <i class="ri-user-shared-fill"></i> Anmeldung
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="register.php">
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
                    <h1 class="h3 mb-3 fw-normal">Anmeldung</h1>
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
                    <small><i class="ri-information-line"></i> Kein Benutzerprofil? Registriere dich <a
                                href="register.php">hier</a>.</small>
                </form>
            </div>
        </main>
    </div>
</div>


<script src="../includes/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
