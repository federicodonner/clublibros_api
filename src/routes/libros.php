<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get All customers
$app->get('/api/libros', function(Request $request, Response $response){
  // // $params = $app->request()->getBody();
  // if($request->getHeaders()['HTTP_AUTHORIZATION']){
  // $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
  // $access_token = explode(" ", $access_token)[1];
  // Find the access token, if a user is returned, find the productos
  // if(!empty($access_token)){
  // $user_found = verifyToken($access_token);
  // if(!empty($user_found)){
  $sql = "SELECT * FROM libros";
  try{
    // Get db object
    $db = new db();
    // Connect
    $db = $db->connect();

    $stmt = $db->query($sql);
    $libros = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    // Add the products array inside an object
    $librosResponse = array('libros'=>$libros);
    $newResponse = $response->withJson($librosResponse);
    return $newResponse;

  }catch(PDOException $e){
    echo '{"error":{"text": '.$e->getMessage().'}}';
  }

});

// Get single producto
$app->get('/api/libros/{id}', function(Request $request, Response $response){
  $id = $request->getAttribute('id');
  $sql = "SELECT * FROM libros WHERE id = $id";

  try{
    // Get db object
    $db = new db();
    // Connect
    $db = $db->connect();

    $stmt = $db->query($sql);
    $libro = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    echo json_encode($libro);

  }catch(PDOException $e){
    echo '{"error":{"text": '.$e->getMessage().'}}';
  }
});



// Add product
$app->post('/api/libros', function(Request $request, Response $response){

  $params = $request->getBody();
  if($request->getHeaders()['HTTP_AUTHORIZATION']){
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, post the products
    if(!empty($access_token)){
      $user_found = verifyToken($access_token);
      if(!empty($user_found)){

        $titulo = $request->getParam('titulo');
        $autor = $request->getParam('autor');
        $ano = $request->getParam('ano');
        $resumen = $request->getParam('resumen');
        $idioma = $request->getParam('idioma');
        $usr_dueno = $request->getParam('usr_dueno');
        $activo = 1;
        //$tapa = $request->getParam('tapa');

        $sql = "INSERT INTO libros (titulo,autor,ano,resumen,idioma,usr_dueno,activo) VALUES (:titulo,:autor,:ano,:resumen,:idioma,:usr_dueno,:activo)";

        try{
          // Get db object
          $db = new db();
          // Connect
          $db = $db->connect();

          $stmt = $db->prepare($sql);

          $stmt->bindParam(':titulo', $titulo);
          $stmt->bindParam(':autor', $autor);
          $stmt->bindParam(':ano', $ano);
          $stmt->bindParam(':resumen', $resumen);
          $stmt->bindParam(':idioma', $idioma);
          $stmt->bindParam(':usr_dueno', $usr_dueno);
          $stmt->bindParam(':activo', $activo);

          $stmt->execute();

          $newResponse = $response->withStatus(200);
          $body = $response->getBody();
          $body->write('{"status": "success","message": "Libro agregado", "libro": "'.$libro.'"}');
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
$app->put('/api/libros/{id}', function(Request $request, Response $response){

  $params = $request->getBody();
  if($request->getHeaders()['HTTP_AUTHORIZATION']){
    $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, post the products
    if(!empty($access_token)){
      $user_found = verifyToken($access_token);
      if(!empty($user_found)){

        $id = $request->getAttribute('id');

        $titulo = $request->getParam('titulo');
        $autor = $request->getParam('autor');
        $ano = $request->getParam('ano');
        $resumen = $request->getParam('resumen');
        $idioma = $request->getParam('idioma');
        $usr_dueno = $request->getParam('usr_dueno');
        $activo = $request->getParam('activo');
        //$tapa = $request->getParam('tapa');

        $sql = "UPDATE libros SET
        titulo = :titulo,
        autor = :autor,
        ano = :ano,
        resumen = :resumen,
        idioma = :idioma,
        usr_dueno = :usr_dueno,
        activo = :activo
        WHERE id = $id";

        try{
          // Get db object
          $db = new db();
          // Connect
          $db = $db->connect();

          $stmt = $db->prepare($sql);

          $stmt->bindParam(':titulo', $titulo);
          $stmt->bindParam(':autor', $autor);
          $stmt->bindParam(':ano', $ano);
          $stmt->bindParam(':resumen', $resumen);
          $stmt->bindParam(':idioma', $idioma);
          $stmt->bindParam(':usr_dueno', $usr_dueno);
          $stmt->bindParam(':activo', $activo);

          $stmt->execute();

          echo('{"notice":{"text":"libro actualizado"}}');

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
function loginError(Response $response, $errorText){
  $newResponse = $response->withStatus(401);
  $body = $response->getBody();
  $body->write('{"status": "login error","message": "'.$errorText.'"}');
  $newResponse = $newResponse->withBody($body);
  return $newResponse;
}
