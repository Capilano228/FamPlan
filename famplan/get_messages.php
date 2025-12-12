<?php
require_once 'config.php';

if (isset($_SESSION['family_id'])) {
    $familyId = $_SESSION['family_id'];
    $sql = "SELECT m.*, u.username FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.family_id = $familyId 
            ORDER BY m.created_at DESC 
            LIMIT 50";
    $result = $conn->query($sql);
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode(array_reverse($messages));
}
?>