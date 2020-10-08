<?php
header('Content-Type: application/json; charset=utf-8');

require_once('../class/Api.php');

$methodRequested = strtoupper($_SERVER['REQUEST_METHOD']);
$body = json_decode(file_get_contents('php://input'), 1) ?? [];
$header = getallheaders() ?? [];

$uri = array_filter(
    explode('/', $_SERVER['REQUEST_URI']),
    function ($elem) {
        return !empty($elem);
    }
);

$api = new Api($methodRequested, $body, $header);

switch ($methodRequested) {
    case 'POST':
        $api->drinkWater();
        break;
    default:
        # code...
        break;
}
