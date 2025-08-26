<?php
$url = "https://webapi.resales-online.com/V6/Search?lang=es&page=1&pagesize=6&P_agency_filterid=4&p1=1035049&p2=5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918";
$options = [
    "http" => [
        "header" => "User-Agent: ResalesAPIPlugin/1.0\r\n"
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);
if ($response === FALSE) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo conectar a la API o la API devolvió un error."]);
} else {
    header('Content-Type: application/json');
    echo $response;
}
?>
