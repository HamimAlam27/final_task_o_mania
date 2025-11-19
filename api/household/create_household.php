<!-- <?php
// require "../../src/config/db.php";
// require "../../src/config/response.php";

// $userId = $_POST['user_id'];
// $name = $_POST['household_name'];

// $stmt = $conn->prepare("INSERT INTO HOUSEHOLD (HOUSEHOLD_NAME, ID_USER) VALUES (?, ?)");
// $stmt->bind_param("si", $name, $userId);

// if ($stmt->execute()) {

//     $household_id = $conn->insert_id;

//     $stmt2 = $conn->prepare("INSERT INTO HOUSEHOLD_MEMBER (ID_USER, ID_HOUSEHOLD, ROLE) VALUES (?, ?, 'admin')");
//     $stmt2->bind_param("ii", $userId, $household_id);
//     $stmt2->execute();

//     jsonResponse("success", "Household created", ["id_household" => $household_id]);
// }

// jsonResponse("error", "Failed to create household");
?> -->


<?php
session_start();
require "../src/config/db.php";

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../sign-in.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $household_name = $_POST['household_name'] ?? '';
    $household_type = $_POST['household_type'] ?? '';

    if (empty($household_name)) {
        die("Household name is required");
    }

    // Generate a unique invite link
    $invite_link = bin2hex(random_bytes(16)); // simple unique token, can use more complex

    // Insert into HOUSEHOLD
    $stmt = $conn->prepare("INSERT INTO HOUSEHOLD (HOUSEHOLD_NAME, INVITE_LINK) VALUES (?, ?)");
    $stmt->bind_param("ss", $household_name, $invite_link);

    if ($stmt->execute()) {
        $household_id = $stmt->insert_id;

        // Insert into HOUSEHOLD_MEMBER as owner
        $stmt2 = $conn->prepare("INSERT INTO HOUSEHOLD_MEMBER (ID_USER, ID_HOUSEHOLD, ROLE) VALUES (?, ?, 'admin')");
        $stmt2->bind_param("ii", $user_id, $household_id);
        $stmt2->execute();

        // Redirect to choose_household page
        header("Location: ../households.php");
        exit;
    } else {
        die("Error creating household: " . $stmt->error);
    }
} else {
    die("Invalid request method");
}
?>
