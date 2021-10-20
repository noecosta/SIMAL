<?php
require_once("includes/functions.php");

// FIELDS
$alerts = [];

// GET ALERTS
$db = getDbLink();
if($db == Null) {
    printMessage("Fehler bei der Aktualisierung der Benachrichtigungen. Bitte versuchen Sie es später erneut.", MSG_ERROR);
}
else {
    $stmt = $db->prepare("
        SELECT
            al.id,
            al.title,
            al.description,
            al.informations,
            al.publish_from,
            al.publish_to,
            st.shortform AS state
        FROM alert al
        INNER JOIN alert_state st ON st.id = al.state
        WHERE al.isDeleted = 0
          AND al.publish_from <= CURRENT_DATE
          AND (al.publish_to >= CURRENT_DATE || al.publish_to IS NULL)
        ORDER BY al.publish_from DESC, al.id DESC /* newest first */"
    );
    $stmt->execute();
    $rs = $stmt->get_result();
    // clean up transaction
    $stmt->free_result();
    $stmt->close();
    if($rs->num_rows > 0) {
        while($row = $rs->fetch_assoc()) {
            $alerts[] = $row;
        }
    }
    else {
        printMessage("Keine Benachrichtigungen verfügbar.");
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
    <title>SIMAL :: Startseite</title>
    <!-- Template from: https://getbootstrap.com/docs/5.0/examples/dashboard/ -->

    <!-- Bootstrap core CSS -->
    <link href="includes/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="includes/assets/css/remixicon.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="includes/assets/css/main.css" rel="stylesheet">
</head>
<body>
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">SIMAL<small> (Simple Alert)</small></a>
    <button class="navbar-toggler position-absolute d-md-none collapsed bg-dark" type="button" data-bs-toggle="collapse"
            data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav w-100 text-end">
        <div class="nav-item text-nowrap">
            <?php
            if(isLoggedIn()) {
                echo '<a class="nav-link px-3" href="manage/logout.php">Abmelden</a>';
            } else {
                echo '<a class="nav-link px-3" href="manage/login.php">Anmelden</a>';
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
                        <a class="nav-link active" aria-current="page" href="#">
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
                                    <a class="nav-link" href="manage/user.php">
                                        <i class="ri-user-search-fill"></i> Benutzerprofil
                                    </a>
                                </li>';

                    /* additional nav entry for authors and administrators */
                    if(hasRole(ROLE_AUTHOR) || hasRole(ROLE_ADMIN)) {
                        echo '<li class="nav-item">
                                    <a class="nav-link" href="manage/alerts.php">
                                        <i class="ri-alarm-warning-line"></i> Benachrichtigungen
                                    </a>
                                </li>';
                    }

                    /* closing tag for nav entries */
                    echo '</ul>';
                } else {
                    echo '<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                                <span>Aktionen</span>
                            </h6>
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="manage/login.php">
                                        <i class="ri-user-shared-fill"></i> Anmeldung
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="manage/register.php">
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
            echo '<main class="col-md-9 ms-sm-auto col-lg-10 p-0">
                        <div class="alert alert-secondary" role="alert">
                            <b>DEBUG: </b>' . $GLOBALS['debug_msg'] . '
                        </div>
                    </main>';
        }
        if(isset($GLOBALS['error_msg']) && !empty($GLOBALS['error_msg'])) {
            echo '<main class="col-md-9 ms-sm-auto col-lg-10 p-0">
                        <div class="alert alert-danger" role="alert">
                            <b>FEHLER: </b>' . $GLOBALS['error_msg'] . '
                        </div>
                    </main>';
        } elseif(isset($GLOBALS['success_msg']) && !empty($GLOBALS['success_msg'])) {
            echo '<main class="col-md-9 ms-sm-auto col-lg-10 p-0">
                        <div class="alert alert-success" role="alert">
                            <b>INFO: </b>' . $GLOBALS['success_msg'] . '
                        </div>
                    </main>';
        }
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h2>Aktuelle Meldungen</h2>
            <div class="alerts">
                <div class="row">
                    <?php
                        // GET AND DISPLAY CURRENT ALERTS
                        foreach($alerts as $entry) {
                            // GET AFFECTED REGIONS
                            $regions = [];
                            $id = strval($entry['id']);
                            $stmt = $db->prepare("
                                SELECT
                                    CONCAT(re.shortform, \" - \", re.longform) AS region
                                FROM alert_region ar
                                INNER JOIN region re ON re.id = ar.region_id
                                WHERE ar.alert_id = ?
                                ORDER BY re.shortform ASC
                            ");
                            $stmt->bind_param("s", $id);
                            $stmt->execute();
                            $rs = $stmt->get_result();
                            if($rs->num_rows > 0) {
                                while($row = $rs->fetch_assoc()) {
                                    $regions[] = $row['region'];
                                }
                            }

                            // CHOOSE WHAT CARD LAYOUT TO DISPLAY
                            $class = "";
                            $cardTitle = "";
                            switch($entry['state']) {
                                case "INFO":
                                    $class = "bg-info";
                                    $cardTitle = "Information";
                                    break;
                                case "WARN":
                                    $class = "bg-warning";
                                    $cardTitle = "Warnung";
                                    break;
                                case "DANGER":
                                    $class = "bg-danger";
                                    $cardTitle = "Gefahr";
                                    break;
                                default:
                                    $class = "bg-info";
                                    $cardTitle = "Information";
                            }

                            // PRINT CARD
                            // header
                            echo '<div class="col-sm-3 pb-2">
                                <div class="card col-xs-12 text-white bg-dark h-100">
                                    <div class="card-header text-uppercase">
                                            <span class="' . $class . ' px-1">
                                                <i class="ri-alarm-warning-fill"></i> ' . $cardTitle . '
                                            </span>
                                    </div>
                                    <div class="card-body ' . $class .'">
                                        <h5 class="card-title">' . $entry["title"] . '</h5>
                                        <p class="card-text">' . $entry["description"] . '</p>
                                        <div class="border-top my-3"></div>
                                        <b>Betroffene Regionen:</b>
                                        <ul class="list">' . PHP_EOL;
                            // regions
                            foreach($regions as $region) {
                                echo '<li>' . $region . '</li>' . PHP_EOL;
                            }
                            echo '</ul>';
                            // additional informations
                            if(strlen($entry['informations']) > 0) {
                                echo '<div class="border-top my-3"></div>
                                    <b>Informationen an die Bevölkerung:</b><br>
                                ' . $entry['informations'] . PHP_EOL;
                            }
                            // footer
                            echo '</div>
                                    <div class="card-footer text-muted small">
                                        <i class="ri-calendar-2-line"></i> ' . date('d.m.Y', strtotime($entry["publish_from"]));
                            if($entry['publish_to'] != Null) {
                                echo ' - ' . date('d.m.Y', strtotime($entry['publish_to'])) . PHP_EOL;
                            }
                            echo '</div>
                                </div>
                            </div>' . PHP_EOL;

                            // clean up transaction
                            $stmt->free_result();
                            $stmt->close();
                        }
                    ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="includes/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
