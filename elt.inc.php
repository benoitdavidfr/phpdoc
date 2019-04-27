<?php
/*PhpDoc:
name:  elt.inc.php
title: elt.inc.php - définition de la classe Elt
classes:
doc: |
  La classe Elt porte une grande partie des traitements de PhpDoc
journal: |
  27/4/2019:
    remplacement de Spyc par le module Yaml de Symfony
  9/8/2017:
    modifs pour faciliter la description des bases MongoDB
    ajout de la possibilité de documenter une propriété privée d'une classe
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  26-27/11/2016:
    première version
*/
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/*PhpDoc: classes
name:  class Elt
title: class Elt - Un élément générique de la doc
methods:
doc: |
  La classe Elt porte une grande partie des traitements de PhpDoc
*/
abstract class Elt {
// Les titres utilisés dans show() définis pour chaque catégorie d'enfant et de lien
  static $titles = [
    'childCategories' => [
      'synchros' => "Mécanismes de synchronisation",
      'submodules' => "Sous-modules",
      'phpScripts' => "Scripts Php",
      'phpIncludes' => "Fichiers .inc.php",
      'functions' => "Fonctions",
      'classes' => "Classes",
      'pubproperties' => "Propriétés publiques",
      'privateproperties' => "Propriétés privées",
      'methods' => "Méthodes",
      'parameters' => "Paramètres",
      'variables' => "Variables",
      'screens' => "Ecrans",
      'sqlDBs' => "Bases de données",
      'sqlFiles' => "Fichiers de description de BD",
      'htmlFiles' => "Fichiers HTML, JS, CSS, Yaml",
      'tables' => "Tables/Collections",
      'columns' => "Colonnes/Champs",
    ],
    'links' => [
      'includes' => "Inclut les fichiers",
      'uses' => "Utilise",
      'hrefs' => "Référence",
      'database' => "Appartient à la BD",
      'selects' => "Consulte",
      'updates' => "Met à jour",
    ],
    'reverseLinks' => [
      'includes' => "Inclus dans",
      'uses' => "Utilisé par",
      'hrefs' => "Référencé par",
      'database' => "Contient",
      'selects' => "Est consulté par",
      'updates' => "Est mise à jour par",
    ],
  ];
  protected $parent=null; // Elt
  protected $localKey=''; // chaine composée de la catégorie et du num d'objet dans cette catégorie
  protected $properties=[]; // [ name => value ]
  protected $children=[]; // [ category => [ num => child:Elt ] ]
  protected $links=[]; // [ category => [ num => string ] ]
  protected $solvedLinks=[]; // [ category => [ num => Elt ] ]
  protected $reverseLinks=[]; // [ category => [ num => Elt ] ]
  protected $fileNotFound=false; // true ssi le fichier n'existe pas
  
/*PhpDoc: methods
name:  name
title: function name() - Renvoie le nom de l'élément ou s'il n'est pas défini la chaine correspondant à $this
doc: |
  Un module n'a pas de propriété name, une méthode spécifique est définie pour la classe Module
*/
  function name() {
    if (!isset($this->properties['name'])) {
      //return $this;
      //echo "<pre>this="; print_r($this);
      throw new Exception("propriete name non definie dans ".__FILE__.", ligne ".__LINE__);
    } else
      return $this->properties['name'];
  }
  
/*PhpDoc: methods
name:  title
title: function title() - Renvoie le titre de l'élément ou s'il n'est pas défini son nom
*/
  function title() {
    return (!isset($this->properties['title']) ? $this->name() : $this->properties['title']);
  }
  
/*PhpDoc: methods
name:  read_yaml
title: static function read_yaml(string $filename) - Lit un fichier Yaml et retourne son contenu comme tableau Php ou null
doc: |
  Si le fichier n'existe pas alors null est retourné
  Si l'analyse Yaml est incorrecte alors une exception est levée
  Si le fichier est encodé en ISO Latin1 au lieu de UTF-8 une alerte est affichée
*/
  static function read_yaml(string $filename) {
		if (!is_file($filename))
			return null;
    if (!($filecontents = file_get_contents($filename)))
      return null;
    try {
      return Yaml::parse($filecontents);
    }
    catch(ParseException $e) {
      try {
        $yaml = Yaml::parse(utf8_encode($filecontents));
        echo "<b>Attention $filename a été converti en UTF-8</b><br>\n";
        return $yaml;
      }
      catch(ParseException $e) {}
      echo "Erreur d'analyse Yaml dans $filename : ".$e->getMessage()."<br>\n";
      throw new Exception("Erreur Yaml dans $filename");
    }
  }
  
/*PhpDoc: methods
name:  init
title: function init(array $params, array $context) - effectue l'initialisation d'un elt à partir de $params et du contexte
doc: |
  $params est un tableau Php correspondant au contenu du fichier Yaml définissant l'objet
  $context est le contexte d'utilisation
    si $context[verbose] est faux et que l'objet est marqué fileNotFound, cad que le fichier yaml correspondant n'a pas été
    trouvé, alors les sous-objets ne sont pas intégrés dans l'arbre
*/
  function init(array $params, array $context) {
    if (!is_array($params)) {
      echo "<pre>";
      throw new Exception("Erreur dans Elt::init() : params n'est pas un tableau");
    }
    $eltType = get_class($this);
    if (isset($eltType::$structure['properties']))
      foreach ($eltType::$structure['properties'] as $prop=>$type) {
        if (isset($params[$prop])) {
          if (in_array($type,['string','text']))
            $this->properties[$prop] = $params[$prop];
          else
            $this->properties[$prop] = new $type ($params[$prop]);
        }
        unset($params[$prop]);
      }
      
    if (isset($eltType::$structure['childCategories']))
      foreach ($eltType::$structure['childCategories'] as $categoryName=>$class) {
        if (isset($params[$categoryName]) and $params[$categoryName]) {
//          echo "Dans Elt::init($eltType), categoryName=$categoryName<br>\n";
//          var_dump($params[$categoryName]);
          foreach ($params[$categoryName] as $num => $child) {
            $childObject = new $class($child, $context);
            if ($childObject->fileNotFound and !$context['verbose'])
              unset($childObject);
            else {
              $childObject->setParent($this);
              $childObject->setLocalKey("$categoryName-$num");
              $this->children[$categoryName][$num] = $childObject;
            }
          }
        }
        unset($params[$categoryName]);
      }
      
    if (isset($eltType::$structure['links']))
      foreach ($eltType::$structure['links'] as $categoryName) {
//        echo "<pre>categoryName=$categoryName, params="; print_r($params); echo "</pre>\n";
        if (isset($params[$categoryName]) and $params[$categoryName]) {
//          echo "Dans Elt::init($eltType), categoryName=$categoryName<br>\n";
          foreach ($params[$categoryName] as $num => $child) {
            $this->links[$categoryName][$num] = $child;
          }
        }
        unset($params[$categoryName]);
      }
      
    if ($params) {
      echo "Dans Elt::init(eltType=$eltType, name=",$this->name(),") il reste:<br>\n";
//      echo '<ul><li>',implode('<li>',array_keys($params)),"</ul>\n";
				echo "<pre>"; print_r($params); echo "</pre>";
    }
  }
    
/*PhpDoc: methods
name:  setParent
title: function setParent(Elt $parent) - affecte le champ parent
*/
  function setParent(Elt $parent) { $this->parent = $parent; }
  
/*PhpDoc: methods
name:  setLocalKey
title: function setLocalKey(string $localKey) - affecte la clé locale, cad la clé de navigation définie par rapport à son parent
doc: |
  localKey est la clé de l'élément utilisée dans la navigation avec access()
  local signifie que c'est la clé par rapport à son élément parent
*/
  function setLocalKey(string $localKey) { $this->localKey = $localKey; }
  
/*PhpDoc: methods
name:  globalKey
title: function globalKey() - calcule la clé globale, cad la clé absolue de l'élément par rapport à la racine
*/
  function globalKey() {
    return ($this->parent ? $this->parent->globalKey() .'/' : '').$this->localKey;
  }
  
/*PhpDoc: methods
name:  access
title: function access($key) - implémente la navigation dans l'arbre au moyen des clés
doc: |
  Chaque élément est identifié dans l'arbre au moyen de sa clé globale (globalKey)
  access() parcourt l'arbre récursivement et renvoie l'élément identifié par la clé
*/
  function access(array $key) {
//      echo "Elt::access(key="; print_r($key); echo ")<br>\n";
    if (!$key)
      return $this;
    $k0 = array_shift($key);
    $k0 = explode('-',$k0);
    return $this->children[$k0[0]][$k0[1]]->access($key);
  }
  
/*PhpDoc: methods
name:  __toString
title: function __toString() - retourne une chaine courte correspondant à l'élément
doc: |
  Utilisée principalement pour le déverminage
*/
  function __toString() { return get_class($this).': '.$this->name(); }
  
/*PhpDoc: methods
name:  show
title: function show() - affiche l'élément
*/
  function show() {
//    echo "<pre>this="; print_r($this); echo "</pre>\n";
    echo "<h2>",$this->title(),"</h2>\n";
    $eltType = get_class($this);
    echo "<table border=1>\n";
    if ($this->parent)
      echo "<tr><td>parent</td><td><a href='?action=show&amp;key=",urlencode($this->parent->globalKey()),"'>",$this->parent,"</a></td></tr>\n";
    foreach ($this->properties as $propName => $propValue) {
      $type = $eltType::$structure['properties'][$propName];
      echo "<tr><td>$propName</td>",
           "<td>",($type=='text'?'<pre>':''),$propValue,($type=='text'?'</pre>':''),"</td>",
           "</tr>\n";
    }
    echo "</table>\n";
    
    foreach ($this->children as $categoryName => $children) {
      echo "<h3>",self::$titles['childCategories'][$categoryName],"</h3><ul>\n";
      foreach ($children as $child)
        echo "<li>",$child->hreflinktitle('show'),"\n";
      echo "</ul>\n";
    }
    
    foreach ($this->solvedLinks as $categoryName => $solvedLinks) {
      echo "<h3><i>",self::$titles['links'][$categoryName],"</i></h3><ul>\n";
      foreach ($solvedLinks as $link)
        echo "<li>",$link->hreflinktitle('show'),"\n";
      echo "</ul>\n";
    }
    
    foreach ($this->reverseLinks as $categoryName => $reverseLinks) {
      echo "<h3><i>",self::$titles['reverseLinks'][$categoryName],"</i></h3><ul>\n";
      foreach ($reverseLinks as $link)
        echo "<li>",$link->hreflinktitle('show'),"\n";
      echo "</ul>\n";
    }
    
    echo "<a href='?action=dump&amp;key=",urlencode($this->globalKey()),"'>dump</a><br>\n";
  }
  
  function hreflinktitle(string $action) {
    return  "<a href='?action=$action&amp;key=".urlencode($this->globalkey())."'>".$this->title()."</a>";
  }
  function hreflink(string $action) {
    return  "<a href='?action=$action&amp;key=".urlencode($this->globalkey())."'>$this</a>";
  }
  
/*PhpDoc: methods
name:  dump
title: function dump() - affiche l'élément
*/
  function dump() {
    echo "<pre>$this\n";
    if ($this->parent)
      echo "parent: ",$this->parent->hreflink('dump'),"\n";
    echo "localKey: $this->localKey\n";
    echo "properties="; print_r($this->properties);
    echo "links="; print_r($this->links);
    echo "<ul>";
    foreach (['children','solvedLinks','reverseLinks'] as $cat0)
      foreach ($this->$cat0 as $categoryName => $children) {
        echo "<li>$cat0 $categoryName<ul>\n";
        foreach ($children as $child)
          echo '<li>',$child->hreflink('dump'),"\n";
        echo "</ul>\n";
      }
    echo "</ul></pre>\n";
  }

/*PhpDoc: methods
name:  findChildByName
title: function findChildByName(string $name) - retrouve un enfant par son nom
*/
  function findChildByName(string $name) {
    foreach ($this->children as $categoryName => $children)
      if ($categoryName<>'submodules')
        foreach ($children as $child)
          if ($child->properties['name']==$name)
            return $child;
    return null;
  }
  
/*PhpDoc: methods
name:  links
title: function links() - Affichage des liens, utile en dév.
*/
  function links() {
    echo "<li>",$this->title(),"<ul>\n";
    foreach ($this->children as $categoryName => $children)
      foreach ($children as $child)
        $child->links();
    foreach ($this->links as $categoryName => $links)
      foreach ($links as $link)
        echo "<li><b>link $categoryName</b>: $link\n";
    echo "</ul>\n";
  }
  
/*PhpDoc: methods
name:  links
title: function module() - Renvoie le module auquel appartient l'élément
doc: |
  Par défaut le module est le module du père
  Une méthode sur la classe Module renvoie l'objet lui-même
*/
  function module() { return $this->parent->module(); }
/*PhpDoc: methods
name:  file
title: function file() - Renvoie le fichier auquel appartient l'élément
doc: |
  Par défaut le fichier est le fichier du père
  Une méthode sur les classes PhpFile, HtmlFile et SqlFile renvoie l'objet lui-même
*/
  function file() {
    try {
      if (!$this->parent)
        throw new Exception("parent null dans Elt::file() sur $this ligne ".__LINE__." de ".__FILE__);
      return $this->parent->file();
    } catch (Exception $e) {
      throw new Exception("parent null dans Elt::file() sur $this ligne ".__LINE__." de ".__FILE__);
    }
  }
  
/*PhpDoc: methods
name:  solveLink
title: function solveLink(string $categoryName, string $link) - Trouve l'objet correspondant au lien
*/
  function solveLink(string $categoryName, string $link) {
//    $eltType = get_class($this);
//    echo "<li>solveLink(eltType=$eltType,categoryName=$categoryName,link=$link)\n";
    if (strncmp($link,'?',1)<>0) {
      return $this->module()->solveLink($categoryName, $link);
    }
    else {
      if (!($child = $this->file()->findChildByName(substr($link,1))))
        throw new Exception("Lien non trouvé ".__FILE__.", ligne ".__LINE__);
      return $child;
    }
  }
  
/*PhpDoc: methods
name:  solveLinks
title: function solveLinks() - résoud les liens, cad traduit le string en objet
*/
  function solveLinks() {
    foreach ($this->links as $categoryName => $links)
      foreach ($links as $link)
        try {
          $solveLink = $this->solveLink($categoryName, $link);
          $this->solvedLinks[$categoryName][] = $solveLink;
          $solveLink->reverseLinks[$categoryName][] = $this;
        } catch (Exception $e) {
          echo '<b>Attention</b> : ',  $e->getMessage(), "<br>\n";
          echo "&nbsp;&nbsp;&nbsp;dans <a href='?action=show&amp;key=",urlencode($this->globalKey()),"'>",
               $this->title(),"</a><br>\n";
        }
    foreach ($this->children as $categoryName => $children)
      foreach ($children as $child) {
        $child->solveLinks();
      }
  }
  
/*PhpDoc: methods
name:  listCategories
title: function listCategories($level=0, &$categoryNames=[]) - liste les catégories, utile en dév.
*/
  function listCategories($level=0, &$categoryNames=[]) {
//    echo "Elt::listCategories(level=$level) sur $this<br>\n";
    if (!$categoryNames)
      $categoryNames = [
        'childCategories' => [],
        'links' => [],
      ];
      
    foreach ($this->links as $categoryName => $links)
      if (!in_array($categoryName, $categoryNames['links']))
        $categoryNames['links'][] = $categoryName;

    foreach ($this->children as $categoryName => $children) {
      if (!in_array($categoryName, $categoryNames['childCategories']))
        $categoryNames['childCategories'][] = $categoryName;
      foreach ($children as $child)
        $child->listCategories($level+1, $categoryNames);
    }
    if (!$level) {
      echo "<pre>"; print_r($categoryNames); echo "</pre>\n";
    }
    return $categoryNames;
  }
};