<?php
/*PhpDoc:
name:  module.inc.php
title: module.inc.php - définition de la classe Module
classes:
journal: |
  27/4/2019:
    remplacement de Spyc par le module Yaml de Symfony
    ajout d'un fichier phpdocagg.pser pour accélérer les lectures
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  17/2/2017
    ajout en test de synchro
  4/12/2016
    ajout des méthodes yaml(), makeagg() et makeallagg()
  30/11/2016:
    ajout de verifyInc()
  28/11/2016:
    ajout de notdocFiles()
  26-27/11/2016:
    première version
*/
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/*PhpDoc: classes
name:  class Module
title: class Module extends Elt - module ou sous-module
methods:
*/
class Module extends Elt {
// [ 'properties' => [ name=>type ], 'childCategories' => [ category => [ 'class'=>categoryClass ] ] ]
  static $structure = [
    'properties'=>[ // liste des propriétés
//      'name' => 'string',
      'path' => 'string',
      'title' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'childCategories'=>[ // liste des catégories d'enfants avec la classe Php associée
      'synchros' => 'Synchro',
      'submodules'=>'Module',
      'phpScripts'=>'PhpFile',
      'phpIncludes'=>'PhpFile',
      'htmlFiles'=>'HtmlFile',
      'sqlDBs'=>'SqlDB',
      'sqlFiles'=>'SqlFile',
    ],
  ];
  
/*PhpDoc: methods
name:  name
title: "function name(): string - Le nom d'un module est son path"
*/
  function name(): string {
    if (!isset($this->properties['path'])) {
      echo "<pre>";
      throw new Exception("propriete path non definie dans ".__FILE__.", ligne ".__LINE__);
    } else
      return $this->properties['path'];
  }
  
/*PhpDoc: methods
name:  __construct
title: function __construct($param, array $context) - créée un module à partir des paramètres et du contexte
doc: |
  $param est généralement le nom du répertoire, il peut aussi être un tableau de paramètres
*/
  function __construct($param, array $context) {
    if (isset($_GET['debug']) && $_GET['debug']) {
      echo "Appel de ",get_class($this),"::__construct() avec param=<pre>"; var_dump($param); echo "</pre>\n";
    }
    if (!is_array($param)) {
      if (is_file(__DIR__.'/..'.$param.'/phpdocagg.pser')) {
        $yaml = unserialize(file_get_contents('..'.$param.'/phpdocagg.pser'));
        //echo "Lecture de $param/phpdocagg.pser OK<br>\n";
        $param = $yaml;
      }
      elseif ($yaml = parent::read_yaml(__DIR__.'/..'.$param.'/phpdocagg.yaml')) {
        //echo "Lecture de $param/phpdocagg.yaml OK<br>\n";
        $param = $yaml;
      }
      elseif ($yaml = parent::read_yaml('..'.$param.'/phpdoc.yaml')) {
        //echo "Lecture de $param/phpdoc.yaml OK<br>\n";
        $param = $yaml;
      }
      else {
        $this->properties['title'] = "$param - Not found";
        $this->fileNotFound = true;
        return;
      }
    }
    $this->init($param, $context);
  }
    
/*PhpDoc: methods
name:  module
title: function module() - Retourne le module contenant l'objet ; pour un module, c'est le module lui-même
*/
  function module(): Module { return $this; }
  
/*PhpDoc: methods
name:  submodule
title: "function submodule(string $name): Module - Renvoie le sous-module portant le nom indiqué"
*/
  function submodule(string $name): Module {
//    echo "<pre>this="; print_r($this); echo "</pre>\n";
//    echo "<br>Module::submodule($name) sur $this\n";
    if (!isset($this->children['submodules']))
      throw new Exception("submodules non defini dans children");
    foreach ($this->children['submodules'] as $submod) {
//      echo "<br>submod=$submod\n";
      if (!isset($submod->properties['path']))
        throw new Exception("sous-module '$name' non trouvé dans le module $this");
      elseif ($this->properties['path']=='/')
        $submodname = substr($submod->properties['path'],
          strlen($this->properties['path']));
      else
        $submodname = substr($submod->properties['path'],strlen($this->properties['path'])+1);
//      echo "<br>submodname=$submodname\n";
      if ($submodname==$name)
        return $submod;
    }
//    echo "<pre>";
    throw new Exception("sous-module '$name' non trouvé dans le module $this");
  }
  
/*PhpDoc: methods
name:  solveLink
title: "function solveLink(string $categoryName, string $link): Elt - Résoud un lien"
*/
  function solveLink(string $categoryName, string $link): Elt {
    //echo "<br>Module::solveLink($categoryName, $link) sur $this\n";
    if (strncmp($link,'../',3)==0) {
      if (!$this->parent)
        throw new Exception("lien '$link' non trouvé dans le module $this");
      return $this->parent->solveLink($categoryName, substr($link,3));
    }
    if (($pos=strpos($link,'/')) !== FALSE) {
      $submodule = substr($link, 0, $pos);
      //echo "<br>submodule=$submodule\n";
      //echo "<br>link=",substr($link,$pos+1),"\n";
      return $this->submodule($submodule)->solveLink($categoryName, substr($link,$pos+1));
    }
    if (($pos=strpos($link,'?')) !== FALSE) {
      $filename = substr($link, 0, $pos);
      $anchor = substr($link, $pos);
    } else {
      $filename = $link;
      $anchor = null;
    }
    //echo "<br>filename=$filename\n";
    if (!($child = $this->findChildByName($filename)))
      throw new Exception("lien '$link' non trouvé dans le module $this");
    if (!$anchor)
      return $child;
    else
      return $child->solveLink($categoryName, $anchor);
  }
  
/*PhpDoc: methods
name: show
title: "function show(): void - redéfinition de show()"
doc: en plus de l'affichage générique, sur un module des actions sont proposées
*/
  function show(): void {
    parent::show();
    echo "<a href='?action=undocumentedFiles&amp;key=",urlencode($this->globalKey()),
         "'>Liste des fichiers non documentés</a><br>";
    echo "<a href='?action=verifyInc&amp;key=",urlencode($this->globalKey()),
         "'>Vérification des listes d'inclusions</a><br>";
    if (!$this->parent)
      echo "<a href='?action=makeallagg'>Fabrication de tous les phpdocagg.yaml</a><br>";
    elseif (!is_file('..'.$this->properties['path'].'/phpdocagg.yaml'))
      echo "<a href='?action=makeagg&amp;key=",urlencode($this->globalKey()),
           "'>Fabrication du phpdocagg.yaml</a><br>";
    else
      echo "<a href='?action=delagg&amp;key=",urlencode($this->globalKey()),
           "'>Suppression du phpdocagg.yaml</a><br>\n";
  }
  
/*PhpDoc: methods
name: listfiles
title: "function listfiles(): void - affiche la liste des fichiers"
*/
  function listfiles(): void {
    if (!$this->parent)
      echo "<pre>";
    echo $this->properties['path'],"\n";
    foreach (['submodules','phpScripts','phpIncludes','htmlFiles','sqlDBs','sqlFiles'] as $category)
      if (isset($this->children[$category]))
        foreach ($this->children[$category] as $child)
          if ($category=='submodules')
            $child->listfiles();
          else {
            $path = $this->properties['path'].'/'.$child->properties['name'];
            echo "<a href='?action=show&amp;key=",$child->globalKey(),"'>$path</a>\n";
          }
  }
  
/*PhpDoc: methods
name: notdocFiles
title: function undocumentedFiles() - Compare les fichiers définis et les fichiers existants
*/
  function undocumentedFiles() {
    $dir = $this->properties['path'];
    if (!is_dir('..'.$dir)) {
      echo "Ce module ne correspond pas à un répertoire<br>\n";
      return;
    }
    echo "<h2>Répertoire $dir</h2>\n";
    $files = scandir('..'.$dir);
    foreach ([
        'phpScripts'=>"Scripts Php",
        'phpIncludes'=>"Fichiers Php inclus",
        'htmlFiles'=>"Fichiers Html,...",
        'sqlFiles'=>"Fichiers Sql",
      ] as $category=>$title)
        if (isset($this->children[$category]) and $this->children[$category]) {
          echo "<h3>$title documentés</h3><ul>\n";
          foreach ($this->children[$category] as $script) {
            $name = $script->name();
            if (in_array($name,$files)) {
              echo "<li>$name\n";
              unset($files[array_search($name,$files)]);
            } else
              echo "<li><b>$name</b> n'existe pas dans le répertoire<br>\n";
          }
          echo "</ul>\n";
        }
    foreach ($files as $filename)
      if (in_array(substr($filename,-3),['.js'])
        or in_array(substr($filename,-4),['.php','.sql','.css'])
        or in_array(substr($filename,-5),['.html'])) {
        if (!isset($first)) {
          echo "<h3>Fichiers non documentés</h3><ul>\n";
          $first = 1;
        }
        echo "<li>$filename\n";
      }
      if (isset($first))
        echo "</ul>\n";
  }
  
/*PhpDoc: methods
name: verifyInc
title: "function verifyInc(): void - Vérification de la liste des fichiers inclus pour tous les fichiers Php et inclus du module"
*/
  function verifyInc(): void {
    $dirpath = $this->properties['path'];
    if (!is_dir('..'.$dirpath)) {
      echo "Ce module ne correspond pas à un répertoire<br>\n";
      return;
    }
    foreach ([
        'phpScripts'=>"Scripts Php",
        'phpIncludes'=>"Fichiers Php inclus",
        //'htmlFiles'=>"Fichiers Html,...",
        //'sqlFiles'=>"Fichiers Sql",
      ] as $category=>$title)
      if (isset($this->children[$category])) {
        echo "<h2>Scripts Php de $dirpath</h2>\n";
        foreach ($this->children[$category] as $script)
          $script->verifyInc($dirpath);
      }
  }
  
/*PhpDoc: methods
name:  yaml
title: "private function yaml(): array - fabrique le Yaml correspondant à un module sous la forme d'un tableau Php"
doc: |
  Utilisé par makeagg()
*/
  private function yaml($dirpath=null): array {
//    echo "Module::yaml(dirpath=$dirpath)<br>\n";
//    $this->dump();
    $dirpath = '..'.$this->properties['path'].'/';
//    echo "dirpath=$dirpath<br>\n";
    $yaml = parent::read_yaml($dirpath.'phpdoc.yaml');
    foreach ([
        'submodules'=>"Sous-modules",
        'phpScripts'=>"Scripts Php",
        'phpIncludes'=>"Fichiers Php inclus",
        'htmlFiles'=>"Fichiers Html,...",
        'sqlFiles'=>"Fichiers Sql",
      ] as $category=>$title)
        if (isset($this->children[$category]))
          foreach ($this->children[$category] as $noelt => $elt) {
            $eltyaml = $elt->yaml($dirpath);
//          echo "<pre>"; print_r($eltyaml); echo "</pre>\n";
            $yaml[$category][$noelt] = $eltyaml;
          }
//    echo "<pre>yaml="; print_r($yaml); echo "</pre>\n";
    return $yaml;
  }
  
/*PhpDoc: methods
name: makeagg
title: "function makeagg($context=null): void - fabrique phpdocagg.yaml/pser qui intègrent en un fichier tte la doc du module"
doc: |
  Le paramètre context :
  - contient effectivement le contexte si la méthode est appelée par phpdoc.php
  - vaut null si la méthode est appelée par makeallagg()
*/
  function makeagg(array $context=null): void {
    $yaml = $this->yaml();
    //echo "<pre>yaml="; print_r($yaml); echo "</pre>\n";
    //echo "<pre>",yaml_emit($yaml,YAML_UTF8_ENCODING),"</pre>";
    $dirpath = '..'.$this->properties['path'];
    file_put_contents("$dirpath/phpdocagg.yaml", Yaml::dump($yaml));
    file_put_contents("$dirpath/phpdocagg.pser", serialize($yaml));
    echo "Création de '$dirpath/phpdocagg.yaml/pser' OK<br>\n";
    // Si la méthode est appelée par phpdoc.php alors affichage de l'objet
    if ($context !== null)
      $this->show();
  }
  
/*PhpDoc: methods
name: delagg
title: "function delagg(): void - suppression du phpdocagg.yaml"
doc: |
  Après avoir supprimé le fichier phpdocagg.yaml, le contenu de la doc est probablement différent
  Il est donc important de relire les fichiers de doc.
  L'appel de phpdoc() permet d'effectuer cette relecture.
  Pour éviter de boucler le contexte est modifié et est testé.
*/
  function delagg($context): void {
    if (!isset($context['delagg'])) {
      $dirpath = '..'.$this->properties['path'];
      if (is_file("$dirpath/phpdocagg.pser")) {
        unlink("$dirpath/phpdocagg.pser");
        echo "Suppression de '$dirpath/phpdocagg.pser' OK<br>\n";
      }
      if (is_file("$dirpath/phpdocagg.yaml")) {
        unlink("$dirpath/phpdocagg.yaml");
        echo "Suppression de '$dirpath/phpdocagg.yaml' OK<br>\n",
             "Relecture des fichiers de doc<br>\n";
        $context['delagg'] = true;
        phpdoc($context);
      } else {
        echo "Erreur de suppression du '$dirpath/phpdocagg.yaml'<br>\n";
        $this->show();
      }
    } else {
      $this->show();
      die();
    }
  }
  
/*PhpDoc: methods
name: makeallagg
title: "function makeallagg(): void - appelle makeagg() pour chacun des modules de root"
*/
  function makeallagg(): void {
    if ($this->parent)
      return;
    foreach ($this->children['submodules'] as $module)
      $module->makeagg();
  }

  /*PhpDoc: methods
  name: store
  title: "function store(string $filename): void - enregistre la description du module dans le fichier"
  */
  function store(string $filename): void {
    file_put_contents($filename, serialize($this));
  }

  /*PhpDoc: methods
  name: read
  title: "static function read(string $filename): Module - lit la description du module dans le fichier"
  */
  static function read(string $filename): Module {
    return unserialize(file_get_contents($filename));
  }

  static function getByPath(string $path, Module $parent=null): Module {
    //echo "Module::getByPath($path, $parent)<br>\n";
    if ($parent == null)
      $parent = Module::read(__DIR__.'/root.pser');
    $name = $path[0];
    foreach ($parent->children['submodules'] as $submodule) {
      //echo "name=",$submodule->name(),"<br>\n";
      $smoduleName = $submodule->name();
      if ($path == $smoduleName)
        return $submodule;
      elseif (substr($path, 0, strlen($smoduleName)) == $smoduleName) {
        return self::getByPath($path, $submodule);
      }
    }
  }
};
