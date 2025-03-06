<?php

require_once "./PDO.php";

$datos = json_decode($_POST['datos'], true);

$cuotas = explode(",", $datos['idCuota']);

$cuando = date('Y-m-d');
$estadoFijo = 2; // Estado de las cuotas
$error = false;

try {
    $conexion->beginTransaction();

    $ActualizarCuota = $conexion->prepare(
        "UPDATE cuotas 
         SET fechaPago_cuota = :fechaPago, 
             estado_cuota = :estado, 
             estadoPago_cuota = :estadoPago, 
             payment_id_mp_cuota = :paymentId 
         WHERE id_cuota = :idCuota"
    );


    foreach ($cuotas as $idCuota) {
        $ActualizarCuota->bindParam(':fechaPago', $cuando);
        $ActualizarCuota->bindParam(':estado', $estadoFijo);
        $ActualizarCuota->bindParam(':estadoPago', $datos['estadoCuota']);
        $ActualizarCuota->bindParam(':paymentId', $datos['paymentId']);
        $ActualizarCuota->bindParam(':idCuota', $idCuota);


        if (!$ActualizarCuota->execute()) {
            $error = true;
            break; 
        }
    }

    if ($error) {
        $conexion->rollBack();
        echo json_encode(["error" => "Error al actualizar las cuotas"]);
    } else {
        $conexion->commit();
        echo json_encode(["success" => "Todas las cuotas fueron actualizadas correctamente"]);
    }
} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(["error" => "Excepción: " . $e->getMessage()]);
}

?>
