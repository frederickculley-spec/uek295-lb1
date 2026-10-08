<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OAT;

/**
 * Verarbeitet die Endpoints für Produkte.
 */
class ApiProducts {
    /**
     * Gibt alle Produkte für einen angemeldeten Benutzer zurück.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Die Produktliste oder eine 401-Antwort.
     */
    
// Produktliste, Token-Anforderung und mögliche Antworten dokumentieren.
#[OAT\Get(
    path: '/api/v1/products',
    summary: 'Gibt alle Produkte zurück.',
    description: 'Erfordert einen gültigen Token-Cookie. Bei einer leeren Tabelle wird eine leere Liste zurückgegeben.',
    tags: ['Produkte'],
    security: [['cookieAuth' => []]],
    responses: [
        new OAT\Response(
            response: 200,
            description: 'Produktliste erfolgreich abgerufen.',
            content: new OAT\JsonContent(
                type: 'array',
                items: new OAT\Items(
                    type: 'object',
                    required: [
                        'product_id', 'sku', 'active', 'id_category',
                        'name', 'image', 'description', 'price', 'stock'
                    ],
                    properties: [
                        new OAT\Property(
                            property: 'product_id',
                            type: 'integer',
                            example: 12345678
                        ),
                        new OAT\Property(
                            property: 'sku',
                            type: 'string',
                            example: 'CSBE-LOGO'
                        ),
                        new OAT\Property(
                            property: 'active',
                            type: 'integer',
                            example: 1
                        ),
                        new OAT\Property(
                            property: 'id_category',
                            type: 'integer',
                            nullable: true,
                            description: 'Kategorie-ID oder null bei einem Produkt ohne Kategorie.',
                            example: 1
                        ),
                        new OAT\Property(
                            property: 'name',
                            type: 'string',
                            example: 'CsBe-Logo'
                        ),
                        new OAT\Property(
                            property: 'image',
                            type: 'string',
                            example: 'https://example.com/logo.png'
                        ),
                        new OAT\Property(
                            property: 'description',
                            type: 'string',
                            example: 'Ein Firmenlogo.'
                        ),
                        new OAT\Property(
                            property: 'price',
                            type: 'number',
                            example: 39999.95
                        ),
                        new OAT\Property(
                            property: 'stock',
                            type: 'integer',
                            example: 3
                        )
                    ]
                )
            )
        ),
        new OAT\Response(
            response: 401,
            description: 'Token fehlt, ist ungültig oder abgelaufen. Die Antwort enthält keinen Body.'
        )
    ]
)]
    public static function getAll(
        
        Request $request,
        Response $response,
        array $args
    ): Response {
        global $database;

        // Ohne gültigen Token keine Produktdaten zurückgeben.
        if (!ApiAuthenticate::isAuthenticated($request)) {
            return $response->withStatus(401);
        }

        // Alle vorgegebenen Produktspalten aus der Datenbank lesen.
        $statement = $database->prepare(
            "SELECT product_id, sku, active, id_category, name,
                    image, description, price, stock
             FROM product"
        );
        $statement->execute();
        $result = $statement->get_result();

        // Datensätze in einer Liste sammeln.
        $products = [];
        while ($product = $result->fetch_assoc()) {
            // Den Preis als JSON-Zahl zurückgeben.
            $product["price"] = (float) $product["price"];
            $products[] = $product;
        }

        // Ressourcen der vorbereiteten Abfrage freigeben.
        $statement->close();

        // Auch eine leere Produktliste als JSON zurückgeben.
        $response->getBody()->write(json_encode($products));

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(200);
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
    // Fehlermeldung im gleichen JSON-Format zurückgeben.
    $response->getBody()->write(json_encode([
        "error" => $message
    ]));

    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($status);
}
/**
 * Erstellt oder aktualisiert ein Produkt anhand seiner SKU.
 *
 * @param Request $request Die eingehende Anfrage.
 * @param Response $response Die ausgehende Antwort.
 * @param array $args Die Parameter aus der Route.
 * @return Response Die Produktdaten oder eine Fehlerantwort.
 */

    // Erstellen und Aktualisieren eines Produkts dokumentieren.
    #[OAT\Put(
        path: '/api/v1/product/{sku}',
        summary: 'Erstellt oder aktualisiert ein Produkt.',
        description: 'Die SKU kommt aus der URL. Existiert sie bereits, wird das Produkt aktualisiert. Andernfalls wird ein neues Produkt mit automatisch erzeugter product_id erstellt. Alle sieben Body-Felder sind erforderlich.',
        tags: ['Produkte'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Artikelnummer des Produkts. Leerzeichen am Anfang und Ende werden entfernt.',
                schema: new OAT\Schema(
                    type: 'string',
                    minLength: 1,
                    maxLength: 100,
                    example: '12345678'
                )
            )
        ],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                type: 'object',
                required: [
                    'active', 'id_category', 'name', 'image',
                    'description', 'price', 'stock'
                ],
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        enum: [0, 1],
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'id_category',
                        type: 'integer',
                        nullable: true,
                        description: 'ID einer vorhandenen Kategorie oder null für keine Kategorie.',
                        minimum: 1,
                        maximum: 2147483647,
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        minLength: 1,
                        maxLength: 500,
                        example: 'CsBe-Logo'
                    ),
                    new OAT\Property(
                        property: 'image',
                        type: 'string',
                        maxLength: 1000,
                        example: 'https://example.com/logo.png'
                    ),
                    new OAT\Property(
                        property: 'description',
                        type: 'string',
                        description: 'Beschreibung mit höchstens 65535 Bytes.',
                        example: 'Kaufen Sie jetzt das tolle Logo der CsBe!'
                    ),
                    new OAT\Property(
                        property: 'price',
                        type: 'number',
                        description: 'Nicht negativer Preis; wird auf zwei Nachkommastellen gerundet.',
                        minimum: 0,
                        example: 39999.95
                    ),
                    new OAT\Property(
                        property: 'stock',
                        type: 'integer',
                        minimum: 0,
                        maximum: 2147483647,
                        example: 3
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 201,
                description: 'Produkt neu erstellt.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: [
                        'product_id', 'sku', 'active', 'id_category',
                        'name', 'image', 'description', 'price', 'stock'
                    ],
                    properties: [
                        new OAT\Property(property: 'product_id', type: 'integer', example: 1),
                        new OAT\Property(property: 'sku', type: 'string', example: '12345678'),
                        new OAT\Property(property: 'active', type: 'integer', example: 1),
                        new OAT\Property(property: 'id_category', type: 'integer', nullable: true, example: 1),
                        new OAT\Property(property: 'name', type: 'string', example: 'CsBe-Logo'),
                        new OAT\Property(property: 'image', type: 'string', example: 'https://example.com/logo.png'),
                        new OAT\Property(property: 'description', type: 'string', example: 'Kaufen Sie jetzt das tolle Logo der CsBe!'),
                        new OAT\Property(property: 'price', type: 'number', example: 39999.95),
                        new OAT\Property(property: 'stock', type: 'integer', example: 3)
                    ]
                )
            ),
            new OAT\Response(
                response: 200,
                description: 'Vorhandenes Produkt aktualisiert. Die product_id bleibt erhalten.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: [
                        'product_id', 'sku', 'active', 'id_category',
                        'name', 'image', 'description', 'price', 'stock'
                    ],
                    properties: [
                        new OAT\Property(property: 'product_id', type: 'integer', example: 1),
                        new OAT\Property(property: 'sku', type: 'string', example: '12345678'),
                        new OAT\Property(property: 'active', type: 'integer', example: 1),
                        new OAT\Property(property: 'id_category', type: 'integer', nullable: true, example: 1),
                        new OAT\Property(property: 'name', type: 'string', example: 'CsBe-Logo'),
                        new OAT\Property(property: 'image', type: 'string', example: 'https://example.com/logo.png'),
                        new OAT\Property(property: 'description', type: 'string', example: 'Kaufen Sie jetzt das tolle Logo der CsBe!'),
                        new OAT\Property(property: 'price', type: 'number', example: 39999.95),
                        new OAT\Property(property: 'stock', type: 'integer', example: 3)
                    ]
                )
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Eingabe: beispielsweise fehlendes Pflichtfeld, falscher Datentyp, unerlaubter Wert oder nicht vorhandene Kategorie.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die angegebene Kategorie existiert nicht.'
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
                description: 'Das Speichern ist wegen eines Datenbankfehlers fehlgeschlagen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Das Produkt konnte wegen eines Datenbankfehlers nicht gespeichert werden.'
                        )
                    ]
                )
            )
        ]
    )]

public static function createOrUpdate(
    Request $request,
    Response $response,
    array $args
): Response {
    global $database;

    // Nur angemeldeten Benutzern Zugriff erlauben.
    if (!ApiAuthenticate::isAuthenticated($request)) {
        return $response->withStatus(401);
    }

    // SKU aus der URL und Produktdaten aus dem JSON-Body lesen.
    $sku = trim($args["sku"]);
    $data = $request->getParsedBody();

    if ($sku === "" || mb_strlen($sku, "UTF-8") > 100) {
        return self::errorResponse(
            $response,
            "Die SKU muss zwischen 1 und 100 Zeichen enthalten.",
            400
        );
    }

    if (!is_array($data)) {
        return self::errorResponse(
            $response,
            "Ein gültiger JSON-Body ist erforderlich.",
            400
        );
    }

    // Alle Felder des vorgegebenen PUT-Bodys verlangen.
    $requiredFields = [
        "active", "id_category", "name", "image",
        "description", "price", "stock"
    ];

    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $data)) {
            return self::errorResponse(
                $response,
                "Das Pflichtfeld " . $field . " fehlt.",
                400
            );
        }
    }

    // Datentypen entsprechend den Produktfeldern prüfen.
    if (
        !is_int($data["active"]) ||
        ($data["active"] !== 0 && $data["active"] !== 1) ||
        !is_string($data["name"]) ||
        !is_string($data["image"]) ||
        !is_string($data["description"]) ||
        (!is_int($data["price"]) && !is_float($data["price"])) ||
        !is_int($data["stock"]) ||
        ($data["id_category"] !== null && !is_int($data["id_category"]))
    ) {
        return self::errorResponse(
            $response,
            "Ungültige Datentypen. active muss 0 oder 1 sein.",
            400
        );
    }

    // Texte bereinigen und Werte für die SQL-Anweisungen vorbereiten.
    $active = $data["active"];
    $categoryId = $data["id_category"];
    $name = trim($data["name"]);
    $image = trim($data["image"]);
    $description = trim($data["description"]);
    $price = round((float) $data["price"], 2);
    $stock = $data["stock"];

    // Textlängen entsprechend den Datenbankspalten prüfen.
    if (
        $name === "" ||
        mb_strlen($name, "UTF-8") > 500 ||
        mb_strlen($image, "UTF-8") > 1000 ||
        strlen($description) > 65535
    ) {
        return self::errorResponse(
            $response,
            "Der Name darf nicht leer sein. Die erlaubten Textlängen müssen eingehalten werden.",
            400
        );
    }

    // Wertebereiche für Preis, Lagerbestand und Kategorie-ID prüfen.
    if (
        $price < 0 ||
        $price >= 1e63 ||
        $stock < 0 ||
        $stock > 2147483647 ||
        ($categoryId !== null &&
            ($categoryId < 1 || $categoryId > 2147483647))
    ) {
        return self::errorResponse(
            $response,
            "Preis, Lagerbestand oder Kategorie-ID liegt ausserhalb des erlaubten Bereichs.",
            400
        );
    }

    try {
        // Eine angegebene Kategorie muss tatsächlich vorhanden sein.
        if ($categoryId !== null) {
            $statement = $database->prepare(
                "SELECT category_id FROM category WHERE category_id = ?"
            );
            $statement->bind_param("i", $categoryId);
            $statement->execute();
            $category = $statement->get_result()->fetch_assoc();
            $statement->close();

            if ($category === null) {
                return self::errorResponse(
                    $response,
                    "Die angegebene Kategorie existiert nicht.",
                    400
                );
            }
        }

        // Nach einem Produkt mit der SKU aus der URL suchen.
        $statement = $database->prepare(
            "SELECT product_id FROM product WHERE sku = ?"
        );
        $statement->bind_param("s", $sku);
        $statement->execute();
        $existingProduct = $statement->get_result()->fetch_assoc();
        $statement->close();

        if ($existingProduct === null) {
            // Neues Produkt einfügen. product_id wird automatisch vergeben.
            $statement = $database->prepare(
                "INSERT INTO product
                 (sku, active, id_category, name, image,
                  description, price, stock)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $statement->bind_param(
                "siisssdi",
                $sku, $active, $categoryId, $name,
                $image, $description, $price, $stock
            );
            $statement->execute();

            $productId = $database->insert_id;
            $statement->close();
            $status = 201;
        } else {
            // Vorhandenes Produkt aktualisieren und seine interne ID behalten.
            $productId = $existingProduct["product_id"];

            $statement = $database->prepare(
                "UPDATE product
                 SET active = ?, id_category = ?, name = ?, image = ?,
                     description = ?, price = ?, stock = ?
                 WHERE product_id = ?"
            );
            $statement->bind_param(
                "iisssdii",
                $active, $categoryId, $name, $image,
                $description, $price, $stock, $productId
            );
            $statement->execute();
            $statement->close();
            $status = 200;
        }

        // Gespeicherte Produktwerte mit interner ID und SKU zurückgeben.
        $product = [
            "product_id" => $productId,
            "sku" => $sku,
            "active" => $active,
            "id_category" => $categoryId,
            "name" => $name,
            "image" => $image,
            "description" => $description,
            "price" => $price,
            "stock" => $stock
        ];

        $response->getBody()->write(json_encode($product));

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus($status);
    } catch (\mysqli_sql_exception $exception) {
        // Datenbankfehler ohne interne SQL-Details an den Client melden.
        return self::errorResponse(
            $response,
            "Das Produkt konnte wegen eines Datenbankfehlers nicht gespeichert werden.",
            500
        );
    }
}
    /**
     * Gibt ein einzelnes Produkt anhand seiner SKU zurück.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Die Produktdaten oder eine Fehlerantwort.
     */
        // Abrufen eines einzelnen Produkts dokumentieren.
    #[OAT\Get(
        path: '/api/v1/product/{sku}',
        summary: 'Gibt ein einzelnes Produkt zurück.',
        description: 'Sucht ein Produkt anhand der SKU aus der URL. Erfordert einen gültigen Token-Cookie.',
        tags: ['Produkte'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Artikelnummer des gesuchten Produkts. Leerzeichen am Anfang und Ende werden entfernt.',
                schema: new OAT\Schema(
                    type: 'string',
                    minLength: 1,
                    maxLength: 100,
                    example: '12345678'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Produkt erfolgreich abgerufen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: [
                        'product_id', 'sku', 'active', 'id_category',
                        'name', 'image', 'description', 'price', 'stock'
                    ],
                    properties: [
                        new OAT\Property(property: 'product_id', type: 'integer', example: 1),
                        new OAT\Property(property: 'sku', type: 'string', example: '12345678'),
                        new OAT\Property(property: 'active', type: 'integer', example: 1),
                        new OAT\Property(
                            property: 'id_category',
                            type: 'integer',
                            nullable: true,
                            description: 'Kategorie-ID oder null bei einem Produkt ohne Kategorie.',
                            example: 1
                        ),
                        new OAT\Property(property: 'name', type: 'string', example: 'CsBe-Logo'),
                        new OAT\Property(property: 'image', type: 'string', example: 'https://example.com/logo.png'),
                        new OAT\Property(property: 'description', type: 'string', example: 'Kaufen Sie jetzt das tolle Logo der CsBe!'),
                        new OAT\Property(property: 'price', type: 'number', example: 39999.95),
                        new OAT\Property(property: 'stock', type: 'integer', example: 3)
                    ]
                )
            ),
            new OAT\Response(
                response: 400,
                description: 'Die SKU ist nach dem Entfernen äusserer Leerzeichen leer oder länger als 100 Zeichen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die SKU muss zwischen 1 und 100 Zeichen enthalten.'
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
                description: 'Kein Produkt mit dieser SKU gefunden.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Das Produkt existiert nicht.'
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
                            example: 'Das Produkt konnte wegen eines Datenbankfehlers nicht geladen werden.'
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

        // SKU aus der URL lesen und prüfen.
        $sku = trim($args["sku"]);

        if ($sku === "" || mb_strlen($sku, "UTF-8") > 100) {
            return self::errorResponse(
                $response,
                "Die SKU muss zwischen 1 und 100 Zeichen enthalten.",
                400
            );
        }

        try {
            // Das Produkt mit der angegebenen SKU suchen.
            $statement = $database->prepare(
                "SELECT product_id, sku, active, id_category, name,
                        image, description, price, stock
                 FROM product
                 WHERE sku = ?"
            );
            $statement->bind_param("s", $sku);
            $statement->execute();
            $product = $statement->get_result()->fetch_assoc();
            $statement->close();

            // Bei einer unbekannten SKU einen Fehler zurückgeben.
            if ($product === null) {
                return self::errorResponse(
                    $response,
                    "Das Produkt existiert nicht.",
                    404
                );
            }

            // Preis als JSON-Zahl zurückgeben.
            $product["price"] = (float)$product["price"];
            $response->getBody()->write(json_encode($product));

            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(200);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Das Produkt konnte wegen eines Datenbankfehlers nicht geladen werden.",
                500
            );
        }
    }
        /**
     * Löscht ein Produkt anhand seiner SKU.
     *
     * @param Request $request Die eingehende Anfrage.
     * @param Response $response Die ausgehende Antwort.
     * @param array $args Die Parameter aus der Route.
     * @return Response Eine Bestätigung ohne Body oder eine Fehlerantwort.
     */

            // Löschen eines Produkts dokumentieren.
    #[OAT\Delete(
        path: '/api/v1/product/{sku}',
        summary: 'Löscht ein Produkt.',
        description: 'Löscht das Produkt mit der angegebenen SKU. Erfordert einen gültigen Token-Cookie. Es wird kein Request-Body benötigt.',
        tags: ['Produkte'],
        security: [['cookieAuth' => []]],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'Artikelnummer des zu löschenden Produkts. Leerzeichen am Anfang und Ende werden entfernt.',
                schema: new OAT\Schema(
                    type: 'string',
                    minLength: 1,
                    maxLength: 100,
                    example: '12345678'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 204,
                description: 'Produkt erfolgreich gelöscht. Die Antwort enthält keinen Body.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Die SKU ist nach dem Entfernen äusserer Leerzeichen leer oder länger als 100 Zeichen.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Die SKU muss zwischen 1 und 100 Zeichen enthalten.'
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
                description: 'Kein Produkt mit dieser SKU gefunden.',
                content: new OAT\JsonContent(
                    type: 'object',
                    required: ['error'],
                    properties: [
                        new OAT\Property(
                            property: 'error',
                            type: 'string',
                            example: 'Das Produkt existiert nicht.'
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
                            example: 'Das Produkt konnte wegen eines Datenbankfehlers nicht gelöscht werden.'
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

        // SKU aus der URL lesen und prüfen.
        $sku = trim($args["sku"]);

        if ($sku === "" || mb_strlen($sku, "UTF-8") > 100) {
            return self::errorResponse(
                $response,
                "Die SKU muss zwischen 1 und 100 Zeichen enthalten.",
                400
            );
        }

        try {
            // Das Produkt mit der angegebenen SKU löschen.
            $statement = $database->prepare(
                "DELETE FROM product WHERE sku = ?"
            );
            $statement->bind_param("s", $sku);
            $statement->execute();

            // Prüfen, ob tatsächlich ein Datensatz gelöscht wurde.
            $deletedRows = $statement->affected_rows;
            $statement->close();

            if ($deletedRows === 0) {
                return self::errorResponse(
                    $response,
                    "Das Produkt existiert nicht.",
                    404
                );
            }

            // Erfolgreiches Löschen ohne Antwort-Body bestätigen.
            return $response->withStatus(204);
        } catch (\mysqli_sql_exception $exception) {
            return self::errorResponse(
                $response,
                "Das Produkt konnte wegen eines Datenbankfehlers nicht gelöscht werden.",
                500
            );
        }
    }
}