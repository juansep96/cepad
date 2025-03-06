
<?php
require 'vendor/autoload.php';
require_once "./PDO.php";


//Este es un CRON que ejecutamos por si algun pago falla el webhook con Mercado Pago.

$ACCESS_TOKEN = 'APP_USR-XXXX';

$ObtenerPagosIncompletos = $conexion -> prepare ("SELECT * FROM cuotas WHERE estado_cuota=1 AND metodoPago_cuota = 'MP' AND estadoPago_cuota = 'PENDIENTE' ");
$ObtenerPagosIncompletos -> execute();

if($ObtenerPagosIncompletos->RowCount()>0){
    foreach($ObtenerPagosIncompletos as $Cuota){
        $preference_id = $Cuota['id_preference_cuota'];
        $curl = curl_init(); 
        curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.mercadopago.com/checkout/preferences/".$preference_id, 
              CURLOPT_CUSTOMREQUEST => "GET", 
              CURLOPT_RETURNTRANSFER => true, 
              CURLOPT_ENCODING => "",
              CURLOPT_TIMEOUT => 0, 
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$ACCESS_TOKEN
              ),
            ));
            $response = curl_exec($curl);
            $response = json_decode($response,true);
            $approved = $response['collection_status'];
            if($appoved=="approved"){
		$ActualizarCuota = $conexion -> prepare ("UPDATE cuotas SET fechaPago_cuota=:1,estado_cuota=:2,estadoPago_cuota=:3 WHERE id_cuota=:5");
		$ActualizarCuota -> bindParam(':1',$cuando);
		$ActualizarCuota -> bindParam(':2',2);
		$ActualizarCuota -> bindParam(':3',"PAGADA");
		$ActualizarCuota -> bindParam(':4',$datos['idCuota']);
		$ActualizarCuota -> execute();

            }
            
    }   
}






?>
