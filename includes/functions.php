<?php
session_start();
require_once('constants.php');
mysqli_report(MYSQLI_REPORT_STRICT);

// GLOBALS
$dbLink = Null;

// SUPERGLOBALS
$GLOBALS['error_msg'] = "";
$GLOBALS['debug_msg'] = "";
$GLOBALS['success_msg'] = "";

// singleton database link
function getDbLink(): ?mysqli { // ? allows the function to return null instead of the configured type
    global $dbLink;

    if($dbLink == Null) {
        try {
            $dbLink = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $dbLink->set_charset('utf8mb4');
            if($dbLink->connect_error) {
                // overwrite link with null if there was an error with the connection
                $dbLink = Null;
            }
        } catch(Exception $ignored) {
        };
    }
    return $dbLink;
}

function printMessage($msg, $type = MSG_DEBUG) {
    if($type == MSG_DEBUG) {
        // add a spacer if there's already something in it.
        if(!empty($GLOBALS['debug_msg'])) {
            $GLOBALS['debug_msg'] .= '<br><b>DEBUG: </b>';
        }
        // add message to superglobal string
        $GLOBALS['debug_msg'] .= $msg;
    } elseif($type == MSG_SUCCESS) {
        // add a spacer if there's already something in it.
        if(!empty($GLOBALS['success_msg'])) {
            $GLOBALS['success_msg'] .= '<br><b>INFO: </b>';
        }
        // add message to superglobal string
        $GLOBALS['success_msg'] .= $msg;
    } elseif($type == MSG_ERROR) {
        // add a spacer if there's already something in it.
        if(!empty($GLOBALS['error_msg'])) {
            $GLOBALS['error_msg'] .= '<br><b>FEHLER: </b>';
        }
        // add message to superglobal string
        $GLOBALS['error_msg'] .= $msg;
    }
}

function isLoggedIn(): bool {
    if(isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']) {
        return true;
    }
    return false;
}

function hasRole($role): bool {
    if(isset($_SESSION['roles']) && in_array($role, $_SESSION['roles'])) {
        return true;
    }
    return false;
}

function hasRoles(...$roles): bool {
    if(isset($_SESSION['roles'])) {
        $roleList = $_SESSION['roles'];
        foreach($roles as $role) {
            if(!in_array($role, $roleList)) {
                // if the current role (id) is not found in the assigned roles, return false and exit the function
                return false;
            }
        }
        // return true as all of the passed roles (ids) seem to be assigned to the user
        return true;
    }
    // return false if no roles (ids) were saved into the session
    return false;
}

/* FROM: https://gist.github.com/jimmygle/2564610 */
function recursive_implode(array $array, $glue = ',', $include_keys = false, $trim_all = false) {
    $glued_string = '';

    // Recursively iterates array and adds key/value to glued string
    array_walk_recursive($array, function($value, $key) use ($glue, $include_keys, &$glued_string) {
        $include_keys and $glued_string .= $key . $glue;
        $glued_string .= $value . $glue;
    });

    // Removes last $glue from string
    strlen($glue) > 0 and $glued_string = substr($glued_string, 0, -strlen($glue));

    // Trim ALL whitespace
    $trim_all and $glued_string = preg_replace('/(\s)/ixsm', '', $glued_string);

    return (string)$glued_string;
}

function sanitiseArray($arr): array {
    return array_map('sanitise', $arr);
}

function sanitise($str) {
    // recursive call if the passed parameter is an array
    if(is_array($str)) {
        return array_map('sanitise', $str);
    } else {
        $strFinalized = trim($str);
        if(strlen($strFinalized) > 0) {
            return htmlspecialchars($strFinalized);
        }
        return $strFinalized;
    }
}

function isDateValid($date): bool {
    $date = date_create_from_format('Y-m-d', $date);
    if($date == false || date_get_last_errors() != false) {
        return false;
    }
    return true;
}

function showErrorPage($msg, $redirect, $title="Es ist ein Fehler aufgetreten.", $automated=true): void {
    ob_start();
    ob_get_clean();
    $url = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '/simal/' . $redirect;
    echo '
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <title>SIMAL :: ' . $title . '</title>
    <!-- Template from: https://getbootstrap.com/docs/5.0/examples/dashboard/ -->

    <!-- Bootstrap core CSS -->
    <link href="/simal/includes/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/simal/includes/assets/css/remixicon.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/simal/includes/assets/css/main.css" rel="stylesheet">
</head>
<body class="bg-dark">
    <div class="d-inline-flex w-100 justify-content-center align-items-center vh-100">
        <h1 class="me-3 pe-3 text-white align-top border-end align-content-center">SIMAL</h1>
        <div class="text-white align-middle">
            <h2 class="fw-bold lead" id="desc">' . $title . '</h2>
            <h2 class="fw-light lead" id="desc">' . $msg . '</h2>
            <div class="align-middle small text-muted">
';
    if($automated) {
        echo '        Sie werden in <span id="cd"> </span> Sekunden zu <a href="' . $url . '">' . $url . '</a> weitergeleitet.
    </div>
    </div>
    </div>

    <script type="text/javascript">
    let sec = 10;
    let fnc = function () {
        sec = sec - 1;
        if(sec <= 0) {
            window.location.href = "' . $url . '";
        }
        else {
            document.getElementById("cd").innerHTML = sec;
            window.setTimeout("fnc()", 1000);
        }
    };
    fnc();
    </script>
';
    }
    else {
        echo '      Zu <a href="' . $url . '">' . $url . '</a> zurückkehren.
    </div>
    </div>
    </div>
';
    }
echo '
    <script src="/simal/includes/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
';
    exit(1); // preemptive exit
}

?>