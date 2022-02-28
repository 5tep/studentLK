<?php

function get_token(){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'http://10.32.40.18:8080/auth/realms/master/protocol/openid-connect/token',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => 'grant_type=password&username=admin&password=Admin%40123&client_id=admin-cli',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
            ),
    ));

    $response = json_decode(curl_exec($curl));
    
    curl_close($curl);
    return $response->{'access_token'};
}

function get_list_users(){
    $auth = 'bGthc3U6VHkwZG82ZG8=';
    $path = 'http://10.32.8.103/univer_test_221/ws/Study.1cws';
    $request = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:stud="http://sgu-infocom.ru/study">
        <soap:Header/>
            <soap:Body>
                <stud:GetUsers/>
            </soap:Body>
        </soap:Envelope>';

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $path,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $request,
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic '.$auth,
        'Content-Type: application/xml'
        ),
    ));

    $response = curl_exec($curl);
    
    $DOMxml = new DOMDocument();
    $DOMxml->loadXML($response);
    $result = $DOMxml->getElementsByTagName("Envelope")->item(0)->getElementsByTagName("Body")->item(0)->getElementsByTagName("GetUsersResponse")->item(0)->getElementsByTagName("return")->item(0)->getElementsByTagName("User");
    curl_close($curl);
    return $result;
}

function find_user($user_name, $token){
    $path = 'http://10.32.40.18:8080/auth/admin/realms/PortalSEVSU/users?username=' . $user_name;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $path,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '. $token
        ),
    ));

    $response = json_decode(curl_exec($curl));
    if($response->{'error'}) return '';
    curl_close($curl);
//    print_r($response);
    return $response[0]->{'id'};
}

function get_id_role($role_name, $token){
    $path = 'http://10.32.40.18:8080/auth/admin/realms/PortalSEVSU/roles/' . $role_name;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $path,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '. $token
        ),
    ));

    $response = json_decode(curl_exec($curl));

    curl_close($curl);
//    print_r($response);
    return $response->{'id'};
}


function get_id_role2($role_name, $token){
    $path = 'http://10.32.40.18:8080/auth/admin/realms/PortalSEVSU/roles/' . $role_name;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $path,
      CURLOPT_RETURNTRANSFE => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $token
        ),
    ));

    $response = json_decode(curl_exec($curl));
//    echo $token;
    curl_close($curl);
    print_r($response);    
    return $response->{'id'};

}

function set_role_user($role_id, $role_name, $user_id, $token){
    $path = 'http://10.32.40.18:8080/auth/admin/realms/PortalSEVSU/users/' . $user_id .'/role-mappings/realm';
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $path,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'[
            {
                "id": "' . $role_id . '",
                "name": "' . $role_name .'"
            }
        ]',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($curl);
//    echo $http_code . ': ' .$role_name .  ' (' . $role_id . ')</br>';
    return $http_code;

}

$users_1c = get_list_users();
$i = 0;

foreach ($users_1c as $user) {
    if ($user->getElementsByTagName("Roles")->length>0){
        $i++;
        $UserId = $user->getElementsByTagName("UserId")->item(0)->nodeValue;
        if ($i > 10000) break;
        foreach($user->getElementsByTagName("Roles") as $role){
            $role_name =  $role->getElementsByTagName("Role")->item(0)->nodeValue;
            $token = get_token();
//            echo $role_name;
            $id_role = get_id_role($role_name, $token);
            $id_user = find_user($UserId, $token);
            //echo $role_name . $id_role . $id_user;
            set_role_user($id_role, $role_name, $id_user, $token);
        }
    }
    else {  $info = 'Not role!';}

} echo "DONE! ". $i . "roles reloaded.";
