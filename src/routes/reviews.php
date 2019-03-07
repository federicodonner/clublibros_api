<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get All customers
$app->get('/api/reviews', function (Request $request, Response $response) {
    // // $params = $app->request()->getBody();
    // if($request->getHeaders()['HTTP_AUTHORIZATION']){
    // $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
    // $access_token = explode(" ", $access_token)[1];
    // Find the access token, if a user is returned, find the productos
    // if(!empty($access_token)){
    // $user_found = verifyToken($access_token);
    // if(!empty($user_found)){
    $sql = "SELECT * FROM reviews";
    try {
        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $reviews = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        // Add the products array inside an object
        $reviewsResponse = array('reviews'=>$reviews);
        $newResponse = $response->withJson($reviewsResponse);
        return $newResponse;
    } catch (PDOException $e) {
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
});

// Get single producto
$app->get('/api/reviews/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $sql = "SELECT * FROM reviews WHERE id = $id";

    try {
        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $review = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($review);
    } catch (PDOException $e) {
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
});



// Add product
$app->post('/api/reviews', function (Request $request, Response $response) {
    $params = $request->getBody();
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                $id_usuario = $request->getParam('id_usuario');
                $id_libro = $request->getParam('id_libro');
                $estrellas = $request->getParam('estrellas');
                $texto = $request->getParam('texto');

                $sql = "INSERT INTO reviews (id_usuario,id_libro,estrellas,texto)
        VALUES (:id_usuario,:id_libro,:estrellas,:texto)";

                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':id_usuario', $id_usuario);
                    $stmt->bindParam(':id_libro', $id_libro);
                    $stmt->bindParam(':estrellas', $estrellas);
                    $stmt->bindParam(':texto', $texto);

                    $stmt->execute();

                    $newResponse = $response->withStatus(200);
                    $body = $response->getBody();
                    $body->write('{"status": "success","message": "Review agregada", "review": "'.$review.'"}');
                    $newResponse = $newResponse->withBody($body);
                    return $newResponse;
                } catch (PDOException $e) {
                    echo '{"error":{"text": '.$e->getMessage().'}}';
                }
            } else {
                return loginError($response, 'Error de login, usuario no encontrado');
            }
        } else {
            return loginError($response, 'Error de login, falta access token');
        }
    } else {
        return loginError($response, 'Error de encabezado HTTP');
    }
});


// Update product
$app->put('/api/reviews/{id}', function (Request $request, Response $response) {
    $params = $request->getBody();
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                $id = $request->getAttribute('id');

                $id_usuario = $request->getParam('id_usuario');
                $id_libro = $request->getParam('id_libro');
                $estrellas = $request->getParam('estrellas');
                $texto = $request->getParam('texto');

                $sql = "UPDATE reviews SET
        id_usuario = :id_usuario,
        id_libro = :id_libro,
        estrellas = :estrellas,
        texto = :texto
        WHERE id = $id";

                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':id_usuario', $id_usuario);
                    $stmt->bindParam(':id_libro', $id_libro);
                    $stmt->bindParam(':estrellas', $estrellas);
                    $stmt->bindParam(':texto', $texto);

                    $stmt->execute();

                    echo('{"notice":{"text":"review actualizada"}}');
                } catch (PDOException $e) {
                    echo '{"error":{"text": '.$e->getMessage().'}}';
                }
            } else {
                return loginError($response, 'Error de login, usuario no encontrado');
            }
        } else {
            return loginError($response, 'Error de login, falta access token');
        }
    } else {
        return loginError($response, 'Error de encabezado HTTP');
    }
});
