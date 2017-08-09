<?php
/*PhpDoc:
name:  sql.inc.php
title: sql.inc.php - définition des classes SqlFile, SqlDB, SqlTable et Column
classes:
journal: |
  26-27/11/2016:
    première version
*/
/*PhpDoc: classes
name:  class SqlFile
title: class SqlFile extends File - fichier .sql
*/
class SqlFile extends File {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'childCategories'=>[ // liste des catégories d'enfants avec la classe Php associée
      'tables'=>'sqlTable',
    ],
  ];
};

/*PhpDoc: classes
name:  class SqlDB
title: class SqlDB extends InFile - Base SQL
*/
class SqlDB extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
  ];
};

/*PhpDoc: classes
name:  class SqlTable
title: class SqlTable extends InFile - Table SQL
*/
class SqlTable extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'childCategories'=>[ // liste des catégories d'enfants avec la classe Php associée
      'columns'=>'Column', // une table comporte des colonnes
    ],
    'links'=>[
      'database', // une table appartient à une base de données
    ],
  ];
};

/*PhpDoc: classes
name:  class Column
title: class Column extends InFile - Colonne d'une table SQL
*/
class Column extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'definition' => 'string',
      'comment' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
  ];
};
