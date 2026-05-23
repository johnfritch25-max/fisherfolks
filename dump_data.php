<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT f.fisherfolk_id, f.first_name, f.last_name, f.livelihood, f.status, b.barangay_name 
                         FROM fisherfolk f 
                         LEFT JOIN barangay b ON f.barangay_id = b.barangay_id 
                         LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "--- Fisherfolk Registry (First 10 Records) ---\n";
    if (empty($records)) {
        echo "No records found.\n";
    } else {
        foreach ($records as $r) {
            printf("[%d] %s %s - %s (%s) - Status: %s\n", 
                $r['fisherfolk_id'], $r['first_name'], $r['last_name'], 
                $r['livelihood'], $r['barangay_name'], $r['status']);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
