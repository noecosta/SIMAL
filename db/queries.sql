/* GET CURRENT ALERTS */
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
ORDER BY al.publish_from DESC, al.id DESC /* newest first */
;

/* GET ALERTS TO EDIT (owner only) */
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
;

/* GET ALERTS TO EDIT (all) */
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
;

/* GET REGIONS FOR ALERT */
SELECT
    CONCAT(re.shortform, " - ", re.longform) AS region
FROM alert_region ar
INNER JOIN region re ON re.id = ar.region_id
WHERE ar.alert_id = ?
ORDER BY re.shortform ASC
;