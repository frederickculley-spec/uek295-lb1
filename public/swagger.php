<?php 
require __DIR__ . "/../vendor/autoload.php"; 
// Require all scripts within /api directory. 
$scripts = glob(__DIR__ . "/api/*.php"); 
foreach ($scripts as $script) { 
    require $script;    
} 
// Build and return OpenAPI documentation as YAML. 
$result = (new \OpenApi\Builder()) 
    ->addSource(__DIR__) 
    ->build(); 
header('Content-Type: application/x-yaml'); 
echo $result->toYaml(); 