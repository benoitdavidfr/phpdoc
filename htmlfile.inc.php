<?php
/*PhpDoc:
name:  htmlfile.inc.php
title: htmlfile.inc.php - définition des classes File, HtmlFile, InFile et Screen
classes:
journal: |
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  4/12/2016
    ajout de la méthode File::yaml()
  26-27/11/2016:
    première version
*/
/*PhpDoc: classes
name:  class File
title: abstract class File extends Elt - sur-classe de HtmlFile, PhpFile et SqlFile
methods:
doc: Factorise les méthodes s'appliquant aux fichiers
*/
abstract class File extends Elt {
  /*PhpDoc: methods
  name:  __construct
  title: function __construct($param, array $context) - crée un objet File à partir de param et du contexte
  */
  function __construct($param, array $context) {
    if (isset($_GET['debug']) and $_GET['debug']) {
      $eltType = get_class($this);
      echo "Appel de $eltType::__construct() avec param=<pre>"; var_dump($param); echo "</pre>\n";
    }
    if (!is_array($param)) {
      $this->properties['name'] = $param;
      if (!($yaml = extractYamlFromFile('..'.$param))) {
        $this->properties['title'] = "$param - Not found";
        $this->fileNotFound = true;
        return;
      }
      $param = $yaml;
    }
    $this->init($param, $context);
  }
  
  /*PhpDoc: methods
  name:  yaml
  title: "function yaml(string $dirpath): ?array - fabrique sous la forme d'un tableau Php le Yaml correspondant à un fichier"
  doc: Retourne null en cas d'erreur
  */
  function yaml(string $dirpath): ?array {
    //echo "File::yaml(dirpath=$dirpath)<br>\n";
    $name = $this->properties['name'];
    //echo "dirpath=$dirpath<br>\n";
    //echo "name=$name<br>\n";
    //$this->dump();
    $yaml = extractYamlFromFile($dirpath.$name);
    //echo "<pre>"; print_r($yaml); echo "</pre>\n";
    return $yaml;
  }
  
  /*PhpDoc: methods
  name:  file
  title: "function file(): File - Renvoie le fichier auquel appartient l'élément, pour un file renvoie l'objet lui-même"
  */
  function file(): File { return $this; }
  
  /*PhpDoc: methods
  name:  path
  title: "function path(): string - Renvoie le chemin du fichier"
  */
  function path(): string { return $this->parent()->name().'/'.$this->name(); }
};

/*PhpDoc: classes
name:  class HtmlFile
title: class HtmlFile extends File - Fichiers HTML, JS, CSS, Yaml
*/
class HtmlFile extends File {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'links'=>[
      'hrefs',
      'includes',// fichiers inclus
    ],
  ];
};

/*PhpDoc: classes
name:  class InFile
title: abstract class InFile extends Elt - sur-classe des éléments qui ne correspondent pas à des fichiers
methods:
doc: Factorise la méthode __construct()
*/
abstract class InFile extends Elt {
/*PhpDoc: methods
name:  __construct
title: function __construct($param, array $context) - crée un élément qui ne correspond pas à un fichier
*/
  function __construct($param, array $context) {
    if (isset($_GET['debug']) and $_GET['debug']) {
      $eltType = get_class($this);
      echo "Appel de $eltType::__construct() avec param=<pre>"; var_dump($param); echo "</pre>\n";
    }
    $this->init($param, $context);
  }
};

/*PhpDoc: classes
name:  class Screen
title: class Screen extends InFile - définition d'écrans
*/
class Screen extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'links'=>[
      'hrefs',
    ],
  ];
};
