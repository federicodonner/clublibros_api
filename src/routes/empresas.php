<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get All customers
$app->get('/api/empresas', function (Request $request, Response $response) {
    $sql = "SELECT * FROM empresas WHERE activo = 1";

    try {
        // Get db object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $empresas = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        // Add the products array inside an object
        $empresasResponse = array('empresas'=>$empresas);
        $newResponse = $response->withJson($empresasResponse);
        return $newResponse;
    } catch (PDOException $e) {
        echo '{"error":{"text": '.$e->getMessage().'}}';
    }
});
//
// // Get single user
// $app->get('/api/usuarios/{id}', function (Request $request, Response $response) {
//     $id = $request->getAttribute('id');
//     $sql = "SELECT * FROM usuarios WHERE id = $id";
//
//     try {
//         // Get db object
//         $db = new db();
//         // Connect
//         $db = $db->connect();
//
//         $stmt = $db->query($sql);
//         $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
//         // Isolate only the selected user to return as an object and not an array
//         $usuario = $usuarios[0];
//
//         // Isolate the user id for following queries
//         $idUsuario = $usuario->id;
//         // Get the books the user brought
//         $sql = "SELECT * FROM libros WHERE usr_dueno = $idUsuario";
//         $stmt = $db->query($sql);
//         $libros = $stmt->fetchAll(PDO::FETCH_OBJ);
//         $usuario->libros = $libros;
//
//         // Get the rentals the user had in the past
//         $sql = "SELECT * FROM alquileres WHERE id_usuario = $idUsuario";
//         $stmt = $db->query($sql);
//         $alquileres = $stmt->fetchAll(PDO::FETCH_OBJ);
//
//         $alquileresTerminados = array();
//
//         // Find the name of each book and add it to the rental object
//         foreach ($alquileres as $alquiler) {
//             // Isolate the book id for querying
//             $idLibro = $alquiler->id_libro;
//             // Find the book with the id
//             $sql = "SELECT * FROM libros WHERE id = $idLibro";
//             $stmt = $db->query($sql);
//             $libros = $stmt->fetchAll(PDO::FETCH_OBJ);
//
//             // Add it to the object
//             $alquiler->nombre_libro = $libros[0]->titulo;
//             $alquiler->autor_libro = $libros[0]->autor;
//             // Add the owner
//             $alquiler->id_dueno = $libros[0]->usr_dueno;
//
//             // If the rental is an active one, separate it
//             // This makes the UI simpler
//             if ($alquiler->activo == 1) {
//                 $usuario->alquilerActivo = $alquiler;
//             } else {
//                 array_push($alquileresTerminados, $alquiler);
//             }
//         }
//         // Add the rentals to the user object
//         $usuario->alquileres = $alquileresTerminados;
//
//
//         $db = null;
//         // Add the book object into an object for response
//         $newResponse = $response->withJson($usuario);
//         return $newResponse;
//     } catch (PDOException $e) {
//         echo '{"error":{"text": '.$e->getMessage().'}}';
//     }
// });
//
//
//
// // Add product
// $app->post('/api/usuarios', function (Request $request, Response $response) {
//     $params = $request->getBody();
//
//     $nombre = $request->getParam('nombre');
//     $email = $request->getParam('email');
//     $empresa = $request->getParam('empresa');
//     $activo = 1;
//     //$foto = $request->getParam('foto');
//
//     $sql = "INSERT INTO usuarios (nombre,email,empresa,activo) VALUES (:nombre,:email,:empresa,:activo)";
//
//     try {
//         // Get db object
//         $db = new db();
//         // Connect
//         $db = $db->connect();
//
//         $stmt = $db->prepare($sql);
//
//         $stmt->bindParam(':nombre', $nombre);
//         $stmt->bindParam(':email', $email);
//         $stmt->bindParam(':empresa', $empresa);
//         $stmt->bindParam(':activo', $activo);
//
//         $stmt->execute();
//
//         $newResponse = $response->withStatus(200);
//         $body = $response->getBody();
//         $body->write('{"status": "success","message": "Usuario agregado", "usuario": "'.$nombre.'"}');
//         $newResponse = $newResponse->withBody($body);
//         return $newResponse;
//     } catch (PDOException $e) {
//         echo '{"error":{"text": '.$e->getMessage().'}}';
//     }
// });
//
//
// // Update product
// $app->put('/api/usuarios/{id}', function (Request $request, Response $response) {
//     $params = $request->getBody();
//     if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
//         $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
//         $access_token = explode(" ", $access_token)[1];
//         // Find the access token, if a user is returned, post the products
//         if (!empty($access_token)) {
//             $user_found = verifyToken($access_token);
//             if (!empty($user_found)) {
//                 $id = $request->getAttribute('id');
//
//                 $nombre = $request->getParam('nombre');
//                 $email = $request->getParam('email');
//                 $activo = $request->getParam('activo');
//                 //$foto = $request->getParam('foto');
//
//                 $sql = "UPDATE usuarios SET
//         nombre = :nombre,
//         email = :email,
//         activo = :activo
//         WHERE id = $id";
//
//                 try {
//                     // Get db object
//                     $db = new db();
//                     // Connect
//                     $db = $db->connect();
//
//                     $stmt = $db->prepare($sql);
//
//                     $stmt->bindParam(':nombre', $nombre);
//                     $stmt->bindParam(':email', $email);
//                     $stmt->bindParam(':activo', $activo);
//
//                     $stmt->execute();
//
//                     echo('{"notice":{"text":"usuario actualizado"}}');
//                 } catch (PDOException $e) {
//                     echo '{"error":{"text": '.$e->getMessage().'}}';
//                 }
//             } else {
//                 return loginError($response, (string) 'Error de login, usuario no encontrado');
//             }
//         } else {
//             return loginError($response, (string) 'Error de login, falta access token');
//         }
//     } else {
//         return loginError($response, (string) 'Error de encabezado HTTP');
//     }
// });
