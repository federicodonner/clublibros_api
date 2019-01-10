<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Add product
$app->post('/api/oauth', function(Request $request, Response $response){

  $grant_type = $request->getParam('grant_type');

  if($grant_type == 'password'){
    $username = $request->getParam('user');
    //$pass = $request->getParam('pass');

    $sql = "SELECT * FROM usuarios WHERE nombre = '$username'";

    try{
      // Get db object
      $db = new db();
      // Connect
      $db = $db->connect();

      $stmt = $db->query($sql);
      $usuario = $stmt->fetchAll(PDO::FETCH_OBJ);

      // Si no hay ningún usuario con ese nombre
      if($usuario == null){
        //cambio el estatus del mensaje e incluyo el mensaje de error
        $newResponse = $response->withStatus(409);
        $body = $response->getBody();
        $body->write('{"status": "error","message": "Nombre de usuario o password incorrecto"}');
        $newResponse = $newResponse->withBody($body);
        return $newResponse;
      }else{
        // Store the user token in the database
        // Prepare viarables
        $access_token = random_str(32);
        $now = time();
        $user_id = $usuario[0]->id;

        // SQL statement
        $sql = "INSERT INTO logins (user_id,token,created_date) VALUES (:user_id,:token,:now)";

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':token', $access_token);
        $stmt->bindParam(':now', $now);

        $stmt->execute();
        // $newResponse = $response->withStatus(201);
        // $body = $response->getBody();
        // $body->write('{"status":"201", "token":"'.$access_token.'"}');
        // $newResponse = $newResponse->withBody($body);
        // return $newReponse
        // ->withHeader('Access-Control-Allow-Origin', '*')
        // ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        // ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        echo('{"status":"201", "token":"'.$access_token.'"}');
      }

    }catch(PDOException $e){
      echo '{"error":{"text": '.$e->getMessage().'}}';
    }

  }else if($grant_type == 'token'){

  }else{



    $data = array('data' => 'incorrect grant_type');
    $newResponse = $response->withJson($data, 406);

    echo($newResponse);
  }
  //
  // $email = $request->getParam('email');
  // $pass = $request->getParam('pass');
  // $type = $request->getParam('type');
  // $description = $request->getParam('description');
  // $picture = $request->getParam('picture');
  //
  // echo($name.' - '.$price.' - '.$stock.' - '.$description.' - '.$picture);
  //
  //
  // $sql = "INSERT INTO productos (name,price,stock,description,picture) VALUES (:name,:price,:stock,:description,:picture)";
  // try{
  //   // Get db object
  //   $db = new db();
  //   // Connect
  //   $db = $db->connect();
  //
  //   $stmt = $db->prepare($sql);
  //
  //   $stmt->bindParam(':name', $name);
  //   $stmt->bindParam(':price', $price);
  //   $stmt->bindParam(':stock', $stock);
  //   $stmt->bindParam(':description', $description);
  //   $stmt->bindParam(':picture', $picture);
  //
  //   $stmt->execute();
  //
  //   echo('{"notice":{"text":"producto added"}}');
  //
  // }catch(PDOException $e){
  //   echo '{"error":{"text": '.$e->getMessage().'}}';
  //
  // }
});

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
  $pieces = [];
  $max = mb_strlen($keyspace, '8bit') - 1;
  for ($i = 0; $i < $length; ++$i) {
    $pieces []= $keyspace[rand(0, $max)];
  }
  return implode('', $pieces);
}
