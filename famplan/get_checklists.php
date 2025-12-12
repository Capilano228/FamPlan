<?php
require_once 'config.php';

if (isset($_SESSION['family_id'])) {
    $familyId = $_SESSION['family_id'];
    $sql = "SELECT e.id as event_id, e.title as event_title, 
            c.id, c.item, c.is_checked 
            FROM events e 
            LEFT JOIN checklists c ON e.id = c.event_id 
            WHERE e.family_id = $familyId 
            ORDER BY e.event_date, c.created_at";
    $result = $conn->query($sql);
    
    $checklists = [];
    while ($row = $result->fetch_assoc()) {
        $checklists[$row['event_id']]['event_title'] = $row['event_title'];
        $checklists[$row['event_id']]['items'][] = [
            'id' => $row['id'],
            'item' => $row['item'],
            'is_checked' => $row['is_checked']
        ];
    }
    
    echo json_encode(array_values($checklists));
}
?>