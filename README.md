ÜK295 LB1 – Shop-API

Für diese Aufgabe habe ich eine API für Produkte und Kategorien erstellt.
Dafür habe ich PHP, Slim und MySQL benutzt.
Die Anmeldung läuft über einen JWT, der im Cookie gespeichert wird.

Benutzte Plattformen

- XAMPP mit Apache und MySQL
- PHP mit mysqli und mbstring
- Composer
- Bruno, um die API zu testen

Installation

1. Den Projektordner unter C:\xampp\htdocs\uek295-lb1 ablegen.
2. Im XAMPP Control Panel Apache und MySQL starten.
3. PhpMyAdmin eine Datenbank uek295_lb1 erstellen. Danach den SQL-Export aus der Abgabe in diese Datenbank importieren.
4. Im Projektordner ein Terminal öffnen und diesen Befehl eingeben: composer install
5. Die Datei config.example.json kopieren. Die Kopie muss `config.json` heissen.
6. In config.json die Daten für die lokale Datenbank eintragen. Auch den Passwort-Platzhalter ersetzen. Das Passwort muss gleich sein wie beim Authenticate-Request in der Bruno-Collection.

Die echte config.json wird nicht auf GitHub hochgeladen, weil dort die lokalen Zugangsdaten drin sind.

Der Ordner vendor wird auch nicht hochgeladen.
Mit composer install werden die benötigten Bibliotheken installiert.
Die Datei `composer.lock` sorgt dafür, dass die gleichen Versionen
installiert werden.

API und Dokumentation öffnen

Die Basis-URL der API ist:

http://localhost/api/v1

Damit diese URL bei meinem lokalen XAMPP-Aufbau funktioniert,
habe ich in C:\xampp\htdocs\.htaccess diese Weiterleitung eingerichtet:

```apache
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^.*$ uek295-lb1/public/index.php [L,QSA]
```
Apache muss .htaccess-Dateien erlauben und mod_rewrite muss aktiv sein.

Die Swagger-Dokumentation kann man hier öffnen:

http://localhost/uek295-lb1/public/docs.html

Die Datei public/swagger.php erstellt die OpenAPI-Beschreibung.

API mit Bruno testen

1. Die bereitgestellte Bruno-Collection öffnen.
2. Zuerst `POST /api/v1/authenticate` senden. Der Benutzername ist `admin`. Das Passwort ist das, welches lokal in config.json eingetragen wurde.

3. Bruno speichert den Token-Cookie nach der Anmeldung.
4. Danach kann man die Produkt- und Kategorie-Requests senden.

Bei den Produkten wird die SKU in der URL verwendet.
Die interne product_id wird automatisch von der Datenbank vergeben.

Wenn man eine Kategorie löscht, werden die dazugehörigen Produkte
nicht gelöscht. Bei diesen Produkten wird id_category auf null gesetzt.

Quellen die für die Nutzung und erstellen genommen wurden.

- Slim: https://www.slimframework.com/docs/v4/
- PHP mysqli: https://www.php.net/manual/de/book.mysqli.php
- Composer: https://getcomposer.org/doc/01-basic-usage.md
- ReallySimpleJWT: https://github.com/RobDWaller/ReallySimpleJWT
- swagger-php: https://zircote.com/swagger-php/