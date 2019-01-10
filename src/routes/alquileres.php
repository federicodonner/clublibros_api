<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get All customers
$app->get('/api/alquileres', function(Request $request, Response $response){

  $sql = "SELECT * FROM alquileres";
  try{
    // Get db object
    $db = new db();
    // Connect
    $db = $db->connect();

    $stmt = $db->query($sql);
    $alquileres = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    // Add the products array inside an object
    $alquileresResponse = array('alquileres'=>$alquileres);
    $newResponse = $response->withJson($alquileresResponse);
    return $newResponse;

  }catch(PDOException $e){
    echo '{"error":{"text": '.$e->getMessage().'}}';
  }

});

// Get single producto
$app->get('/api/alquileres/{id}', function(Request $request, Response $response){
  $id = $request->getAttribute('id');
  $sql = "SELECT * FROM alquileres WHERE id = $id";

  try{
    // Get db object
    $db = new db();
    // Connect
    $db = $db->connect();

    $stmt = $db->query($sql);
    $alquiler = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    echo json_encode($alquiler);

  }catch(PDOException $e){
    echo '{"error":{"text": '.$e->getMessage().'}}';
  }
});



// Add product
$app->post('/api/alquileres', function(Request $request, Response $response){

  $params = $request->getBody();

  if($request->getHeaders()['HTTP_AUTHORIZATION']){
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, post the products
    if(!empty($access_token)){
      $user_found = verifyToken($access_token);
      if(!empty($user_found)){

        $fecha_salida = $request->getParam('fecha_salida');
        $fecha_devolucion = $request->getParam('fecha_devolucion');
        $id_libro = $request->getParam('id_libro');
        $id_usuario = $request->getParam('id_usuario');
        $activo = $request->getParam('activo');

        $sql = "INSERT INTO alquileres(fecha_salida,fecha_devolucion,id_libro,id_usuario,activo) VALUES (:fecha_salida,:fecha_devolucion,:id_libro,:id_usuario,:activo)";

        try{
          // Get db object
          $db = new db();
          // Connect
          $db = $db->connect();

          $stmt = $db->prepare($sql);

          $stmt->bindParam(':fecha_salida', $fecha_salida);
          $stmt->bindParam(':fecha_devolucion', $fecha_devolucion);
          $stmt->bindParam(':id_libro', $id_libro);
          $stmt->bindParam(':id_usuario', $id_usuario);
          $stmt->bindParam(':activo', $activo);

          $stmt->execute();

          $newResponse = $response->withStatus(200);
          $body = $response->getBody();
          $body->write('{"status": "success","message": "Alquiler agregado"');
            $newResponse = $newResponse->withBody($body);
            return $newResponse;


          }catch(PDOException $e){
            echo '{"error":{"text": '.$e->getMessage().'}}';

          }
        }else{
          return loginError($response, (string) 'Error de login, usuario no encontrado');
        }
      }else{
        return loginError($response, (string) 'Error de login, falta access token');
      }
    }else{
      return loginError($response, (string) 'Error de encabezado HTTP');
    }
  });


  // Update product
  $app->put('/api/alquileres/{id}', function(Request $request, Response $response){

    $params = $request->getBody();
    if($request->getHeaders()['HTTP_AUTHORIZATION']){
      $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
      $access_token = explode(" ", $access_token)[1];
      // Find the access token, if a user is returned, post the products
      if(!empty($access_token)){
        $user_found = verifyToken($access_token);
        if(!empty($user_found)){

          $id = $request->getAttribute('id');

          $fecha_devolucion = $request->getParam('fecha_devolucion');


          $sql = "UPDATE alquileres SET
          fecha_devolucion = :fecha_devolucion
          WHERE id = $id";

          try{
            // Get db object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->prepare($sql);

            $stmt->bindParam(':fecha_devolucion', $fecha_devolucion);

            $stmt->execute();

            echo('{"notice":{"text":"alquiler actualizado"'.$fecha_devolucion.'}}');

          }catch(PDOException $e){
            echo '{"error":{"text": '.$e->getMessage().'}}';
          }
        }else{
          return loginError($response, (string) 'Error de login, usuario no encontrado');
        }
      }else{
        return loginError($response, (string) 'Error de login, falta access token');
      }
    }else{
      return loginError($response, (string) 'Error de encabezado HTTP');
    }
  });
