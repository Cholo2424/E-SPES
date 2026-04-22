<?php
function req(string $method, string $url, array $payload = []): array {
    $opts = [
        'http' => [
            'method' => $method,
            'ignore_errors' => true,
            'timeout' => 20,
            'header' => "Accept: application/json\r\n",
        ],
    ];

    if ($method === 'POST') {
        $opts['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $opts['http']['content'] = http_build_query($payload);
    }

    $raw = file_get_contents($url, false, stream_context_create($opts));
    $json = is_string($raw) ? json_decode($raw, true) : null;

    return is_array($json) ? $json : ['raw' => $raw];
}

$listSummary = req('GET', 'http://127.0.0.1:8000/api/spes-management/applicants?per_page=1000&fields=summary');

$create = req('POST', 'http://127.0.0.1:8000/api/add-applicant', [
    'lastName' => 'BTNTEST',
    'firstName' => 'FLOW',
    'dob' => '2005-08-08',
    'sex' => 'Female',
    'email' => 'btntest.flow.' . time() . '@example.com',
    'contactNumber' => '09171234567',
    'collegeSchool' => 'CITY COLLEGE OF CALAMBA',
    'collegeCourse' => 'BSIT',
    'address' => 'Purok 1, District 1, Calamba City, Laguna',
]);

$id = (int)($create['data']['id'] ?? 0);
$approve = $id > 0 ? req('POST', 'http://127.0.0.1:8000/api/pending-applicants/' . $id . '/approve') : ['message' => 'create failed'];

$pendingAfter = req('GET', 'http://127.0.0.1:8000/api/spes-management/applicants?per_page=1000&fields=summary');
$management = req('GET', 'http://127.0.0.1:8000/api/spes-management/records?per_page=1000');
$deployment = req('GET', 'http://127.0.0.1:8000/api/deployment/records?per_page=1000');

$pendingIds = array_map(static fn($r) => $r['id'] ?? null, is_array($pendingAfter['data'] ?? null) ? $pendingAfter['data'] : []);
$managementApplicantIds = array_map(static fn($r) => $r['applicant_id'] ?? null, is_array($management['data'] ?? null) ? $management['data'] : []);
$deploymentApplicantIds = array_map(static fn($r) => $r['applicant_id'] ?? null, is_array($deployment['data'] ?? null) ? $deployment['data'] : []);

echo json_encode([
    'summaryListCount' => is_array($listSummary['data'] ?? null) ? count($listSummary['data']) : null,
    'createdId' => $id,
    'approveMessage' => $approve['message'] ?? null,
    'pendingHasApprovedId' => in_array($id, $pendingIds, true),
    'managementHasApprovedId' => in_array($id, $managementApplicantIds, true),
    'deploymentHasApprovedId' => in_array($id, $deploymentApplicantIds, true),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
