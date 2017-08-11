<?php
/*PhpDoc:
name:  phpfile.inc.php
title: phpfile.inc.php - définition des classes PhpFile, FunClassVar, Method, Parameter et Type
classes:
journal: |
  9/8/2017:
    ajout de la possibilité de documenter une propriété privée d'une classe
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  30/11-1/12/2016:
    ajout de verifyInc()
  26-27/11/2016:
    première version
*/
/*PhpDoc: classes
name:  class PhpFile
title: class PhpFile extends File - classe correspondant aux fichiers Php et fichiers inclus .inc.php
methods:
doc: |
*/
class PhpFile extends File {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'childCategories'=>[ // liste des catégories d'enfants avec la classe Php associée
      'screens'=>'Screen',
      'functions'=>'FunClassVar',
      'classes'=>'FunClassVar',
      'variables'=>'FunClassVar',
      'tables'=>'SqlTable', // La table SQL est créée par un script Php
    ],
    'links'=>[
      'includes',// fichiers Php inclus
      'uses',    // utilisation d'une fonction, d'une classe, d'une méthode ou d'une variable
      'hrefs',   // hrefs définis dans le Html de sortie
      'selects', // tables consultées
      'updates', // tables mises à jour
    ],
  ];
  
/*PhpDoc: methods
name: verifyInc
title: private function filepathFromTokens(array $tokens, integer $indice) - renvoie le nom du fichier inclus
doc: |
  Utilisée par verifyInc()
*/
  private function filepathFromTokens(array $tokens, $indice) {
/*
    echo "<pre>token T_REQUIRE ou T_REQUIRE_ONCE détecté:\n";
    for($j=1;$j<10;$j++) {
      if (is_array($tokens[$indice+$j]))
        echo "tokens[indice+$j] : ",token_name($tokens[$indice+$j][0])," (",$tokens[$indice+$j][1],")\n";
      else
        echo "tokens[indice+$j] : ",$tokens[$indice+$j],"\n";
    }
    echo "</pre>\n";
*/
    $indice++;
    $token = $tokens[$indice];
    if (is_array($token) and ($token[0]==T_WHITESPACE)) {
      $indice++;
      $token = $tokens[$indice];
    }

    if (is_array($token) and ($token[0]==T_CONSTANT_ENCAPSED_STRING)) {
      $name = $tokens[$indice][1];
      $name = substr($name, 1, strlen($name)-2);
//      echo "name=$name<br>\n";
      return $name;
    } elseif (
// Cas require_once dirname(__FILE__).'/../spyc/spyc.inc.php';
          (is_array($tokens[$indice]) and ($tokens[$indice][0]==T_STRING) and ($tokens[$indice][1]=='dirname'))
          and (!is_array($tokens[$indice+1]) and ($tokens[$indice+1]=='('))
          and (is_array($tokens[$indice+2]) and ($tokens[$indice+2][0]==T_FILE) and ($tokens[$indice+2][1]=='__FILE__'))
          and (!is_array($tokens[$indice+3]) and ($tokens[$indice+3]==')'))
          and (!is_array($tokens[$indice+4]) and ($tokens[$indice+4]=='.'))
          and (is_array($tokens[$indice+5]) and ($tokens[$indice+5][0]==T_CONSTANT_ENCAPSED_STRING))
        )
    {
//      echo "Cas en cours ligne ".__LINE__."<br>\n";
      $name = $tokens[$indice+5][1];
      $name = substr($name, 2, strlen($name)-3);
//      echo "name=$name<br>\n";
      return $name;
    } else {
//      echo "<pre>";
      throw new Exception("Cas non prévu ligne ".__LINE__." du fichier ".__FILE__);
    }
  }
  
/*PhpDoc: methods
name: verifyInc
title: function verifyInc(string $dirpath)
doc: |
  Vérifie que les fichiers inclus correspondent bien à ceux présents dans le code Php
  La détection n'est pas parfaite car cela serait trop complexe
  Notamment les xpath avec /* génèrent des faux débuts de commentaire
*/
  function verifyInc(string $dirpath) {
    echo "<h3>$dirpath/",$this->properties['name'],' : ',$this->properties['title'],"</h3>\n";
    $path = "../$dirpath/".$this->properties['name'];
    if (!file_exists($path)) {
      echo "Le script ou fichier inclus n'existe pas.<br>\n";
      return;
    }
    
// fabrication de la liste des fichiers inclus issue du code Php
    $incfiles = []; // liste des fichiers inclus trouvés dans le code Php
    $tokens = @token_get_all(file_get_contents($path));
    foreach ($tokens as $i => $token)
      if (is_array($token) and (($token[0]==T_REQUIRE) or ($token[0]==T_REQUIRE_ONCE)))
        try {
          $filename = $this->filepathFromTokens($tokens, $i);
          if (!in_array($filename, $incfiles))
            $incfiles[] = $filename;
        } catch(Exception $e) {
          echo "<b>Erreur: ",$e->getMessage(),"</b><br>\n";
        }
//    echo "<pre>incfiles="; print_r($incfiles); echo "</pre>"; //die();
      
//    $this->dump();
// liste des fichiers inclus issue de PhpDoc
    $includes = [];
    if (isset($this->solvedLinks['includes']))
      foreach ($this->links['includes'] as $include)
        $includes[$include] = 1;
//    echo "<pre>includes="; print_r($includes); echo "</pre>";
      
// Confrontation de la liste issue du code Php avec la liste issue de PhpDoc
    foreach ($incfiles as $incfile) {
      if (isset($includes[$incfile])) {
        echo "$incfile OK<br>\n";
        unset($includes[$incfile]);
      } else
         echo "<b>$incfile dans le code Php mais absent de PhpDoc</b><br>\n";
    }
    if ($includes) {
      echo "<br><u>Fichiers documentés dans PhpDoc mais non trouvés dans le code Php:</u><br>\n";
      foreach(array_keys($includes) as $include)
        echo "$include<br>\n";
    }
  }
};

/*PhpDoc: classes
name:  class FunClassVar
title: class FunClassVar extends InFile - classe correspondant aux functions, aux classes et aux variables
*/
class FunClassVar extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'resultType' => 'Type', // le type du résultat d'une fonction
      'type' => 'Type',       // Le type d'une variable
      'lastUpdate' => 'string',
      'doc' => 'text',
      'journal' => 'text',
    ],
    'childCategories'=>[ // liste des catégories d'enfants avec la classe Php associée
      'methods'=>'Method', // Les méthodes d'une classe
      'parameters'=>'Parameter', // Les paramètres d'une fonction
      'pubproperties'=>'FunClassVar', // Les propriéts publiques d'une classe
      'privateproperties'=>'FunClassVar', // Les propriéts privées d'une classe
    ],
    'links'=>[
      'uses',  // Une fonction ou une classe peut réutiliser une fonction, une classe ou une méthode
      'selects', // consulte une table
      'updates', // met à jour une table
    ],
  ];
};

/*PhpDoc: classes
name:  class Method
title: class Method extends InFile - classe correspondant aux méthodes
*/
class Method extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'resultType' => 'Type', // le type du résultat d'une méthode
      'doc' => 'text',
      'journal' => 'text',
    ],
    'childCategories'=>[ // liste des catégories d'enfants avec la classe Php associée
      'parameters'=>'Parameter', // Les paramètres d'une méthode
    ],
    'links'=>[
      'uses',  // Une méthode peut en réutiliser une fonction, une classe ou une autre méthode
      'selects', // consulte une table
      'updates', // met à jour une table
    ],
  ];
};

/*PhpDoc: classes
name:  class Parameter
title: class Parameter extends InFile - classe correspondant aux paramètres d'une fonction ou d'une méthode
*/
class Parameter extends InFile {
  static $structure = [
    'properties'=>[
      'name' => 'string',
      'title' => 'string',
      'type' => 'Type',
      'doc' => 'text',
      'journal' => 'text',
    ],
  ];
};

/*PhpDoc: classes
name:  class Type
title: class Type - définition d'un type
methods:
doc: La classe Type n'appartient pas à la hiérarchie des autres types car les types sont des propriétés et non des enfants
*/
class Type {
  protected $def; // définition du type sous la forme d'une valeur Php
  
/*PhpDoc: methods
name:  __construct
title: function __construct($param) - création d'un type
*/
  function __construct($param) {
    if (isset($_GET['debug']) and $_GET['debug']) {
      $eltType = get_class($this);
      echo "Appel de $eltType::__construct() avec param=<pre>"; var_dump($param); echo "</pre>\n";
    }
    $this->def = $param;
  }
  
/*PhpDoc: methods
name:  toString
title: function toString($def, integer $level=0) - affichage du type
*/
  function toString($def, int $level=0) {
    if (!is_array($def))
      return $def;
    $keys = array_keys($def);
    if ($keys[0]===0)
      return '['.$this->toString($def[0], $level+1).']';
    $result = '';
    $sep = (count($keys)>1 ? "'" : '"');
    foreach ($def as $key=>$value)
      $result .= (!$result?"[\n":",\n").str_repeat('  ',$level+1).$sep.$key.$sep.'=>'.$this->toString($value, $level+1);
    return $result."\n".str_repeat('  ',$level)."]";
  }
  
/*PhpDoc: methods
name:  __toString
title: function __toString() - affichage du type, appele toString()
*/
  function __toString() { return '<pre>'.$this->toString($this->def).'</pre>'; }
};

