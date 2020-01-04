<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

use App\Services\LanguagePack;

$user_LanguagePack = new LanguagePack('english');


echo __('Тест %s =)', 'не переводимый текст'); // Test (не переводимый текст) =)
echo __('Это нужно перевести');