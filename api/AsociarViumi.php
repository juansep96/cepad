<?php

$idCuota = $_POST['idCuota'];

require_once "./PDO.php";

//Actualizar la cuota como pendiente y con metodo de pago Viumi.

$medioDePago = "VIUMI";
$estadoPago = "PENDIENTE";
$id = $_POST['prf_id'];

$ActualizarCuota = $conexion -> prepare ("UPDATE cuotas SET metodoPago_cuota=:1,estadoPago_cuota=:2,id_preference_cuota=:3 WHERE id_cuota=:4");
$ActualizarCuota -> bindParam(':1',$medioDePago);
$ActualizarCuota -> bindParam(':2',$estadoPago);
$ActualizarCuota -> bindParam(':3',$id);
$ActualizarCuota -> bindParam(':4',$idCuota);
$ActualizarCuota -> execute();

?>
