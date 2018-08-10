<?php
// fichier de test pour calltab.php
function fff() {
}

class ccc {
  static function sss () {
    fff();
  }
};

function ggg() {
  ccc::sss();
}

fff();
ccc::sss();
ggg();
