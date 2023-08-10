<html>
<head>
<title>Mailing Labels</title>
<meta name="description" content="WildApricot API Call to create a printout of mailing labels from your membership database.">
<meta name="keywords" content="WildApricot, API, member search">
<meta name="author" content="Don Harris / dharris@sudoers.com / DonaldTHarris@gmail.com">
</head>
<body>
 <form action="mailingLabel.php" method="post">
   Name: <input type="text" name="Name"><br><br>
   <input type="submit">
 </form><br>
      <?php

       /**
       * API helper class. You can copy whole class in your PHP application.
       * See: https://gethelp.wildapricot.com/en/articles/485-sample-api-applications#php
       * Also: https://github.com/WildApricot/ApiSamples/blob/master/PHP/sampleApplication.php
       */
       
       class WaApiClient
          {
             const AUTH_URL = 'https://oauth.wildapricot.org/auth/token';
      
             private $tokenScope = 'auto';
      
             private static $_instance;
             private $token;
      
             public function initTokenByContactCredentials($userName, $password, $scope = null)
             {
                if ($scope) {
                   $this->tokenScope = $scope;
                }
      
                $this->token = $this->getAuthTokenByAdminCredentials($userName, $password);
                if (!$this->token) {
                   throw new Exception('Unable to get authorization token.');
                }
             }
      
             public function initTokenByApiKey($apiKey, $scope = null)
             {
                if ($scope) {
                   $this->tokenScope = $scope;
                }
      
                $this->token = $this->getAuthTokenByApiKey($apiKey);
                if (!$this->token) {
                   throw new Exception('Unable to get authorization token.');
                }
             }
      
             // this function makes authenticated request to API
             // -----------------------
             // $url is an absolute URL
             // $verb is an optional parameter.
             // Use 'GET' to retrieve data,
             //     'POST' to create new record
             //     'PUT' to update existing record
             //     'DELETE' to remove record
             // $data is an optional parameter - data to sent to server. Pass this parameter with 'POST' or 'PUT' requests.
             // ------------------------
             // returns object decoded from response json
      
             public function makeRequest($url, $verb = 'GET', $data = null)
             {
                if (!$this->token) {
                   throw new Exception('Access token is not initialized. Call initTokenByApiKey or initTokenByContactCredentials before performing requests.');
                }
      
                $ch = curl_init();
                $headers = array(
                   'Authorization: Bearer ' . $this->token,
                   'Content-Type: application/json'
                );
                curl_setopt($ch, CURLOPT_URL, $url);
      
                if ($data) {
                   $jsonData = json_encode($data);
                   curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                   $headers = array_merge($headers, array('Content-Length: '.strlen($jsonData)));
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
      
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $jsonResult = curl_exec($ch);
                if ($jsonResult === false) {
                   throw new Exception(curl_errno($ch) . ': ' . curl_error($ch));
                }
      
                // var_dump($jsonResult); // Uncomment line to debug response
      
                curl_close($ch);
                return json_decode($jsonResult, true);
             }
      
             private function getAuthTokenByAdminCredentials($login, $password)
             {
                if ($login == '') {
                   throw new Exception('login is empty');
                }
      
                $data = sprintf("grant_type=%s&username=%s&password=%s&scope=%s", 'password', urlencode($login), urlencode($password), urlencode($this->tokenScope));
      
                throw new Exception('Change clientId and clientSecret to values specific for your authorized application. For details see:  https://help.wildapricot.com/display/DOC/Authorizing+external+applications');
                $clientId = 'SamplePhpApplication';
                $clientSecret = 'open_wa_api_client';
                $authorizationHeader = "Authorization: Basic " . base64_encode( $clientId . ":" . $clientSecret);
      
                return $this->getAuthToken($data, $authorizationHeader);
             }
      
             private function getAuthTokenByApiKey($apiKey)
             {
                $data = sprintf("grant_type=%s&scope=%s", 'client_credentials', $this->tokenScope);
                $authorizationHeader = "Authorization: Basic " . base64_encode("APIKEY:" . $apiKey);
                return $this->getAuthToken($data, $authorizationHeader);
             }
      
             private function getAuthToken($data, $authorizationHeader)
             {
                $ch = curl_init();
                $headers = array(
                   $authorizationHeader,
                   'Content-Length: ' . strlen($data)
                );
                curl_setopt($ch, CURLOPT_URL, WaApiClient::AUTH_URL);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              $response = curl_exec($ch);
              if ($response === false) {
                   throw new Exception(curl_errno($ch) . ': ' . curl_error($ch));
                }
                // var_dump($response); // Uncomment line to debug response
      
                $result = json_decode($response , true);
                curl_close($ch);
                return $result['access_token'];
             }
      
             public static function getInstance()
             {
                if (!is_object(self::$_instance)) {
                   self::$_instance = new self();
                }
      
                return self::$_instance;
             }
      
             public final function __clone()
             {
                throw new Exception('It\'s impossible to clone singleton "' . __CLASS__ . '"!');
             }
      
             private function __construct()
             {
                if (!extension_loaded('curl')) {
                   throw new Exception('cURL library is not loaded');
                }
             }
      
             public function __destruct()
             {
                $this->token = null;
             }
          }
       if(!empty($_POST))
       {
          $waApiClient = WaApiClient::getInstance();
          $waApiClient->initTokenByApiKey('ENTER_YOUR_API_KEY_HERE');

          $postNameArr = $_POST;
          $name=$postNameArr["Name"];
         
          
          function getAccountDetails()
          {
             global $waApiClient;
             $url = 'https://api.wildapricot.org/v2/Accounts/';
             $response = $waApiClient->makeRequest($url);
             return  $response[0]; // usually you have access to one account
          }

          $account = getAccountDetails();
          $accountUrl = $account['Url'];

          function findMemberInfo()
          {
             global $waApiClient;
             global $accountUrl;
             global $name;
             $queryParams = array(
                '$async' => 'false', // execute request synchronously
                '$filter' => 'Member eq true', // filter only members
                '$filter' => 'LastName eq '.$name, // filter on LastName
                //'$select' => 'FirstName,LastName,Email,StreetAddress',
             );
             $url = $accountUrl . '/Contacts/?' . http_build_query($queryParams);
             return $waApiClient->makeRequest($url);
          }

           $memberInfoResult = findMemberInfo();
      
             foreach ($memberInfoResult['Contacts'] as $newContact) {
               echo sprintf('Member Number:'." %s", $newContact['Id']);
               echo '<br /><br />';
               echo sprintf("%s %s",$newContact['FirstName'], $newContact['LastName']); 
               echo '<br />';
               echo sprintf("%s",$newContact['FieldValues'][24]['Value']);
               echo '<br />';
               if ($newContact['FieldValues'][25]['Value']) {
                   echo sprintf("%s",$newContact['FieldValues'][25]['Value']);
                   echo '<br />';
                }
               echo sprintf("%s, %s  %s",$newContact['FieldValues'][26]['Value'], $newContact['FieldValues'][27]['Value'], $newContact['FieldValues'][28]['Value']);
                 echo '<br />';
               echo '<br><br><hr><br>';
             }

         }
         else {
            print("Please enter the Last Name.");
         }
      ?>
</body>
</html>
