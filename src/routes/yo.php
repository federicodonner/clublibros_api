<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get the current user's information¡
$app->get('/api/yo', function (Request $request, Response $response) {
    // Verify the access token to validate the user selection
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {

              // Find the user based on the token
                $idUsuario = $user_found[0]->user_id;
                $sql = "SELECT * FROM usuarios WHERE id = $idUsuario";
                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    // Execute the query
                    $stmt = $db->query($sql);
                    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $usuario = $usuarios[0];


                    // Get the books the user brought
                    $sql = "SELECT * FROM libros WHERE usr_dueno = $idUsuario";
                    $stmt = $db->query($sql);
                    $libros = $stmt->fetchAll(PDO::FETCH_OBJ);

                    foreach ($libros as $libro) {
                        $id_libro = $libro->id;
                        $sql = "SELECT * FROM alquileres WHERE id_libro = $id_libro and activo = 1";
                        $stmt = $db->query($sql);
                        $alquileres = $stmt->fetchAll(PDO::FETCH_OBJ);

                        if (!empty($alquileres)) {
                            $id_usuario = $alquileres[0]->id_usuario;

                            $sql = "SELECT * FROM usuarios WHERE id = $id_usuario";
                            $stmt = $db->query($sql);
                            $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

                            $alquileres[0]->detallesUsuario = $usuarios[0];
                        }

                        $libro->alquilerActivo = $alquileres[0];
                    }


                    $usuario->libros = $libros;

                    // Get the rentals the user had in the past
                    $sql = "SELECT * FROM alquileres WHERE id_usuario = $idUsuario";
                    $stmt = $db->query($sql);
                    $alquileres = $stmt->fetchAll(PDO::FETCH_OBJ);

                    // Find the name of each book and add it to the rental object
                    foreach ($alquileres as $alquiler) {
                        // Isolate the book id for querying
                        $idLibro = $alquiler->id_libro;
                        // Find the book with the id
                        $sql = "SELECT * FROM libros WHERE id = $idLibro";
                        $stmt = $db->query($sql);
                        $libros = $stmt->fetchAll(PDO::FETCH_OBJ);
                        // Add it to the object
                        $alquiler->nombre_libro = $libros[0]->titulo;
                        $alquiler->autor_libro = $libros[0]->autor;

                        // If the rental is an active one, separate it
                        // This makes the UI simpler
                        if ($alquiler->activo == 1) {
                            $usuario->alquilerActivo = $alquiler;
                        }
                    }
                    // Add the rentals to the user object
                    $usuario->alquileres = $alquileres;


                    // Reset the connection variables
                    $db = null;

                    // Add the products array inside an object
                    $usuariosResponse = $usuario;
                    $newResponse = $response->withJson($usuariosResponse);
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
