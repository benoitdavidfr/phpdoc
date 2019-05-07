<?php
/*PhpDoc:
name: structure.php
title: structure.php - extrait de chaque classe correspondant à un élément la structure correspondante et l'affiche
doc: |
  utile pour vérifier la documentation
*/
require_once __DIR__.'/inc.php';

header('Content-type: application/json');

$structure = [];

foreach (['Module','HtmlFile','PhpFile','Screen','FunClassVar','Method','Parameter','Type','SqlFile','SqlDB','SqlTable','Column','Synchro'] as $class) {
  $structure[$class] = isset($class::$structure) ? $class::$structure : null;
}
echo json_encode($structure, JSON_PRETTY_PRINT);
