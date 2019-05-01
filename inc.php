<?php
/*PhpDoc:
name:  inc.php
title: inc.php - fichiers Php à inclure pour utiliser PhpDoc
includes:
  - ../vendor/autoload.php
  - elt.inc.php
  - module.inc.php
  - htmlfile.inc.php
  - exyamlfile.inc.php
  - phpfile.inc.php
  - exyamlphp.inc.php
  - sql.inc.php
  - synchro.inc.php
journal: |
  1/5/2019:
    première version
*/
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/elt.inc.php';
require_once __DIR__.'/module.inc.php';
require_once __DIR__.'/htmlfile.inc.php';
require_once __DIR__.'/exyamlfile.inc.php';
require_once __DIR__.'/phpfile.inc.php';
require_once __DIR__.'/exyamlphp.inc.php';
require_once __DIR__.'/sql.inc.php';
require_once __DIR__.'/synchro.inc.php';
