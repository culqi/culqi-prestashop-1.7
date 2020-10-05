<?php
/**
 * Ejemplo 2
 * Como crear un charge a una tarjeta usando Culqi PHP.
 */

try {
  // Usando Composer (o puedes incluir las dependencias manualmente)
    include_once dirname(__FILE__).'/../libraries/Requests/library/Requests.php';
  Requests::register_autoloader();
  include_once dirname(__FILE__).'/../libraries/culqi-php/lib/culqi.php';

  // Configurar tu API Key y autenticación
  $SECRET_KEY = "{SECRET KEY}";
  $culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));

  // Creando Cargo a una tarjeta
  $charge = $culqi->Charges->create(
      array(
        "amount" => 1000,
        "currency_code" => "PEN",
        "email" => "test3122@culqi.com",
        "source_id" => $_POST['token']
      )
  );
  // Respuesta
  echo json_encode($charge);

} catch (Exception $e) {
  echo json_encode($e->getMessage());
}
