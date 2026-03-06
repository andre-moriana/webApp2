<?php
header('Content-Type: application/json');

$signed_request = $_POST['signed_request'];
$data = parse_signed_request($signed_request);
$user_id = $data['user_id'];

// Start data deletion
$confirmation_code = bin2hex(random_bytes(8)); // unique code for the deletion request
$status_url = 'https://www.arctraining.fr/deletion-status?code=' . $confirmation_code; // URL to track the deletion


$data = array(
  'url' => $status_url,
  'confirmation_code' => $confirmation_code
);
echo json_encode($data);

function parse_signed_request($signed_request) {
  list($encoded_sig, $payload) = explode('.', $signed_request, 2);

  $secret = "appsecret"; // Use your app secret here

  // decode the data
  $sig = base64_url_decode($encoded_sig);
  $data = json_decode(base64_url_decode($payload), true);

  // confirm the signature
  $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
  if ($sig !== $expected_sig) {
    error_log('Bad Signed JSON signature!');
    return null;
  }

  return $data;
}

function base64_url_decode($input) {
  return base64_decode(strtr($input, '-_', '+/'));
}
?>