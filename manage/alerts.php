<?php
require_once('../includes/functions.php');

// CHECK PERMISSIONS TO VIEW THE PAGE
if(!(isLoggedIn() && (hasRole(ROLE_AUTHOR) || hasRole(ROLE_ADMIN)))) {
    // USER IS NOT LOGGED IN OR HAS NO RIGHTS TO VIEW THIS PAGE, REDIRECTING TO THE MAIN PAGE
    showErrorPage("Keine Berechtigung um diese Seite anzuzeigen.", 'index.php');
}

// FIELDS
$view = V_ALERTS_DEFAULT;
$alertData = [];
$alertId = null;
$data = [];
$invalidFields = [];
$isPostInvalid = false;

// CHECK FOR ACTIONS
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = sanitiseArray($_POST);
    // DEBUG OUTPUT
    // printMessage("Received a POST request with the following data:<br>" . recursive_implode($data));
}
elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    $data = sanitiseArray($_GET);
    // DEBUG OUTPUT
    // printMessage("Received a GET request with the following data:<br>" . recursive_implode($data));
}

if(isset($data['mode'])) {
    $actionSucceeded = true;

    $db = getDbLink();
    if($db == Null) {
        printMessage("Keine Verbindung zur Datenbank. Bitte versuchen Sie es später erneut.", MSG_ERROR);
        $actionSucceeded = false;
    }
    else {
        // get alert data if mode is not "add"
        if($data['mode'] != 'add') {
            if(isset($data['alert_id'])) {
                $alertId = strval($data['alert_id']);
                $stmt = $db->prepare('
                    SELECT * FROM `alert` WHERE id = ?
                ');
                $stmt->bind_param('s', $alertId);
                $stmt->execute();
                $rs = $stmt->get_result();
                // clean up transaction
                $stmt->close();

                if($rs->num_rows == 1) {
                    // save data to global array
                    $alertData = $rs->fetch_assoc();
                    // grab regions and save it to global array
                    $regions = [];
                    $stmt = $db->prepare('
                        SELECT
                            ar.region_id
                        FROM alert_region ar
                        WHERE ar.alert_id = ?
                    ');
                    $stmt->bind_param('s', $alertId);
                    $stmt->execute();
                    $rs = $stmt->get_result();
                    if($rs->num_rows > 0) {
                        while($row = $rs->fetch_assoc()) {
                            $regions[] = $row['region_id'];
                        }
                    }
                    // clean up transaction
                    $stmt->free_result();
                    $stmt->close();
                    $alertData['regions'] = $regions;
                }
                else {
                    printMessage("Keine Benachrichtigung mit dieser ID gefunden.", MSG_ERROR);
                    $actionSucceeded = false;
                }
            }
            else {
                printMessage("Ungültige Aktion.", MSG_ERROR);
                $actionSucceeded = false;
            }
        }

        // map action view and action processing (ignore if the action failed)
        if($actionSucceeded) {
            switch($data['mode']) {
                case 'add':
                    $view = V_ALERTS_ADD;
                    break;
                case 'edit':
                    if(hasRole(ROLE_ADMIN) == false) {
                        // check if it's the owner, deny request otherwise
                        if(strval($alertData['creator']) != strval($_SESSION['id'])) {
                            printMessage("Keine Berechtigung um diese Aktion auszuführen.", MSG_ERROR);
                            break;
                        }
                    }
                    $view = V_ALERTS_EDIT;
                    break;
                case 'disable':
                    if(hasRole(ROLE_ADMIN) == false) {
                        // check if it's the owner, deny request otherwise
                        if(strval($alertData['creator']) != strval($_SESSION['id'])) {
                            printMessage("Keine Berechtigung um diese Aktion auszuführen.", MSG_ERROR);
                            break;
                        }
                    }
                    if($alertData['isDeleted'] == 0) {
                        $stmt = $db->prepare('
                            UPDATE `alert` SET `isDeleted` = 1 WHERE id = ?
                        ');
                        $stmt->bind_param('s', $alertId);
                        if($stmt->execute()) {
                            printMessage("Die Benachrichtigung mit der ID \"$alertId\" wurde erfolgreich deaktiviert.", MSG_SUCCESS);
                        }
                        else {
                            printMessage("Es gab einen Fehler bei der Deaktivierung der Benachrichtigung mit der ID \"$alertId\". Bitte versuchen Sie es später erneut.", MSG_ERROR);
                        }
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                    }
                    else {
                        printMessage("Diese Benachrichtigung wurde bereits als deaktiviert markiert.", MSG_ERROR);
                    }
                    break;
                case 'reactivate':
                    if($alertData['isDeleted'] == 1) {
                        if(hasRole(ROLE_ADMIN)) {
                            $stmt = $db->prepare('
                                UPDATE `alert` SET `isDeleted` = 0 WHERE id = ?
                            ');
                            $stmt->bind_param('s', $alertId);
                            if($stmt->execute()) {
                                printMessage("Die Benachrichtigung mit der ID \"$alertId\" wurde erfolgreich reaktiviert.", MSG_SUCCESS);
                            }
                            else {
                                printMessage("Es gab einen Fehler bei der Reaktivierung der Benachrichtigung mit der ID \"$alertId\". Bitte versuchen Sie es später erneut.", MSG_ERROR);
                            }
                            // clean up transaction
                            $stmt->free_result();
                            $stmt->close();
                        }
                        else {
                            printMessage("Keine Berechtigung um diese Aktion auszuführen.", MSG_ERROR);
                        }
                    }
                    else {
                        printMessage("Diese Benachrichtigung ist bereits aktiv.", MSG_ERROR);
                    }
            }

            // CHECK FOR POSTED DATA
            if(isset($data['postMode'])) {
                switch($data['postMode']) {
                    case 'add':
                        // validate data
                        if(!isset($data['category'])) {
                            $invalidFields[] = 'category';
                            printMessage("Es wurde keine gültige Kategorie angegeben.", MSG_ERROR);
                        }
                        if(!isset($data['title']) || strlen($data['title']) < 3 || strlen($data['title']) > 30) {
                            $invalidFields[] = 'title';
                            printMessage("Es wurde kein gültiger Titel angegeben.", MSG_ERROR);
                        }
                        if(!isset($data['description']) || strlen($data['description']) < 10 || strlen($data['description']) > 350) {
                            $invalidFields[] = 'description';
                            printMessage("Es wurde keine gültige Beschreibung angegeben.", MSG_ERROR);
                        }
                        if(!isset($data['regions']) || sizeof($data['regions']) == 0) {
                            $invalidFields[] = 'regions';
                            printMessage("Es wurde(n) keine gültige(n) Region(en) angegeben.", MSG_ERROR);
                        }
                        if(isset($data['additionalinformations']) && strlen($data['additionalinformations']) > 0 && (strlen($data['additionalinformations']) < 10 || strlen($data['additionalinformations']) > 350)) {
                            $invalidFields[] = 'additionalinformations';
                            printMessage("Es wurden ungültige Zusatzinformationen angegeben.", MSG_ERROR);
                        }
                        if(isset($data['from']) && isDateValid($data['from'])) {
                            $invalidFields[] = 'from';
                            printMessage("Es wurde ein ungültiges \"Von\"-Datum angegeben.", MSG_ERROR);
                        }
                        if(isset($data['to']) && isDateValid($data['to'])) {
                            $invalidFields[] = 'to';
                            printMessage("Es wurde ein ungültiges \"Bis\"-Datum angegeben.", MSG_ERROR);
                        }

                        // set invalid flag if necessary
                        if(sizeof($invalidFields) > 0) {
                            $isPostInvalid = true;
                        }

                        // save alert if all data is valid
                        if($isPostInvalid == false) {
                            $creator = strval($_SESSION['id']);
                            $db = getDbLink();
                            if($db == Null) {
                                printMessage("Keine Verbindung zur Datenbank. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                            }
                            else {
                                // save meta data
                                $state = $data['category'] ?? null;
                                $title = $data['title'] ?? null;
                                $description = $data['description'] ?? null;
                                $informations = $data['additionalinformations'] == null || strlen($data['additionalinformations']) == 0 ? null : $data['additionalinformations'];
                                $publishFrom = $data['from'] == null || strlen($data['from']) == 0 ? date('Y-m-d') : $data['from'];
                                $publishTo = $data['to'] == null || strlen($data['to']) == 0 ? null : $data['to'];
                                $regions = $data['regions'] ?? null;
                                $stmt = $db->prepare('
                                    INSERT INTO `alert` (`id`, `state`, `title`, `description`, `informations`, `publish_from`, `publish_to`, `creator`, `isDeleted`)
                                    VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, \'0\') 
                                ');
                                $stmt->bind_param('sssssss', $state, $title, $description, $informations, $publishFrom, $publishTo, $creator);
                                if($stmt->execute()) {
                                    // save regions
                                    $createdId = $db->insert_id;
                                    // clean up transaction
                                    $stmt->free_result();
                                    $stmt->close();
                                    if($regions != null) {
                                        foreach($regions as $region) {
                                            $id = $region;
                                            $stmt = $db->prepare('
                                                INSERT INTO `alert_region` (`alert_id`, `region_id`)
                                                VALUES (?, ?) 
                                            ');
                                            $stmt->bind_param('ii', $createdId, $id);
                                            $stmt->execute();
                                            // clean up transaction
                                            $stmt->free_result();
                                            $stmt->close();
                                        }
                                    }
                                    printMessage("Die Benachrichtigung wurde erfolgreich hinzugefügt.", MSG_SUCCESS);
                                }
                                else {
                                    printMessage("Fehler bei der Abfrage: <br>" . $db->error);
                                    printMessage("Fehler beim Abspeichern der Benachrichtigung. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                                }
                            }
                        }
                        break;
                    case 'edit':
                        if(hasRole(ROLE_ADMIN) == false) {
                            // check if it's the owner, deny request otherwise
                            if(strval($alertData['creator']) != strval($_SESSION['id'])) {
                                break;
                            }
                        }
                        // validate data
                        if(!isset($data['category'])) {
                            $invalidFields[] = 'category';
                            printMessage("Es wurde keine gültige Kategorie angegeben.", MSG_ERROR);
                        }
                        if(!isset($data['title']) || strlen($data['title']) < 3 || strlen($data['title']) > 30) {
                            $invalidFields[] = 'title';
                            printMessage("Es wurde kein gültiger Titel angegeben.", MSG_ERROR);
                        }
                        if(!isset($data['description']) || strlen($data['description']) < 10 || strlen($data['description']) > 350) {
                            $invalidFields[] = 'description';
                            printMessage("Es wurde keine gültige Beschreibung angegeben.", MSG_ERROR);
                        }
                        if(!isset($data['regions']) || sizeof($data['regions']) == 0) {
                            $invalidFields[] = 'regions';
                            printMessage("Es wurde(n) keine gültige(n) Region(en) angegeben.", MSG_ERROR);
                        }
                        if(isset($data['additionalinformations']) && strlen($data['additionalinformations']) > 0 && (strlen($data['additionalinformations']) < 10 || strlen($data['additionalinformations']) > 350)) {
                            $invalidFields[] = 'additionalinformations';
                            printMessage("Es wurden ungültige Zusatzinformationen angegeben.", MSG_ERROR);
                        }
                        if(isset($data['from']) && isDateValid($data['from'])) {
                            $invalidFields[] = 'from';
                            printMessage("Es wurde ein ungültiges \"Von\"-Datum angegeben.", MSG_ERROR);
                        }
                        if(isset($data['to']) && isDateValid($data['to'])) {
                            $invalidFields[] = 'to';
                            printMessage("Es wurde ein ungültiges \"Bis\"-Datum angegeben.", MSG_ERROR);
                        }

                        // set invalid flag if necessary
                        if(sizeof($invalidFields) > 0) {
                            $isPostInvalid = true;
                        }

                        // update alert if all data is valid
                        if($isPostInvalid == false) {
                            $creator = strval($_SESSION['id']);
                            $db = getDbLink();
                            if($db == Null) {
                                printMessage("Keine Verbindung zur Datenbank. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                            }
                            else {
                                // update meta data
                                $state = $data['category'] ?? null;
                                $title = $data['title'] ?? null;
                                $description = $data['description'] ?? null;
                                $informations = $data['additionalinformations'] == null || strlen($data['additionalinformations']) == 0 ? null : $data['additionalinformations'];
                                $publishFrom = $data['from'] == null || strlen($data['from']) == 0 ? date('Y-m-d') : $data['from'];
                                $publishTo = $data['to'] == null || strlen($data['to']) == 0 ? null : $data['to'];
                                $regions = $data['regions'] ?? null;
                                $stmt = $db->prepare('
                                    UPDATE `alert` SET `state` = ?, `title` = ?, `description` = ?, `informations` = ?, `publish_from` = ?, `publish_to` = ?
                                    WHERE `id` = ?
                                ');
                                $stmt->bind_param('sssssss', $state, $title, $description, $informations, $publishFrom, $publishTo, $alertId);
                                if($stmt->execute()) {
                                    // clean up transaction
                                    $stmt->close();

                                    // clear any available regions for this alert
                                    $stmt = $db->prepare('
                                        DELETE FROM `alert_region` WHERE `alert_id` = ?
                                    ');
                                    $stmt->bind_param('s', $alertId);
                                    $stmt->execute();
                                    // clean up transaction
                                    $stmt->close();

                                    if($regions != null) {
                                        foreach($regions as $region) {
                                            $id = $region;
                                            $stmt = $db->prepare('
                                                INSERT INTO `alert_region` (`alert_id`, `region_id`)
                                                VALUES (?, ?) 
                                            ');
                                            $stmt->bind_param('si', $alertId, $id);
                                            $stmt->execute();
                                            // clean up transaction
                                            $stmt->close();
                                        }
                                    }

                                    // update alertData with the updated data
                                    $stmt = $db->prepare('
                                        SELECT * FROM `alert` WHERE id = ?
                                    ');
                                    $stmt->bind_param('s', $alertId);
                                    $stmt->execute();
                                    $rs = $stmt->get_result();
                                    // clean up transaction
                                    $stmt->close();

                                    if($rs->num_rows == 1) {
                                        // save data to global array
                                        $alertData = $rs->fetch_assoc();
                                        // grab regions and save it to global array
                                        $regions = [];
                                        $stmt = $db->prepare('
                                            SELECT
                                                ar.region_id
                                            FROM alert_region ar
                                            WHERE ar.alert_id = ?
                                        ');
                                        $stmt->bind_param('s', $alertId);
                                        $stmt->execute();
                                        $rs = $stmt->get_result();
                                        if($rs->num_rows > 0) {
                                            while($row = $rs->fetch_assoc()) {
                                                $regions[] = $row['region_id'];
                                            }
                                        }
                                        // clean up transaction
                                        $stmt->free_result();
                                        $stmt->close();
                                        $alertData['regions'] = $regions;
                                    }
                                    else {
                                        printMessage("Keine Benachrichtigung mit dieser ID gefunden.", MSG_ERROR);
                                    }
                                    printMessage("Die Benachrichtigung wurde erfolgreich aktualisiert.", MSG_SUCCESS);
                                }
                                else {
                                    printMessage("Fehler bei der Abfrage: <br>" . $db->error);
                                    printMessage("Fehler beim Aktualisieren der Benachrichtigung. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                                }
                            }
                        }
                        break;
                }
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
    <title>SIMAL :: Benachrichtigungen</title>
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
                                    <a class="nav-link active" href="alerts.php">
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

        <?php
            // decide which view to display
            switch($view) {
                case V_ALERTS_DEFAULT:
                    echo '<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="float-start">
                                        <h3>Benachrichtigungen</h3>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="float-end">
                                        <a href="alerts.php?mode=add" class="btn btn-success btn-sm justify-content-center" title="Neue Benachrichtigung erstellen"><i class="ri-add-fill"></i> Erstellen</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Titel</th>
                                            <th scope="col">Veröffentlichungszeitraum</th>
                                            <th scope="col">Ersteller</th>
                                            <th scope="col">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        ';

                    // GET ALERTS
                    $db = getDbLink();
                    if($db == Null) {
                        printMessage("Fehler bei der Abfrage der Benachrichtigungen. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                    }
                    else {
                        // CHECK ROLE
                        $stmt = Null;
                        if(hasRole(ROLE_ADMIN)) {
                            $stmt = $db->prepare('
                                SELECT
                                    al.id,
                                    al.title,
                                    al.description,
                                    al.informations,
                                    al.publish_from,
                                    al.publish_to,
                                    al.isDeleted,
                                    CONCAT(us.firstname, " ", us.lastname, " (", us.username, ")") AS creator,
                                    st.shortform AS state
                                FROM alert al
                                INNER JOIN alert_state st ON st.id = al.state
                                INNER JOIN user us ON us.id = al.creator
                                ORDER BY al.publish_from DESC, al.id DESC /* newest first */
                            ');
                        }
                        else {
                            $id = strval($_SESSION['id']);
                            $stmt = $db->prepare('
                                SELECT
                                    al.id,
                                    al.title,
                                    al.description,
                                    al.informations,
                                    al.publish_from,
                                    al.publish_to,
                                    al.isDeleted,
                                    CONCAT(us.firstname, " ", us.lastname, " (", us.username, ")") AS creator,
                                    st.shortform AS state
                                FROM alert al
                                INNER JOIN alert_state st ON st.id = al.state
                                INNER JOIN user us ON us.id = al.creator
                                WHERE al.isDeleted = 0
                                  AND al.creator = ?
                                ORDER BY al.publish_from DESC, al.id DESC /* newest first */
                            ');
                            $stmt->bind_param('s', $id);
                        }

                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows > 0) {
                            while($entry = $rs->fetch_assoc()) {
                                // FORMAT DATE
                                $date = date('d.m.Y', strtotime($entry['publish_from']));
                                if($entry['publish_to'] != Null) {
                                    $date .= ' - ' . date('d.m.Y', strtotime($entry['publish_to']));
                                }
                                // print header
                                echo '<tr>
                                        <td class="align-middle">' . $entry['id'] . '</td>
                                        <td class="align-middle">' . $entry['title'] . '</td>
                                        <td class="align-middle">' . $date . '</td>
                                        <td class="align-middle">' . $entry['creator'] . '</td>
                                        <td class="align-middle">';
                                // print actions
                                echo '<a href="alerts.php?mode=edit&amp;alert_id=' . $entry['id'] . '" class="btn btn-custom btn-secondary btn-sm mx-1" title="Bearbeiten"><i class="ri-pencil-fill"></i></a>';
                                // decide what action to display
                                if(hasRole(ROLE_ADMIN)) {
                                    if($entry['isDeleted'] == 0) {
                                        echo '<a href="alerts.php?mode=disable&amp;alert_id=' . $entry['id'] . '" class="btn btn-custom btn-danger btn-sm mx-1" title="Deaktivieren"><i class="ri-eye-off-fill"></i></a>';
                                    }
                                    else {
                                        echo '<a href="alerts.php?mode=reactivate&amp;alert_id=' . $entry['id'] . '" class="btn btn-custom btn-success btn-sm mx-1" title="Reaktivieren"><i class="ri-arrow-go-back-fill"></i></a>';
                                    }
                                }
                                else {
                                    echo '<a href="alerts.php?mode=disable&amp;alert_id=' . $entry['id'] . '" class="btn btn-custom btn-danger btn-sm mx-1" title="Deaktivieren"><i class="ri-eye-off-fill"></i></a>';
                                }
                                // print footer
                                echo '</td>
                                    </tr>';
                            }
                        }
                        else {
                            printMessage("Keine Benachrichtigungen verfügbar.");
                        }
                    }

                    // print footer
                    echo '                    </tbody>
                                </table>
                            </div>
                        </main>';
                    break;

                case V_ALERTS_ADD:
                    // print header
                    echo '<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="float-start">
                                        <h3>Benachrichtigung hinzufügen</h3>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="float-end">
                                        <a href="alerts.php" class="btn btn-success btn-sm justify-content-center" title="Zur Übersicht wechseln"><i class="ri-file-list-2-fill"></i> Zur Übersicht</a>
                                    </div>
                                </div>
                                <div class="col-sm-12 pt-2">
                                    <form method="post" target="_self">
                                      <div class="form-group cust-form-group row">
                                        <label for="category" class="col-2 col-form-label">Kategorie</label> 
                                        <div class="col-10">
                                          <select id="category" name="category" class="form-control" required>
                                            <option value="" disabled>Bitte auswählen</option>'.PHP_EOL;
                    // grab alert states from the database
                    $states = [];
                    $db = getDbLink();
                    if($db == Null) {
                        printMessage("Fehler bei der Abfrage der Kategorien. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                    }
                    else {
                        $stmt = $db->prepare('
                            SELECT
                                CONCAT(als.shortform, " - ", als.longform) AS state,
                                als.id AS id
                            FROM alert_state als                   
                            ORDER BY als.id ASC
                        ');
                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows > 0) {
                            while($row = $rs->fetch_assoc()) {
                                $states[] = ['id' => $row['id'], 'name' => $row['state']];
                            }
                        }
                    }
                    foreach($states as $state) {
                        if($isPostInvalid && !in_array('category', $invalidFields)) {
                            if($state['id'] == $data['category']) {
                                // entry has been selected before, adding selected tag
                                echo '<option value="' . $state['id'] . '" selected>' . $state['name'] . '</option>' . PHP_EOL;
                                continue;
                            }
                        }
                        echo '<option value="' . $state['id'] . '">' . $state['name'] . '</option>' . PHP_EOL;
                    }
                    $title = $description = '';
                    if($isPostInvalid && !in_array('title', $invalidFields)) {
                        $title = $data['title'];
                    }
                    if($isPostInvalid && !in_array('description', $invalidFields)) {
                        $description = $data['description'];
                    }
                    echo '</select>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="title" class="col-2 col-form-label">Titel</label> 
                                        <div class="col-10">
                                          <input id="title" value="' . $title . '" name="title" placeholder="..." type="text" class="form-control" minlength="3" maxlength="30" required>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label class="col-2 col-form-label" for="description">Beschreibung</label> 
                                        <div class="col-10">
                                          <textarea id="description" placeholder="..." name="description" cols="40" rows="5" class="form-control" minlength="10" maxlength="350" required>' . $description . '</textarea>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="regions" class="col-2 col-form-label">Betroffene Regionen</label> 
                                        <div class="col-10">
                                          <select id="regions" name="regions[]" class="form-control small-text" multiple="multiple" required>';
                    // grab regions from the database
                    $regions = [];
                    $db = getDbLink();
                    if($db == Null) {
                        printMessage("Fehler bei der Abfrage der Regionen. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                    }
                    else {
                        $stmt = $db->prepare('
                            SELECT
                                CONCAT(re.shortform, " - ", re.longform) AS region,
                                re.id AS id
                            FROM region re                   
                            ORDER BY re.shortform ASC
                        ');
                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows > 0) {
                            while($row = $rs->fetch_assoc()) {
                                $regions[] = ['id' => $row['id'], 'name' => $row['region']];
                            }
                        }
                    }
                    foreach($regions as $region) {
                        if($isPostInvalid && !in_array('regions', $invalidFields)) {
                            if(in_array($region['id'], $data['regions'])) {
                                // entry has been selected before, adding selected tag
                                echo '<option value="' . $region['id'] . '" selected>' . $region['name'] . '</option>' . PHP_EOL;
                                continue;
                            }
                        }
                        echo '<option value="' . $region['id'] . '"> ' . $region['name'] . '</option>' . PHP_EOL;
                    }

                    // print footer
                    $informations = $from = $to = '';
                    if($isPostInvalid && !in_array('additionalinformations', $invalidFields)) {
                        $informations = $data['additionalinformations'];
                    }
                    if($isPostInvalid && !in_array('from', $invalidFields)) {
                        $from = $data['from'];
                    }
                    if($isPostInvalid && !in_array('to', $invalidFields)) {
                        $to = $data['to'];
                    }
                    echo '</select>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="additionalinformations" class="col-2 col-form-label">Zusätzliche Informationen [optional]</label> 
                                        <div class="col-10">
                                          <textarea id="additionalinformations" placeholder="..." name="additionalinformations" cols="40" rows="3" minlength="10" maxlength="350" class="form-control">' . $informations . '</textarea>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="from" class="col-2 col-form-label">Veröffentlichungszeitraum [optional]</label> 
                                        <div class="col-5">
                                          <div class="input-group">
                                            <div class="input-group-prepend">
                                              <div class="input-group-text">von</div>
                                            </div>
                                            <input type="date" id="from" name="from" value="' . $from . '" min="2021-01-01" class="form-control">
                                          </div>
                                        </div>
                                        <div class="col-5">
                                          <div class="input-group">
                                            <div class="input-group-prepend">
                                              <div class="input-group-text">bis</div>
                                            </div> 
                                            <input type="date" id="to" name="to" value="' . $to . '" min="2021-01-01" class="form-control">
                                          </div>
                                        </div>
                                      </div>
                                      <input type="hidden" id="mode" name="mode" value="add">
                                      <input type="hidden" id="postMode" name="postMode" value="add">
                                      <div class="form-group row">
                                        <div class="offset-2 col-10">
                                          <button name="submit" type="submit" class="w-100 btn btn-primary">Absenden</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                            </div>
                        </main>';
                    break;
                case V_ALERTS_EDIT:
                    // print header
                    echo '<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="float-start">
                                        <h3>Benachrichtigung aktualisieren</h3>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="float-end">
                                        <a href="alerts.php" class="btn btn-success btn-sm justify-content-center" title="Zur Übersicht wechseln"><i class="ri-file-list-2-fill"></i> Zur Übersicht</a>
                                    </div>
                                </div>
                                <div class="col-sm-12 pt-2">
                                    <form method="post" target="_self">
                                      <div class="form-group cust-form-group row">
                                        <label for="category" class="col-2 col-form-label">Kategorie</label> 
                                        <div class="col-10">
                                          <select id="category" name="category" class="form-control" required>
                                            <option value="" disabled>Bitte auswählen</option>'.PHP_EOL;
                    // grab alert states from the database
                    $states = [];
                    $db = getDbLink();
                    if($db == Null) {
                        printMessage("Fehler bei der Abfrage der Kategorien. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                    }
                    else {
                        $stmt = $db->prepare('
                            SELECT
                                CONCAT(als.shortform, " - ", als.longform) AS state,
                                als.id AS id
                            FROM alert_state als                   
                            ORDER BY als.id ASC
                        ');
                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows > 0) {
                            while($row = $rs->fetch_assoc()) {
                                $states[] = ['id' => $row['id'], 'name' => $row['state']];
                            }
                        }
                    }
                    foreach($states as $state) {
                        if($state['id'] == $alertData['state']) {
                            // entry has been selected before, adding selected tag
                            echo '<option value="' . $state['id'] . '" selected>' . $state['name'] . '</option>' . PHP_EOL;
                            continue;
                        }
                        echo '<option value="' . $state['id'] . '">' . $state['name'] . '</option>' . PHP_EOL;
                    }
                    $title = $alertData['title'] ?? '';
                    $description = $alertData['description'] ?? '';
                    echo '</select>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="title" class="col-2 col-form-label">Titel</label> 
                                        <div class="col-10">
                                          <input id="title" value="' . $title . '" name="title" placeholder="..." type="text" class="form-control" minlength="3" maxlength="30" required>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label class="col-2 col-form-label" for="description">Beschreibung</label> 
                                        <div class="col-10">
                                          <textarea id="description" placeholder="..." name="description" cols="40" rows="5" class="form-control" minlength="10" maxlength="350" required>' . $description . '</textarea>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="regions" class="col-2 col-form-label">Betroffene Regionen</label> 
                                        <div class="col-10">
                                          <select id="regions" name="regions[]" class="form-control small-text" multiple="multiple" required>';
                    // grab regions from the database
                    $regions = [];
                    $db = getDbLink();
                    if($db == Null) {
                        printMessage("Fehler bei der Abfrage der Regionen. Bitte versuchen Sie es später erneut.", MSG_ERROR);
                    }
                    else {
                        $stmt = $db->prepare('
                            SELECT
                                CONCAT(re.shortform, " - ", re.longform) AS region,
                                re.id AS id
                            FROM region re                   
                            ORDER BY re.shortform ASC
                        ');
                        $stmt->execute();
                        $rs = $stmt->get_result();
                        // clean up transaction
                        $stmt->free_result();
                        $stmt->close();
                        if($rs->num_rows > 0) {
                            while($row = $rs->fetch_assoc()) {
                                $regions[] = ['id' => $row['id'], 'name' => $row['region']];
                            }
                        }
                    }
                    foreach($regions as $region) {
                        if(in_array($region['id'], $alertData['regions'])) {
                            // entry has been selected before, adding selected tag
                            echo '<option value="' . $region['id'] . '" selected>' . $region['name'] . '</option>' . PHP_EOL;
                            continue;
                        }
                        echo '<option value="' . $region['id'] . '"> ' . $region['name'] . '</option>' . PHP_EOL;
                    }

                    // print footer
                    $informations = $alertData['informations'] ?? '';
                    $from = $alertData['publish_from'] ?? '';
                    $to = $alertData['publish_to'] ?? '';
                    echo '</select>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="additionalinformations" class="col-2 col-form-label">Zusätzliche Informationen [optional]</label> 
                                        <div class="col-10">
                                          <textarea id="additionalinformations" placeholder="..." name="additionalinformations" cols="40" rows="3" minlength="10" maxlength="350" class="form-control">' . $informations . '</textarea>
                                        </div>
                                      </div>
                                      <div class="form-group cust-form-group row">
                                        <label for="from" class="col-2 col-form-label">Veröffentlichungszeitraum [optional]</label> 
                                        <div class="col-5">
                                          <div class="input-group">
                                            <div class="input-group-prepend">
                                              <div class="input-group-text">von</div>
                                            </div>
                                            <input type="date" id="from" name="from" value="' . $from . '" min="2021-01-01" class="form-control">
                                          </div>
                                        </div>
                                        <div class="col-5">
                                          <div class="input-group">
                                            <div class="input-group-prepend">
                                              <div class="input-group-text">bis</div>
                                            </div> 
                                            <input type="date" id="to" name="to" value="' . $to . '" min="2021-01-01" class="form-control">
                                          </div>
                                        </div>
                                      </div>
                                      <input type="hidden" id="mode" name="mode" value="edit">
                                      <input type="hidden" id="postMode" name="postMode" value="edit">
                                      <input type="hidden" id="alert_id" name="alert_id" value="' . $alertId . '">
                                      <div class="form-group row">
                                        <div class="offset-2 col-10">
                                          <button name="submit" type="submit" class="w-100 btn btn-primary">Absenden</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                            </div>
                        </main>';
                    break;
            }
        ?>
    </div>
</div>


<script src="../includes/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
