<?php

require 'vendor/autoload.php';
require_once "./PDO.php";

$cuotas = json_decode($_POST['idCuotas'], true); // Array de IDs de cuotas
 
$totalMonto = 0;
$titulos = [];
$idDocente = null;

// Obtener información de las cuotas
foreach ($cuotas as $idCuota) {
    $ObtenerInformacionCuota = $conexion->prepare("SELECT * FROM cuotas LEFT JOIN docentes ON idDocente_cuota = id_docente WHERE id_cuota = :idCuota");
    $ObtenerInformacionCuota->bindParam(':idCuota', $idCuota);

    if (!$ObtenerInformacionCuota->execute()) {
        echo "\nPDO::errorInfo():\n";
        print_r($ObtenerInformacionCuota->errorInfo());
        exit;
    }

    $Cuota = $ObtenerInformacionCuota->fetch(PDO::FETCH_ASSOC);
    if (!$Cuota) {
        echo json_encode(["error" => "Cuota con ID $idCuota no encontrada"]);
        exit;
    }

    if ($idDocente === null) {
        $idDocente = $Cuota['id_docente'];
    }

    $titulo = "CUOTA Nº " . $Cuota['numero_cuota'] . " - " . strtoupper($Cuota['apellido_docente']) . " " . strtoupper($Cuota['nombre_docente']);
    $titulos[] = $titulo;
    $totalMonto += floatval($Cuota['monto_cuota']);
}

$tituloFinal = implode(" | ", $titulos);
$totalMonto = number_format($totalMonto, 2, '.', '');

$mercadoPagoId = '';
$mercadoPagoUrl = '#';
$viumiId = '';
$viumiUrl = '#';

// SDK de Mercado Pago  
MercadoPago\SDK::setAccessToken('APP_USR-XXX');
$preference = new MercadoPago\Preference();
$item = new MercadoPago\Item();
$item->id = implode(",", $cuotas); // IDs de cuotas concatenados
$item->title = $tituloFinal;
$item->quantity = 1;
$item->unit_price = floatval($totalMonto);
$item->currency_id = 'ARS';

$preference->items = [$item];
$preference->back_urls = [
    "success" => "https://cdocente.com.ar/intranet/docentes/dashboard.html?id=$idDocente&payment=ok&id_cuotas=" . implode(",", $cuotas),
];
$preference->notification_url = "https://cdocente.com.ar/intranet/docentes/Recibir.php?id_cuotas=" . implode(",", $cuotas);
$preference->auto_return = "approved";
$preference->binary_mode = true;

$preference->save();


$mercadoPagoUrl = $preference->init_point;
$mercadoPagoId = $preference->id;


$viumiApiKey = "YOUR_VIUMI_API_KEY"; 
$viumiApiUrl = "https://api.viumi.com.ar/api/v1/payments";

$data = [
    "amount" => floatval($totalMonto),
    "concept" => $tituloFinal,
    "return_url" => "https://cdocente.com.ar/intranet/docentes/dashboard.html?id=$idDocente&id_cuotas=" . implode(",", $cuotas) . "&payment_viumi=ok&status={status}&operation_id={operation_id}",
    "callback_url" => "https://cdocente.com.ar/intranet/docentes/Recibir.php?id_cuotas=" . implode(",", $cuotas) . "&status={status}&operation_id={operation_id}",
];

$ch = curl_init($viumiApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $viumiApiKey,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $viumiResponse = json_decode($response, true);
    $viumiUrl = $viumiResponse['url']; 
    $viumiId = $viumiResponse['id'];  
}


$response = [
    'cuotas' => $cuotas,
    'mercadopago' => [
        'url' => $mercadoPagoUrl,
        'id' => $mercadoPagoId,
    ],
    'viumi' => [
        'url' => $viumiUrl,
        'id' => $viumiId,
    ],
];

echo json_encode($response);

?>
