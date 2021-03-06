<?php
header('Content-Type: application/json; charset=utf-8');

require_once('../class/Api.php');

$header = getallheaders() ?? [];
$body = json_decode(file_get_contents('php://input'), 1) ?? [];
$methodRequested = strtoupper($_SERVER['REQUEST_METHOD']);

if (isset($_GET['iduser'])) {
    $body['id_param_url'] = $_GET['iduser'];
}

$api = new Api($methodRequested, $body, $header);

switch ($methodRequested) {
    case 'POST':
        $api->login();
        break;
    default:
        echo json_encode(
            array("message" => "Endpoint not found")
        );
        break;
}