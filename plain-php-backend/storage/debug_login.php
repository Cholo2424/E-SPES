<?php
$ctx = stream_context_create([
  'http' => [
    'method' => 'POST',
    'ignore_errors' => true,
    'timeout' => 20,
    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
    'content' => json_encode(['email'=>'admin@ccc.edu.ph','password'=>'Admin@12345'])
  ]
]);
$raw = @file_get_contents('http://127.0.0.1:8000/api/login', false, $ctx);
if ($raw === false) { echo "REQUEST_FAILED\n"; exit(1);} 
echo $raw;
