<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get All customers
$app->get('/api/usuarios', function(Request $request, Response $response){
  // // $params = $app->request()->getBody();
  // if($request->getHeaders()['HTTP_AUTHORIZATION']){
  // $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
  // $access_token = explode(" ", $access_token)[1];
  // Find the access token, if a user is returned, find the productos
  // if(!empty($access_token)){
  // $user_found = verifyToken($access_token);
  // if(!empty($user_found)){
  $sql = "SELECT * FROM usuarios";
  try{
    // Get db object
    $db = new db();
    // Connect
    $db = $db->connect();

    $stmt = $db->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    // Add the products array inside an object
    $usuariosResponse = array('usuarios'=>$usuarios);
    $newResponse = $response->withJson($usuariosResponse);
    return $newResponse;

  }catch(PDOException $e){
    echo '{"error":{"text": '.$e->getMessage().'}}';
  }

});

// Get single producto
$app->get('/api/usuarios/{id}', function(Request $request, Response $response){
  $id = $request->getAttribute('id');
  $sql = "SELECT * FROM usuarios WHERE id = $id";

  try{
    // Get db object
    $db = new db();
    // Connect
    $db = $db->connect();

    $stmt = $db->query($sql);
    $usuario = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    echo json_encode($usuario);

  }catch(PDOException $e){
    echo '{"error":{"text": '.$e->getMessage().'}}';
  }
});



// Add product
$app->post('/api/usuarios', function(Request $request, Response $response){

  $params = $request->getBody();
  if($request->getHeaders()['HTTP_AUTHORIZATION']){
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, post the products
    if(!empty($access_token)){
      $user_found = verifyToken($access_token);
      if(!empty($user_found)){

        $nombre = $request->getParam('nombre');
        $email = $request->getParam('email');
        $empresa = $request->getParam('empresa');
        $activo = 1
        //$foto = $request->getParam('foto');

        $sql = "INSERT INTO usuarios (nombre,email,empresa,activo) VALUES (:nombre,:email,:empresa,:activo)";

        try{
          // Get db object
          $db = new db();
          // Connect
          $db = $db->connect();

          $stmt = $db->prepare($sql);

          $stmt->bindParam(':nombre', $nombre);
          $stmt->bindParam(':email', $email);
          $stmt->bindParam(':empresa', $empresa);
          $stmt->bindParam(':activo', $activo);

          $stmt->execute();

          $newResponse = $response->withStatus(200);
          $body = $response->getBody();
          $body->write('{"status": "success","message": "Usuario agregado", "usuario": "'.$usuario.'"}');
          $newResponse = $newResponse->withBody($body);
          return $newResponse;


        }catch(PDOException $e){
          echo '{"error":{"text": '.$e->getMessage().'}}';

        }
      }else{
        return loginError($response, 'Error de login, usuario no encontrado');
      }
    }else{
      return loginError($response, 'Error de login, falta access token');
    }
  }else{
    return loginError($response, 'Error de encabezado HTTP');
  }
});


// Update product
$app->put('/api/usuarios/{id}', function(Request $request, Response $response){

  $params = $request->getBody();
  if($request->getHeaders()['HTTP_AUTHORIZATION']){
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, post the products
    if(!empty($access_token)){
      $user_found = verifyToken($access_token);
      if(!empty($user_found)){

        $id = $request->getAttribute('id');

        $nombre = $request->getParam('nombre');
        $email = $request->getParam('email');
        $empresa = $request->getParam('empresa');
        $activo = $request->getParam('activo');
        //$foto = $request->getParam('foto');

        $sql = "UPDATE usuarios SET
        nombre = :nombre,
        email = :email,
        empresa = :empresa,
        activo = :activo
        WHERE id = $id";

        try{
          // Get db object
          $db = new db();
          // Connect
          $db = $db->connect();

          $stmt = $db->prepare($sql);

          $stmt->bindParam(':nombre', $nombre);
          $stmt->bindParam(':email', $email);
          $stmt->bindParam(':empresa', $empresa);
          $stmt->bindParam(':activo', $activo);

          $stmt->execute();

          echo('{"notice":{"text":"usuario actualizado"}}');

        }catch(PDOException $e){
          echo '{"error":{"text": '.$e->getMessage().'}}';
        }
      }else{
        return loginError($response, 'Error de login, usuario no encontrado');
      }
    }else{
      return loginError($response, 'Error de login, falta access token');
    }
  }else{
    return loginError($response, 'Error de encabezado HTTP');
  }
});


// Return a response with a 401 not allowed error.
function loginError(Response $response, String $errorText){
  $newResponse = $response->withStatus(401);
  $body = $response->getBody();
  $body->write('{"status": "login error","message": "'.$errorText.'"}');
  $newResponse = $newResponse->withBody($body);
  return $newResponse;
}
