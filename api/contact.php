<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
if (!$input) {
    $input = $_POST;
}

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$service = isset($input['service']) ? trim($input['service']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$budget = isset($input['budget']) ? trim($input['budget']) : '';

$errors = [];

if ($name === '') {
    $errors['name'] = 'Full Name is required';
}
if ($email === '') {
    $errors['email'] = 'Email Address is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Email Address is invalid';
}
if ($phone === '') {
    $errors['phone'] = 'Phone Number is required';
} elseif (!preg_match('/^\+?[\d\s-]{8,15}$/', $phone)) {
    $errors['phone'] = 'Phone Number is invalid';
}
if ($service === '') {
    $errors['service'] = 'Please select a service';
}
if ($message === '') {
    $errors['message'] = 'Message details are required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    require_once dirname(__DIR__) . '/includes/db.php';
    $stmt = db()->prepare(
        'INSERT INTO leads (name, email, phone, service, message, budget, status) VALUES (?,?,?,?,?,?,\'new\')'
    );
    $stmt->execute([$name, $email, $phone, $service, $message, $budget]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['form' => 'Could not save your enquiry. Please try again.']]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);
