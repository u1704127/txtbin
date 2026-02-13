<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if ($username === "" || $password === "") {
        echo json_encode([
            'success' => false,
            'message' => 'Both username and password are required'
        ]);
        exit;
    }

    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM passkey_table WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username already taken. Please choose another.'
        ]);
        exit;
    }

    // Hash password and insert new user
    $hashedPassword = md5($password);
    $insertStmt = $conn->prepare("INSERT INTO passkey_table (username, passkey) VALUES (?, ?)");
    $insertStmt->bind_param("ss", $username, $hashedPassword);

    if ($insertStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully! You can now login.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
