<?php
// Устанавливаем кодировку UTF-8
error_reporting(-1);
header('Content-Type: text/html; charset=utf-8');

// Получаем данные из формы
$name = $_POST['name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$site = $_POST['site'];
$city = $_POST['city'];

//Получаем токен авторизации для обращения к API Zoho CRM
require_once 'OAuth.php';
$OAuth = new OAuth();
$OAuth->refreshToken();

require_once 'Leads.php';
$Leads = new Leads();

$findLead = $Leads->findLead($phone); // Проверяем наличие лидов с таким же номером телефона
if ($findLead != false){
    $Leads->convertLead($findLead, $name, $phone, $email); // Если лид найден, вызываем метод конвертации его в сделку с привязанным к ней контактом
}else{
    $Leads->addNewLead($name, $phone, $email, $site, $city); // Если лид не найден, создаем новый
}
