<?php
// --- register.php ---

// Include the database configuration file
require_once 'db_config.php';

// Create an empty response array
$response = array();

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get the data from the POST request (sent from Android)
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Basic validation: check if fields are empty
    if (!empty($name) && !empty($email) && !empty($password)) {
        
        // --- Check if email already exists ---
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Email already exists
            $response['error'] = true;
            $response['message'] = 'Email already registered.';
            $stmt_check->close();
        } else {
            // Email is available, proceed with registration
            $stmt_check->close();

            // --- Hash the password for security ---
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // --- Prepare the SQL statement to prevent SQL injection ---
            $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $name, $email, $hashed_password);

            // --- Execute the statement ---
            if ($stmt_insert->execute()) {
                // User registered successfully
                $response['error'] = false;
                $response['message'] = 'User registered successfully.';
            } else {
                // Failed to register user
                $response['error'] = true;
                $response['message'] = 'Registration failed. Please try again.';
            }
            $stmt_insert->close();
        }
    } else {
        // Required fields are missing
        $response['error'] = true;
        $response['message'] = 'Required fields are missing.';
    }
} else {
    // Request method is not POST
    $response['error'] = true;
    $response['message'] = 'Invalid request method.';
}

// Close the database connection
$conn->close();

// Echo the response as JSON
echo json_encode($response);
?>