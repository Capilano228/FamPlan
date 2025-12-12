<?php
require_once 'config.php';

if (isset($_SESSION['family_id'])) {
    $familyId = $_SESSION['family_id'];
    $sql = "SELECT * FROM events WHERE family_id = $familyId ORDER BY event_date ASC";
    $result = $conn->query($sql);
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode($events);
}
?>