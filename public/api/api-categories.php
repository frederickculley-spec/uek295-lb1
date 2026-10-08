<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OAT;

/**
 * Verarbeitet die Endpoints für Kategorien.
 */
class ApiCategories {
    /**
     * Gibt alle Kategorien für einen angemeldeten Benutzer zurück.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Die Kategorienliste oder eine Fehlerantwort.
     */

    // Auflisten aller Kategorien dokumentieren.
    #[OAT\Get(
        path: '/api/v1/categories',
        summary: 'Gibt alle Kategorien zurück.',
        description: 'Erfordert einen gültigen Token-Cookie. Bei einer leeren Tabelle wird eine leere Liste zurückgegeben.',
        tags: ['Kategorien'],
        security: [['cookieAuth' => []]],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Kategorienliste erfolgreich abgerufen.',
                content: new OAT\JsonContent(
                    type: 'array',
                    items: new OAT\Items(
                        type: 'object',
                        required: ['category_id', 'active', 'name'],
                        properties: [
                            new OAT\Property(
                                property: 'category_id',
                                type: 'integer',
                                example: 1
                            ),
                            new OAT\Property(
                                property: 'active',
                                type: 'integer',
                                example: 1
                            ),
                            new OAT\Property(
                                property: 'name',
                                type: 'string',
                                example: 'Firmen-Logos'
                            )
                        ]
                    )
                )
            ),
            new OAT\Response(
                response: 401,
                description: 'Token fehlt, ist ungültig oder abgelaufen. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 500,
                description: 'Das Abrufen ist wegen eines Datenbankfehlers fehlgeschlagen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorien konnten wegen eines Datenbankfehlers nicht geladen werden.'
                        )
                    ]
                )
            )
        ]
    )]

    public static function getAll(
        Request $request,
        Response $response,
        array $args
    ): Response {
        global $database;

        // Nur angemeldeten Benutzern Zugriff erlauben.
        if (!ApiAuthenticate::isAuthenticated($request)) {
            return $response->withStatus(401);
        }

        try {
            // Alle Kategorien aus der Datenbank lesen.
            $statement = $database->prepare(
                "SELECT category_id, active, name FROM category"
            );
            $statement->execute();
            $result = $statement->get_result();

            // Kategorien in einer Liste sammeln.
            $categories = [];

            while ($category = $result->fetch_assoc()) {
                $categories[] = $category;
            }

            $statement->close();

            // Auch eine leere Liste als JSON zurückgeben.
            $response->getBody()->write(json_encode($categories));

            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(200);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Die Kategorien konnten wegen eines Datenbankfehlers nicht geladen werden.",
                500
            );
        }
    }

    /**
     * Erstellt eine JSON-Antwort für einen Fehler.
     *
     * @param Response $response Die ausgehende Antwort.
     * @param string $message Die Beschreibung des Fehlers.
     * @param int $status Der HTTP-Statuscode.
     * @return Response Die JSON-Fehlerantwort.
     */
    public static function errorResponse(
        Response $response,
        string $message,
        int $status
    ): Response {
        $response->getBody()->write(json_encode([
            "error" => $message
        ]));

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus($status);
    }

    /**
     * Erstellt eine neue Kategorie.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Die neue Kategorie oder eine Fehlerantwort.
     */

    // Erstellen einer Kategorie dokumentieren.
    #[OAT\Post(
        path: '/api/v1/category',
        summary: 'Erstellt eine neue Kategorie.',
        description: 'Erfordert einen gültigen Token-Cookie. Jeder erfolgreiche Aufruf erstellt eine neue Kategorie. Die category_id wird automatisch vergeben.',
        tags: ['Kategorien'],
        security: [['cookieAuth' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                type: 'object',
                required: ['active', 'name'],
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        description: '0 für inaktiv, 1 für aktiv.',
                        enum: [0, 1],
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        description: 'Kategoriename. Leerzeichen am Anfang und Ende werden entfernt. Danach sind 1 bis 500 Zeichen erforderlich.',
                        minLength: 1,
                        maxLength: 500,
                        example: 'Firmen-Logos'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 201,
                description: 'Kategorie erfolgreich erstellt.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['category_id', 'active', 'name'],
                    properties: [
                        new OAT\Property(
                            property: 'category_id',
                            type: 'integer',
                            description: 'Automatisch erzeugte ID der neuen Kategorie.',
                            example: 2
                        ),
                        new OAT\Property(
                            property: 'active',
                            type: 'integer',
                            example: 1
                        ),
                        new OAT\Property(
                            property: 'name',
                            type: 'string',
                            example: 'Firmen-Logos'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Eingabe: fehlendes Pflichtfeld, falscher Datentyp, active nicht 0 oder 1 oder ungültige Namenslänge.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Der Kategoriename muss zwischen 1 und 500 Zeichen enthalten.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 401,
                description: 'Token fehlt, ist ungültig oder abgelaufen. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 500,
                description: 'Das Erstellen ist wegen eines Datenbankfehlers fehlgeschlagen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie konnte wegen eines Datenbankfehlers nicht erstellt werden.'
                        )
                    ]
                )
            )
        ]
    )]

    public static function create(
        Request $request,
        Response $response,
        array $args
    ): Response {
        global $database;

        // Nur angemeldeten Benutzern Zugriff erlauben.
        if (!ApiAuthenticate::isAuthenticated($request)) {
            return $response->withStatus(401);
        }

        // Pflichtfelder und Datentypen prüfen.
        $data = $request->getParsedBody();

        if (
            !is_array($data) ||
            !isset($data["active"], $data["name"]) ||
            !is_int($data["active"]) ||
            ($data["active"] !== 0 && $data["active"] !== 1) ||
            !is_string($data["name"])
        ) {
            return self::errorResponse(
                $response,
                "active muss als Zahl 0 oder 1 und name als Text angegeben werden.",
                400
            );
        }

        $active = $data["active"];
        $name = trim($data["name"]);

        // Einen nicht leeren Namen mit erlaubter Länge verlangen.
        if ($name === "" || mb_strlen($name, "UTF-8") > 500) {
            return self::errorResponse(
                $response,
                "Der Kategoriename muss zwischen 1 und 500 Zeichen enthalten.",
                400
            );
        }

        try {
            // Kategorie einfügen; die Datenbank vergibt die ID.
            $statement = $database->prepare(
                "INSERT INTO category (active, name) VALUES (?, ?)"
            );
            $statement->bind_param("is", $active, $name);
            $statement->execute();
            $categoryId = $database->insert_id;
            $statement->close();

            // Gespeicherte Werte mit der neuen ID zurückgeben.
            $category = [
                "category_id" => $categoryId,
                "active" => $active,
                "name" => $name
            ];

            $response->getBody()->write(json_encode($category));

            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(201);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Die Kategorie konnte wegen eines Datenbankfehlers nicht erstellt werden.",
                500
            );
        }
    }
    
    /**
     * Ändert die angegebenen Felder einer vorhandenen Kategorie.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Die aktualisierte Kategorie oder eine Fehlerantwort.
     */

    // Ändern einer vorhandenen Kategorie dokumentieren.
    #[OAT\Patch(
        path: '/api/v1/category/{category_id}',
        summary: 'Ändert eine vorhandene Kategorie.',
        description: 'Erfordert einen gültigen Token-Cookie. Mindestens active oder name muss angegeben werden. Nicht mitgesendete Felder behalten ihren bisherigen Wert. Die Antwort enthält die vollständige aktualisierte Kategorie.',
        tags: ['Kategorien'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OAT\Parameter(
                name: 'category_id',
                in: 'path',
                required: true,
                description: 'ID der zu ändernden Kategorie.',
                schema: new OAT\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: 2147483647,
                    example: 1
                )
            )
        ],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'active, name oder beide Felder angeben.',
            content: new OAT\JsonContent(
                type: 'object',
                anyOf: [
                    new OAT\Schema(required: ['active']),
                    new OAT\Schema(required: ['name'])
                ],
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        description: '0 für inaktiv, 1 für aktiv.',
                        enum: [0, 1],
                        example: 0
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        description: 'Leerzeichen am Anfang und Ende werden entfernt. Danach sind 1 bis 500 Zeichen erforderlich.',
                        minLength: 1,
                        maxLength: 500,
                        example: 'Tolle Firmen-Logos'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Kategorie erfolgreich geändert.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['category_id', 'active', 'name'],
                    properties: [
                        new OAT\Property(
                            property: 'category_id',
                            type: 'integer',
                            example: 1
                        ),
                        new OAT\Property(
                            property: 'active',
                            type: 'integer',
                            example: 0
                        ),
                        new OAT\Property(
                            property: 'name',
                            type: 'string',
                            example: 'Tolle Firmen-Logos'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Kategorie-ID oder Eingabe: kein änderbares Feld, falscher Datentyp, active nicht 0 oder 1 oder ungültige Namenslänge.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Mindestens active oder name muss angegeben werden.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 401,
                description: 'Token fehlt, ist ungültig oder abgelaufen. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Keine Kategorie mit dieser ID gefunden.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie existiert nicht.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 500,
                description: 'Das Ändern ist wegen eines Datenbankfehlers fehlgeschlagen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie konnte wegen eines Datenbankfehlers nicht geändert werden.'
                        )
                    ]
                )
            )
        ]
    )]

    public static function update(
        Request $request,
        Response $response,
        array $args
    ): Response {
        global $database;

        // Nur angemeldeten Benutzern Zugriff erlauben.
        if (!ApiAuthenticate::isAuthenticated($request)) {
            return $response->withStatus(401);
        }

        // Die Kategorie-ID muss eine positive ganze Zahl sein.
        $idText = $args["category_id"];

        if (
            !ctype_digit($idText) ||
            $idText < 1 ||
            $idText > 2147483647
        ) {
            return self::errorResponse(
                $response,
                "Die Kategorie-ID muss eine ganze Zahl zwischen 1 und 2147483647 sein.",
                400
            );
        }

        $categoryId = (int)$idText;
        $data = $request->getParsedBody();

        // Mindestens eines der beiden änderbaren Felder verlangen.
        if (
            !is_array($data) ||
            (
                !array_key_exists("active", $data) &&
                !array_key_exists("name", $data)
            )
        ) {
            return self::errorResponse(
                $response,
                "Mindestens active oder name muss angegeben werden.",
                400
            );
        }

        // active nur prüfen, wenn das Feld mitgesendet wurde.
        if (
            array_key_exists("active", $data) &&
            (
                !is_int($data["active"]) ||
                ($data["active"] !== 0 && $data["active"] !== 1)
            )
        ) {
            return self::errorResponse(
                $response,
                "active muss als Zahl 0 oder 1 angegeben werden.",
                400
            );
        }

        // name nur prüfen, wenn das Feld mitgesendet wurde.
        if (array_key_exists("name", $data)) {
            if (
                !is_string($data["name"]) ||
                trim($data["name"]) === "" ||
                mb_strlen(trim($data["name"]), "UTF-8") > 500
            ) {
                return self::errorResponse(
                    $response,
                    "Der Kategoriename muss als Text mit 1 bis 500 Zeichen angegeben werden.",
                    400
                );
            }
        }

        try {
            // Die bisherigen Werte der Kategorie laden.
            $statement = $database->prepare(
                "SELECT category_id, active, name
                 FROM category WHERE category_id = ?"
            );
            $statement->bind_param("i", $categoryId);
            $statement->execute();
            $category = $statement->get_result()->fetch_assoc();
            $statement->close();

            if ($category === null) {
                return self::errorResponse(
                    $response,
                    "Die Kategorie existiert nicht.",
                    404
                );
            }

            // Nur mitgesendete Felder ersetzen.
            if (array_key_exists("active", $data)) {
                $category["active"] = $data["active"];
            }

            if (array_key_exists("name", $data)) {
                $category["name"] = trim($data["name"]);
            }

            $active = $category["active"];
            $name = $category["name"];

            // Die aktualisierten Werte speichern.
            $statement = $database->prepare(
                "UPDATE category
                 SET active = ?, name = ?
                 WHERE category_id = ?"
            );
            $statement->bind_param("isi", $active, $name, $categoryId);
            $statement->execute();
            $statement->close();

            // Die vollständige aktualisierte Kategorie zurückgeben.
            $response->getBody()->write(json_encode($category));

            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(200);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Die Kategorie konnte wegen eines Datenbankfehlers nicht geändert werden.",
                500
            );
        }
    }

    /**
     * Gibt eine einzelne Kategorie anhand ihrer ID zurück.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Die Kategorie oder eine Fehlerantwort.
     */

    // Abrufen einer einzelnen Kategorie dokumentieren.
    #[OAT\Get(
        path: '/api/v1/category/{category_id}',
        summary: 'Gibt eine einzelne Kategorie zurück.',
        description: 'Sucht eine Kategorie anhand ihrer ID. Erfordert einen gültigen Token-Cookie.',
        tags: ['Kategorien'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OAT\Parameter(
                name: 'category_id',
                in: 'path',
                required: true,
                description: 'ID der gesuchten Kategorie.',
                schema: new OAT\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: 2147483647,
                    example: 1
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Kategorie erfolgreich abgerufen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['category_id', 'active', 'name'],
                    properties: [
                        new OAT\Property(
                            property: 'category_id',
                            type: 'integer',
                            example: 1
                        ),
                        new OAT\Property(
                            property: 'active',
                            type: 'integer',
                            example: 0
                        ),
                        new OAT\Property(
                            property: 'name',
                            type: 'string',
                            example: 'Tolle Firmen-Logos'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 400,
                description: 'Die Kategorie-ID ist keine ganze Zahl zwischen 1 und 2147483647.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie-ID muss eine ganze Zahl zwischen 1 und 2147483647 sein.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 401,
                description: 'Token fehlt, ist ungültig oder abgelaufen. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Keine Kategorie mit dieser ID gefunden.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie existiert nicht.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 500,
                description: 'Das Abrufen ist wegen eines Datenbankfehlers fehlgeschlagen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie konnte wegen eines Datenbankfehlers nicht geladen werden.'
                        )
                    ]
                )
            )
        ]
    )]

    public static function getOne(
        Request $request,
        Response $response,
        array $args
    ): Response {
        global $database;

        // Nur angemeldeten Benutzern Zugriff erlauben.
        if (!ApiAuthenticate::isAuthenticated($request)) {
            return $response->withStatus(401);
        }

        // Die Kategorie-ID muss eine positive ganze Zahl sein.
        $idText = $args["category_id"];

        if (
            !ctype_digit($idText) ||
            $idText < 1 ||
            $idText > 2147483647
        ) {
            return self::errorResponse(
                $response,
                "Die Kategorie-ID muss eine ganze Zahl zwischen 1 und 2147483647 sein.",
                400
            );
        }

        $categoryId = (int)$idText;

        try {
            // Die Kategorie mit der angegebenen ID suchen.
            $statement = $database->prepare(
                "SELECT category_id, active, name
                 FROM category WHERE category_id = ?"
            );
            $statement->bind_param("i", $categoryId);
            $statement->execute();
            $category = $statement->get_result()->fetch_assoc();
            $statement->close();

            if ($category === null) {
                return self::errorResponse(
                    $response,
                    "Die Kategorie existiert nicht.",
                    404
                );
            }

            // Die gefundene Kategorie als JSON zurückgeben.
            $response->getBody()->write(json_encode($category));

            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(200);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Die Kategorie konnte wegen eines Datenbankfehlers nicht geladen werden.",
                500
            );
        }
    }

    /**
     * Löscht eine Kategorie anhand ihrer ID.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Eine Bestätigung ohne Body oder eine Fehlerantwort.
     */

    // Löschen einer Kategorie dokumentieren.
    #[OAT\Delete(
        path: '/api/v1/category/{category_id}',
        summary: 'Löscht eine Kategorie.',
        description: 'Erfordert einen gültigen Token-Cookie. Zugehörige Produkte bleiben erhalten; ihre id_category wird durch die Fremdschlüssel-Beziehung auf null gesetzt. Es wird kein Request-Body benötigt.',
        tags: ['Kategorien'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OAT\Parameter(
                name: 'category_id',
                in: 'path',
                required: true,
                description: 'ID der zu löschenden Kategorie.',
                schema: new OAT\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: 2147483647,
                    example: 1
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 204,
                description: 'Kategorie erfolgreich gelöscht. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Die Kategorie-ID ist keine ganze Zahl zwischen 1 und 2147483647.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie-ID muss eine ganze Zahl zwischen 1 und 2147483647 sein.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 401,
                description: 'Token fehlt, ist ungültig oder abgelaufen. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Keine Kategorie mit dieser ID gefunden.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie existiert nicht.'
                        )
                    ]
                )
            ),
            new OAT\Response(
                response: 500,
                description: 'Das Löschen ist wegen eines Datenbankfehlers fehlgeschlagen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die Kategorie konnte wegen eines Datenbankfehlers nicht gelöscht werden.'
                        )
                    ]
                )
            )
        ]
    )]

    public static function delete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        global $database;

        // Nur angemeldeten Benutzern Zugriff erlauben.
        if (!ApiAuthenticate::isAuthenticated($request)) {
            return $response->withStatus(401);
        }

        // Die Kategorie-ID muss eine positive ganze Zahl sein.
        $idText = $args["category_id"];

        if (
            !ctype_digit($idText) ||
            $idText < 1 ||
            $idText > 2147483647
        ) {
            return self::errorResponse(
                $response,
                "Die Kategorie-ID muss eine ganze Zahl zwischen 1 und 2147483647 sein.",
                400
            );
        }

        $categoryId = (int)$idText;

        try {
            // Kategorie löschen. Zugehörige Produkte bleiben erhalten.
            // Der Fremdschlüssel setzt deren id_category auf NULL.
            $statement = $database->prepare(
                "DELETE FROM category WHERE category_id = ?"
            );
            $statement->bind_param("i", $categoryId);
            $statement->execute();

            // Prüfen, ob eine Kategorie gelöscht wurde.
            $deletedRows = $statement->affected_rows;
            $statement->close();

            if ($deletedRows === 0) {
                return self::errorResponse(
                    $response,
                    "Die Kategorie existiert nicht.",
                    404
                );
            }

            // Erfolgreiches Löschen ohne Antwort-Body bestätigen.
            return $response->withStatus(204);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Die Kategorie konnte wegen eines Datenbankfehlers nicht gelöscht werden.",
                500
            );
        }
    }

}