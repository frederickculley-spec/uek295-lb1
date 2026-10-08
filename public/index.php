<?php
use ReallySimpleJWT\Token;
use Slim\Factory\AppFactory; // Unverändert
use Slim\Psr7\Request;
use Slim\Psr7\Response;

require __DIR__ . "/../vendor/autoload.php";
require "api/api-main.php";
require "api/api-authenticate.php";  // Klasse für die Anmeldung laden.
require "api/api-products.php"; // Klasse für die Produkt-Endpoints laden.
require "api/api-categories.php"; // Klasse für die Kategorie-Endpoints laden.


$config = json_decode(file_get_contents(__DIR__ . "/../config.json"), true);
// Verbindung zur LB1-Datenbank herstellen.
$database = new mysqli(
    $config["db_host"],
    $config["db_user"],
    $config["db_password"],
    $config["db_name"]
);

// Zeichensatz für Umlaute und weitere Unicode-Zeichen festlegen.
$database->set_charset("utf8mb4");


//TODO: Hier weitere API-Dateien requiren.

$app = AppFactory::create(); // Unverändert

$app->setBasePath("/api/v1");  //wichtig für LB1

$app->addBodyParsingMiddleware();

// endpoint
// Anmeldung über die Methode authenticate der Klasse ApiAuthenticate verarbeiten.
$app->post("/authenticate", [ApiAuthenticate::class, "authenticate"]);


//TODO: Hier weitere Endpoints definieren.
// Alle Produkte über die Methode getAll der Klasse ApiProducts abrufen.
$app->get("/products", [ApiProducts::class, "getAll"]);
// Produkt anhand der SKU aus der URL erstellen oder aktualisieren.
$app->put("/product/{sku}", [ApiProducts::class, "createOrUpdate"]);

// Ein einzelnes Produkt anhand seiner SKU abrufen.
$app->get("/product/{sku}", [ApiProducts::class, "getOne"]);

// Ein Produkt anhand seiner SKU löschen.
$app->delete("/product/{sku}", [ApiProducts::class, "delete"]);

// Alle Kategorien abrufen.
$app->get("/categories", [ApiCategories::class, "getAll"]);


// Eine neue Kategorie erstellen.
$app->post("/category", [ApiCategories::class, "create"]);


// Angegebene Felder einer vorhandenen Kategorie ändern.
$app->patch("/category/{category_id}", [ApiCategories::class, "update"]);


// Eine einzelne Kategorie anhand ihrer ID abrufen.
$app->get("/category/{category_id}", [ApiCategories::class, "getOne"]);

// Eine Kategorie anhand ihrer ID löschen.
$app->delete("/category/{category_id}", [ApiCategories::class, "delete"]);

$app->run(); // Unverändert