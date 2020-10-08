<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../class/Api.php';

$header = getallheaders() ?? [];
$body = json_decode(file_get_contents('php://input'), 1) ?? [];
$methodRequested = strtoupper($_SERVER['REQUEST_METHOD']);

if (isset($_GET['iduser'])) {
    $body['id_param_url'] = $_GET['iduser'];
}

$api = new Api($methodRequested, $body, $header);

switch ($methodRequested) {
    case 'GET':
        $api->getOrListUser();
        break;
    case 'POST':
        $api->createUser();
        break;
    case 'PUT':
        $api->updateUser();
        break;
    case 'DELETE':
        $api->deleteUser();
        break;
    default:
        echo json_encode(
            array("message" => "Endpoint not found")
        );
        break;
}


