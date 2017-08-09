<?php
/*PhpDoc:
name:  synchro.inc.php
title: synchro.inc.php - définition de la classe Synchro
classes:
journal: |
  17/2/2017
    première version
*/
/*PhpDoc: classes
name:  class Synchro
title: class Synchro extends InFile - classe correspondant aux infos de synchronisation
methods:
doc: |
  Un projet PhpDoc (répertoire MyPassport) correspond à:
  - un répertoire source déjà identifié dans PhpDoc (path)
  - d'éventuels répertoires de publication sur internet (prod, dev, mac, ...)
  Ces derniers répertoires peuvent être définis dans PhpDoc par:
  - le mot-clé 'synchros' associé à une liste de publications, chacune définie par:
    - name: un identifiant
    - url: URL à laquelle le projet est publié
    - title: titre expliquant à quoi correspond cette publication
  Cette information permet de générer automatiquement les URL de synchronisation
  Ce qui pourra être fait dans /synchro/index.php en générant des URL de synchro du type:
    http://localhost/{path}/synchro.php?remote={synchro.url}/'>{synchro.title}</a>
  Exemple:
    path: /migcat3
    synchros:
      - name: prod
        url: http://migcat.fr
        title: site de production
      - name: dev
        url: http://geoapi.alwaysdata.net/migcat3
        title: site de développement
  URLs de synchro générées:
    <li><a href='http://localhost/migcat3/synchro.php?remote=http://migcat.fr/'>site de production</a>
    <li><a href='http://localhost/migcat3/synchro.php?remote=http://migcat.fr/'>site de production</a>
  Cela permet d'instrumenter une méthode de publication et d'éviter de définir des méthodes trop spécifiques
*/
class Synchro extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'url' => 'string',
      'title' => 'string',
    ],
  ];
};
