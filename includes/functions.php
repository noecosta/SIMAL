<?php
session_start();
require_once("constants.php");
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
            $dbLink->set_charset("utf8mb4");
            if($dbLink->connect_error) {
                // overwrite link with null if there was an error with the connection
                $dbLink = Null;
            }
        } catch(Exception $ignored) {};
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
    }
    elseif($type == MSG_SUCCESS) {
        // add a spacer if there's already something in it.
        if(!empty($GLOBALS['success_msg'])) {
            $GLOBALS['success_msg'] .= '<br><b>INFO: </b>';
        }
        // add message to superglobal string
        $GLOBALS['success_msg'] .= $msg;
    }
    elseif($type == MSG_ERROR) {
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

function hasRoles(... $roles): bool {
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
        $include_keys and $glued_string .= $key.$glue;
        $glued_string .= $value.$glue;
    });

    // Removes last $glue from string
    strlen($glue) > 0 and $glued_string = substr($glued_string, 0, -strlen($glue));

    // Trim ALL whitespace
    $trim_all and $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);

    return (string) $glued_string;
}

function sanitise($str) {
    // recursive call if the passed parameter is an array
    if(is_array($str)) {
        return array_map('sanitise', $str);
    }
    else {
        $strFinalized = trim($str);
        if(strlen($strFinalized) > 0) {
            return htmlspecialchars($strFinalized);
        }
        return $strFinalized;
    }
}

function sanitiseArray($arr): array {
    return array_map('sanitise', $arr);
}

function isDateValid($date): bool {
    $date = date_create_from_format("Y-m-d", $date);
    if($date == false || date_get_last_errors() != false) {
        return false;
    }
    return true;
}

?>