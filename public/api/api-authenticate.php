<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ReallySimpleJWT\Token;
use OpenApi\Attributes as OAT;

class ApiAuthenticate {

// Anmeldung mit Eingabedaten und möglichen Antworten dokumentieren.
#[OAT\Post(
    path: '/api/v1/authenticate',
    summary: 'Meldet einen Benutzer an und erstellt einen JWT.',
    tags: ['Authentifizierung'],
    requestBody: new OAT\RequestBody(
        required: true,
        description: 'Benutzername und Passwort müssen als Text angegeben werden.',
        content: new OAT\JsonContent(
            required: ['username', 'password'],
            properties: [
                new OAT\Property(
                    property: 'username',
                    type: 'string',
                    example: 'admin'
                ),
                new OAT\Property(
                    property: 'password',
                    type: 'string',
                    example: 'deinPasswort'
                )
            ]
        )
    ),
    responses: [
        new OAT\Response(
            response: 200,
            description: 'Anmeldung erfolgreich. Der JWT wird als Cookie namens token zurückgegeben.',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'success',
                        type: 'boolean',
                        example: true
                    )
                ]
            )
        ),
        new OAT\Response(
            response: 400,
           description: 'Ungültiges JSON, fehlende Pflichtfelder oder falscher Datentyp.',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'error',
                        type: 'string',
                        example: 'Benutzername und Passwort müssen als Text angegeben werden.'
                    )
                ]
            )
        ),
        new OAT\Response(
            response: 401,
            description: 'Benutzername oder Passwort ist falsch. Die Antwort enthält keinen Body.'
        )
    ]
)]

    public static function authenticate(Request $request, Response $response, $args) {
    
           global $config;
$requestBody = $request->getParsedBody();

// Prüfen, ob Benutzername und Passwort als Text vorhanden sind.
if (
    !is_array($requestBody) ||
    !isset($requestBody["username"], $requestBody["password"]) ||
    !is_string($requestBody["username"]) ||
    !is_string($requestBody["password"])
) {
    $response->getBody()->write(json_encode([
        "error" => "Benutzername und Passwort müssen als Text angegeben werden."
    ]));

    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus(400);
}

//check if credentials are not valid.

if ($requestBody["username"] != "admin" || $requestBody["password"] != $config["password"]) {
//return error.
    return $response->withStatus(401, "Invalid credentials");
}
//generate token

$token = Token::create("admin", $config["password"], time() + 3600, "localhost");

//return token as cookie.

setcookie("token", $token, time() + 3600);

$response = $response->withHeader("content-type","application/json");
$response->getBody()->write(json_encode(["success" => true]));
return $response->withStatus(200);

    }
  /**
 * Prüft den Token-Cookie für geschützte Endpoints.
 *
 * @param Request $request Die eingehende Anfrage.
 * @return bool Ob ein gültiger, nicht abgelaufener Token vorhanden ist.
 */
public static function isAuthenticated(Request $request): bool {
    global $config;

    // Token aus den Cookies der Anfrage lesen.
    $cookies = $request->getCookieParams();
    $token = $cookies["token"] ?? null;

    // Ohne einen Token als Text ist kein Zugriff erlaubt.
    if (!is_string($token) || $token === "") {
        return false;
    }

    try {
        // Signatur und Ablaufzeit mit der JWT-Bibliothek prüfen.
        return Token::validate($token, $config["password"])
            && Token::validateExpiration($token);
    } catch (\Throwable $exception) {
        // Beschädigte oder unlesbare Tokens ebenfalls ablehnen.
        return false;
    }
}  
}
