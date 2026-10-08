<?php
use OpenApi\Attributes as OAT;

#[OAT\Info(
    title: 'ÜK295 LB1 – Shop-API',
    version: '1.0.0'
)]

// Anmeldung über den Token-Cookie für Swagger beschreiben.
#[OAT\SecurityScheme(
    securityScheme: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'token',
    description: 'JWT aus POST /api/v1/authenticate. Für geschützte Endpoints erforderlich.'
)]

class ApiMain {

}