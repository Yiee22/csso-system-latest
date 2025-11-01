<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username']) || !in_array($_SESSION['usertype'], ['Secretary', 'Treasurer', 'Auditor', 'Social Manager', 'Senator', 'Governor', 'Vice Governor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get POST data
$timeType = $_POST['timeType'] ?? '';
$eventName = $_POST['eventName'] ?? '';
$eventDate = $_POST['eventDate'] ?? '';
$location = $_POST['location'] ?? '';
$studentsJson = $_POST['students'] ?? '[]';
$students = json_decode($studentsJson, true);

// Validate input
if (empty($timeType) || empty($eventName) || empty($students)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Valid time types
$validTimeTypes = ['amLogin', 'amLogout', 'pmLogin', 'pmLogout'];
if (!in_array($timeType, $validTimeTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time type']);
    exit();
}

$conn->begin_transaction();

try {
    $successCount = 0;
    $currentTime = date('H:i:s');
    
    foreach ($students as $studentId) {
        // Check if attendance record exists for this student and event
        $checkStmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE students_id = ? AND event_name = ? AND event_date = ?");
        $checkStmt->bind_param("sss", $studentId, $eventName, $eventDate);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $row = $result->fetch_assoc();
            $attendanceId = $row['attendance_id'];
            
            $updateStmt = $conn->prepare("UPDATE attendance SET $timeType = ? WHERE attendance_id = ?");
            $updateStmt->bind_param("si", $currentTime, $attendanceId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Insert new record
            $insertStmt = $conn->prepare("INSERT INTO attendance (students_id, event_name, event_date, location, $timeType) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssss", $studentId, $eventName, $eventDate, $location, $currentTime);
            $insertStmt->execute();
            $insertStmt->close();
        }
        
        $checkStmt->close();
        $successCount++;
    }
    
    $conn->commit();
    
    $timeLabels = [
        'amLogin' => 'AM Login',
        'amLogout' => 'AM Logout',
        'pmLogin' => 'PM Login',
        'pmLogout' => 'PM Logout'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => "{$timeLabels[$timeType]} recorded for $successCount student(s)"
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error recording attendance: ' . $e->getMessage()
    ]);
}

$conn->close();
?>