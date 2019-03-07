<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Get All books. For each book, find if it's rented and get the active rental information
// For each active rental, find the name of the user that has it
$app->get('/api/libros', function (Request $request, Response $response) {
    // Verify the access token to validate the user selection
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                // The access token is necesary to identify the company of the active user and
                // only return the books from that company

                // Find the user based on the token
                $idUsuario = $user_found[0]->user_id;
                $sql = "SELECT * FROM usuarios WHERE id = $idUsuario";

                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $empresa = $usuarios[0]->empresa;

                    // Select all books
                    $sql = "SELECT * FROM libros";
                    $stmt = $db->query($sql);
                    $libros = $stmt->fetchAll(PDO::FETCH_OBJ);
                    // This array is returned to the user
                    $librosResponse = [];

                    // Verify if the user only wants the available books or the whole list
                    $disponibles = $request->getQueryParams()['disponibles'];

                    // Find the active owner and rentals for each book
                    foreach ($libros as $libro) {

                        // Find the book owner, get the id and find it in the users table
                        $usr_dueno = $libro->usr_dueno;
                        $sql = "SELECT * FROM usuarios WHERE id = $usr_dueno";
                        $stmt = $db->query($sql);
                        $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                        // Store the owner's name in the book object
                        $libro->usr_dueno_nombre = $usuarios[0]->nombre;
                        $libro->usr_dueno_empresa = $usuarios[0]->empresa;

                        $idLibro = $libro->id;
                        $sql = "SELECT * FROM alquileres WHERE id_libro = $idLibro AND activo = 1";
                        $stmt = $db->query($sql);
                        $alquileres = $stmt->fetchAll(PDO::FETCH_OBJ);

                        // If the book has active rentals, find the name of the user that has it
                        if (count($alquileres)>0) {
                            // Goes through all the rentals to keep it general, each book should only have one active rental at a time
                            foreach ($alquileres as $alquiler) {
                                $usr_alquiler = $alquiler->id_usuario;
                                $sql = "SELECT * FROM usuarios WHERE id = $usr_alquiler";
                                $stmt = $db->query($sql);
                                $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                                // Stores the user name inside the rental objetct
                                $alquiler->nombre = $usuarios[0]->nombre;
                            }
                        }
                        // Add the rental object to the book object in the array
                        $libro->alquileres = $alquileres;

                        // Verify if the book belongs to the same company as the current user
                        if ($libro->usr_dueno_empresa == $empresa) {

                          // If the user only wants the available books, only   push the
                            // books with no outstanding rentals
                            // and from other owners
                            if (
                              ($disponibles == 'true' && !count($alquileres) && $usr_dueno != $idUsuario) ||
                              $disponibles != 'true'
                          ) {
                                // If everything fits, push it into a new array that is then returned
                                array_push($librosResponse, $libro);
                            }
                        }
                    }

                    // Reset all variables
                    $db = null;
                    $alquileres = null;
                    $idLibro = null;
                    $idUsuario = null;

                    // Add the books array into an object for response
                    $newResponse = $response->withJson($librosResponse);
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

// Get single book
$app->get('/api/libros/{id}', function (Request $request, Response $response) {

  // Verify the access token to validate the user selection
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                // The access token is necesary to identify the company of the active user and
                // only return the books from that company


                $idUsuarioLogueado = $user_found[0]->user_id;

                $id = $request->getAttribute('id');
                $sql = "SELECT * FROM libros WHERE id = $id";

                try {
                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->query($sql);
                    $libros = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $libro = $libros[0];

                    // Find the book's rentals
                    $idLibro = $libro->id;
                    $sql = "SELECT * FROM alquileres WHERE id_libro = $idLibro";
                    $stmt = $db->query($sql);
                    $alquileres = $stmt->fetchAll(PDO::FETCH_OBJ);

                    // Find the book's reviews
                    $sql = "SELECT * FROM reviews WHERE id_libro = $idLibro";
                    $stmt = $db->query($sql);
                    $reviews = $stmt->fetchAll(PDO::FETCH_OBJ);

                    // Find the book owner, get the id and find it in the users table
                    $idUsuario = $libro->usr_dueno;
                    $sql = "SELECT * FROM usuarios WHERE id = $idUsuario";
                    $stmt = $db->query($sql);
                    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                    // Store the owner's name in the book object
                    $libro->usr_dueno_nombre = $usuarios[0]->nombre;

                    $alquileresTerminados = array();

                    // If the book has active rentals, find the name of the user that has it
                    if (count($alquileres)>0) {
                        // Goes through all the rentals to keep it general, each book should only have one active rental at a time
                        foreach ($alquileres as $alquiler) {
                            $idUsuarioAlquiler = $alquiler->id_usuario;
                            $sql = "SELECT * FROM usuarios WHERE id = $idUsuarioAlquiler";
                            $stmt = $db->query($sql);
                            $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                            // Stores the user name inside the rental objetct
                            $alquiler->nombre = $usuarios[0]->nombre;
                            // If the current rental is still active, separate it
                            // This makes the UI much easier
                            if ($alquiler->activo) {
                                $libro->alquilerActivo = $alquiler;
                            } else {
                                // Finished rentals are stored in another array to
                                // separate them from the current rental
                                array_push($alquileresTerminados, $alquiler);
                            }
                        }
                    }
                    // Add the rental object to the book object in the array
                    $libro->alquileres = $alquileresTerminados;

                    // Variable that indicates if the user has entered a review for this book
                    $reviewDelUsuario = false;

                    // If the book has reviews, find the name of the user that wrote it
                    if (count($reviews)>0) {
                        // Goes through all the reviews
                        foreach ($reviews as $review) {
                            $idUsuarioReview = $review->id_usuario;

                            // Set the variable if the review author is the active user
                            if ($idUsuarioReview == $idUsuarioLogueado) {
                                $reviewDelUsuario = true;
                            }

                            $sql = "SELECT * FROM usuarios WHERE id = $idUsuarioReview";
                            $stmt = $db->query($sql);
                            $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                            // Stores the user name inside the review objetct
                            $review->nombre = $usuarios[0]->nombre;
                            // If the current rental is still active, separate it
                // This makes the UI much easier
                        }
                    }
                    // Add the reviews object to the book object
                    $libro->reviews = $reviews;
                    $libro->reviewDelUsuario = $reviewDelUsuario;

                    // Verify if the user has an active rental
                    // This is used in the UI in the rental confirmation page
                    $sql = "SELECT * FROM alquileres WHERE id_usuario = $idUsuarioLogueado and activo = 1";
                    $stmt = $db->query($sql);
                    $alquileresUsuarioLogueado = $stmt->fetchAll(PDO::FETCH_OBJ);

                    if (count($alquileresUsuarioLogueado)>0) {
                        $libro->usuarioTieneAlquiler = true;
                    }

                    $db = null;

                    //echo json_encode($libro);

                    // Add the books array into an object for response
                    $newResponse = $response->withJson($libro);
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



// Add product
$app->post('/api/libros', function (Request $request, Response $response) {
    $params = $request->getBody();
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                $titulo = $request->getParam('titulo');
                $autor = $request->getParam('autor');
                $ano = $request->getParam('ano');
                $resumen = $request->getParam('resumen');
                $idioma = $request->getParam('idioma');
                $usr_dueno = $request->getParam('usr_dueno');
                $activo = 1;
                //$tapa = $request->getParam('tapa');

                $sql = "INSERT INTO libros (titulo,autor,ano,resumen,idioma,usr_dueno,activo) VALUES (:titulo,:autor,:ano,:resumen,:idioma,:usr_dueno,:activo)";

                try {
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
                    $body->write('{"status": "success","message": "Libro agregado", "libro": "'.$titulo.'"}');
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
$app->put('/api/libros/{id}', function (Request $request, Response $response) {
    $params = $request->getBody();
    if ($request->getHeaders()['HTTP_AUTHORIZATION']) {
        $access_token = $request->getHeaders()['HTTP_AUTHORIZATION'][0];
        $access_token = explode(" ", $access_token)[1];
        // Find the access token, if a user is returned, post the products
        if (!empty($access_token)) {
            $user_found = verifyToken($access_token);
            if (!empty($user_found)) {
                $id = $request->getAttribute('id');

                // Get the queryparam to verify if its an enable/disable operation
                $operation = $request->getQueryParam('operation');

                if ($operation) {
                    if ($operation == 'enable') {
                        $activo = 1;
                    } else {
                        $activo = 0;
                    }

                    $sql = "UPDATE libros SET activo = :activo WHERE id = $id";

                    // Get db object
                    $db = new db();
                    // Connect
                    $db = $db->connect();

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':activo', $activo);

                    $stmt->execute();

                    $newResponse = $response->withStatus(200);
                    $body = $response->getBody();
                    $body->write('{"status": "success","message": "Libro actualizado"}');
                    $newResponse = $newResponse->withBody($body);
                } else {
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

                    try {
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
                        $body->write('{"status": "success","message": "Libro actualizado"}');
                        $newResponse = $newResponse->withBody($body);
                    } catch (PDOException $e) {
                        echo '{"error":{"text": '.$e->getMessage().'}}';
                    }
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


// Return a response with a 401 not allowed error.
function loginError(Response $response, $errorText)
{
    $newResponse = $response->withStatus(401);
    $body = $response->getBody();
    $body->write('{"status": "login error","message": "'.$errorText.'"}');
    $newResponse = $newResponse->withBody($body);
    return $newResponse;
}
