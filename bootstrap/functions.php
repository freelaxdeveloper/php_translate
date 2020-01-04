<?php

define('H', $_SERVER ['DOCUMENT_ROOT']);
define('TMP', H . '/storage/tmp');


function passgen($len = 32) {
  $password = '';
  $small = 'abcdefghijklmnopqrstuvwxyz';
  $large = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $numbers = '1234567890';
  for ($i = 0; $i < $len; $i++) {
      switch (mt_rand(1, 3)) {
          case 3 :
              $password .= $large [mt_rand(0, 25)];
              break;
          case 2 :
              $password .= $small [mt_rand(0, 25)];
              break;
          case 1 :
              $password .= $numbers [mt_rand(0, 9)];
              break;
      }
  }
  return $password;
}

function __() {
  $args = func_get_args();
  $args_num = count($args);
  if (!$args_num) {
      // нет ни строки ни параметров, вообще нихрена
      return '';
  }

  global $user_LanguagePack;
  $string = $user_LanguagePack->getString($args[0]);

  if ($args_num == 1) {
      // строка без параметров
      return $string;
  }

// строка с параметрами
  $args4eval = array();
  for ($i = 1; $i < $args_num; $i++) {
      $args4eval[] = '$args[' . $i . ']';
  }
  return eval('return sprintf($string,' . implode(',', $args4eval) . ');');
}
