<?php

require_once "./PDO.php";

$valid_extensions = array('jpeg', 'jpg', 'png', 'gif', 'bmp','webp','pdf'); 
$path = 'comprobantes/'; // DIRECTORIO
$img = $_FILES['file']['name'];
$tmp = $_FILES['file']['tmp_name'];
$ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));

$final_image = rand(1000,1000000).$img;

if(in_array($ext, $valid_extensions)) { 
    $path = $path.strtolower($final_image); 
    if(move_uploaded_file($tmp,$path))  {
        $urlComprobante = $path;
        $idCuota = $_POST['id_cuota'];
        $fechaPago = $_POST['fechaPago'];
        $metodo = "TRANSFERENCIA";
        $estado = "PENDIENTE";
        $ActualizarCuota = $conexion -> prepare ("UPDATE cuotas SET metodoPago_cuota = :1, estadoPago_cuota = :2, comprobante_cuota = :3, fechaPago_cuota = :4 WHERE id_cuota = :5");
        $ActualizarCuota -> bindParam(':1',$metodo);
        $ActualizarCuota -> bindParam(':2',$estado);
        $ActualizarCuota -> bindParam(':3',$path);
        $ActualizarCuota -> bindParam(':4',$fechaPago);
        $ActualizarCuota -> bindParam(':5',$idCuota);
        if(!$ActualizarCuota -> execute()){
            echo "\nPDO::errorInfo():\n";
            print_r($ActualizarCuota->errorInfo());
        }else{
            echo "OK";
        }
    }
}

?>
