<?php
class Leads
{

    // Метод отправки запроса в Zoho CRM
    public function sendRequest(string $url, string $method, string $data = null){
        $access_token = file_get_contents('access_token.txt');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Zoho-oauthtoken ' . $access_token,
            'Content-Type: application/json'
        ));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, $method);
        if($method == 'POST'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $response = json_encode(json_decode(curl_exec($curl)));
        curl_close($curl);

        return $response;
    }

    // Метод создания нового лида
    public function addNewLead(string $name,string $phone, string $email, string $site, string $city){

        // Формируем тело запроса
        $data = json_encode(array(
           'data' => array(
               array(
                   'Last_Name' => $name,
                   'Phone' => $phone,
                   'Email' => $email,
                   'Website' => $site,
                   'City' => $city,
               )
           )
        ));

        // Через метод отправки запроса, выполняем запрос к API Zoho на добавление нового лида
        $addLead = $this->sendRequest("https://www.zohoapis.com/crm/v2/Leads", "POST", $data);
        $addLead = json_decode($addLead, true);

        // Получаем ID, созданного лида
        $leadID = $addLead['data']['0']['details']['id'];

        return $leadID;
    }

    // Метод поиска лида по номеру телефона
    public function findLead(string $phone){

        $find = $this->sendRequest("https://www.zohoapis.com/crm/v2/Leads/search?criteria=Phone:equals:$phone", 'GET');
        $find = json_decode($find, true);

        if ($find != null){
            $findLeadID = $find['data']['0']['id'];
            echo '$findLeadID = ';

            return $findLeadID;
        }else{
            return false;
        }
    }

    // Метод поиска контакта по номеру телефона (используется в методе addNewContact)
    public function findContact(string $phone){

        $find = $this->sendRequest("https://www.zohoapis.com/crm/v2/Contacts/search?criteria=Phone:equals:$phone", 'GET');
        $find = json_decode($find, true);

        if ($find != null){
            $contactID = $find['data']['0']['id'];
            return $contactID; // Если контакт с таким номером найден, возвращает его ID
        }else{
            return false; // Если не найден, возвращает false
        }

    }

    // Метод создания нового контакта
    public function addNewContact(string $name, string $phone, string $email){

        $findContact = $this->findContact($phone); // Проверяем, что контакта с таким номером телефона еще нет

        if ($findContact != false){
            $contactID = $findContact;
            return $contactID; // Если контакт с таким номером найден, возвращает его ID
        }else{
            // Если нет, формируется запрос на создание нового контакта
            $data = json_encode(array(
                    'data'=>array(
                        array(
                            'Last_Name' => $name,
                            'Phone' => $phone,
                            'Email' => $email,
                        )
                    )
                )
            );

            $addContact = $this->sendRequest("https://www.zohoapis.com/crm/v2/Contacts", 'POST', $data);
            $addContact = json_decode($addContact, true);
            $contactID = $addContact['data']['0']['details']['id'];

            return $contactID; // После выполнения запроса возвращает ID, созданного контакта
        }


    }

    // Метод для конвертации лида в сделку + контакт
    public function convertLead(string $leadID, string $name, string $phone, string $email)
    {
        $addContact = $this->addNewContact($name, $phone, $email); // Создаем контакт или получаем ID уже существующего

        $data = json_encode(array(
            'data'=>array(
                array(
                    'overwrite' => true,
                    'Account' => "",
                    'Contact' => $addContact,
                    "Deals" => array(
                        "Deal_Name" => "Заявка с сайта " . $_SERVER['HTTP_REFERER'],
                        "Closing_Date" => date("Y-m-d"),
                        "Stage" => "Закрытые успешно",
                        "Amount" => 10000
                    )
                )
            )
        ));

        $convert = $this->sendRequest("https://www.zohoapis.com/crm/v2/Leads/$leadID/actions/convert", "POST", $data);
        $convert = json_decode($convert, true);
        $dealID = $convert['data']['0']['Deals'];

        return $dealID; // Возвращает ID, созданной сделки

    }
}