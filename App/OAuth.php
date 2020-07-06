<?php
require_once 'Config/config.php';
class OAuth
{
    /*
     * Запрос авторизации( https://www.zoho.com/crm/developer/docs/api/auth-request.html )
     * И первое получение токена(access_token) производится единожды, поэтому я не стал прописывать лишние методы для них.
     *
     * Запрос авторизации производится GET-запросом, по следующему URL:
     * https://accounts.zoho.com/oauth/v2/auth?scope=ZohoCRM.users.ALL&client_id={client_id}&response_type=code&access_type=offline&redirect_uri={redirect_uri}
     *
     * В результате этого запроса мы получаем: code и Accounts_URL необходимые для получения первого токена
     *
     * Первый токен(access_token) получается POST-запросом, со следующими параметрами:
     * - URL: {Accounts_URL}/oauth/v2/token
     * - grant_type: "authorization_code"
     * - client_id - id клиента из Zoho API Console
     * - client_secret - секретный ключ для обращения к Zoho API из Zoho API Console
     * - redirect_uri - URI переадресации из Zoho API Console
     * - code - получен при предыдущем запросе(запрос авторизации).
     *
     * После получения первого токена(access_token) мы также получаем токен обновления(refresh_token), с помощью которого можем получать новый access_token
     * Обновлять access_token необходимо, так как он действует 1 час.
     */


    public function refreshToken(){

        $url = 'https://accounts.zoho.com/oauth/v2/token';

        // Формируем тело запроса
        $data = array(
            'refresh_token' => REFRESH_TOKEN,
            'grant_type' => 'refresh_token',
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'redirect_uri' => REDIRECT_URI
        );

        // Формируем параметры запроса
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);

        // Выполняем запрос и декодируем полученный результат в массив
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result, true);

        // Из полученного массива берем новый access_token.
        $access_token = $result['access_token'];
        file_put_contents('access_token.txt', $access_token);
    }
}