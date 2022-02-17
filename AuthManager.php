<?php

namespace common\modules\student\components;

use common\models\Rolerule;
use common\models\User;
use Yii;
use yii\httpclient\Client; /* add sadurmanov 16.02.2022 */

class AuthManager extends \yii\base\Component
{
    public $login;
    public $password;

    public $serviceUrl;

    protected $client;

    public function checkCredentials($login, $password)
    {
        /* add sadurmanov 16-17.02.2022 */
        $auth_type = '1C';
        $auth_type = 'ldap';
        if ($auth_type == 'ldap') {
            $ADauth = new Client(['baseUrl' => 'http://10.32.40.18:8080/auth']);
            $response = $ADauth->createRequest()
                ->setMethod('post')
                ->setUrl('/realms/PortalSEVSU/protocol/openid-connect/token')
                ->setData(['grant_type' => 'password', 'username' => $login, 'password' => $password, 'client_id' => 'lk-sevsu', 'client_secret' => 'jPZPsFE21Vsx57yAVOBMfzcTHcNXBMf8'])
                ->send();
            if ($response->isOk) {
                $access_token = $response->data['access_token'];
                $get_user = $ADauth->createRequest()
                    ->setFormat(Client::FORMAT_JSON)
                    ->setMethod('get')
                    ->setUrl('/realms/PortalSEVSU/protocol/openid-connect/userinfo')
                    ->addHeaders(['Authorization' => 'Bearer ' . $access_token])
                    ->send();
                    if ($get_user->isOk) {
                        $fio = $get_user->data['family_name'] . ' ' . $get_user->data['given_name'];
                        $email = $get_user->data['email'];
                        //$LDAP_ENTRY_DN = $get_user->data['attributes']['LDAP_ENTRY_DN'];
                        (preg_match('/\d\d-\d\d\d\d\d\d/', $login)) ? $UserId = substr($login, -9) : $UserId = $login;
                        //$UserId = '00-054216';
                        $response = Yii::$app->soapClientStudent->load("GetUsers");
                        foreach ($response->return->User as $user) {
                            if ($user->UserId == $UserId) {
                                $roles = $user->Roles;
                                $data =  $user;
                                break;                
                            }    
                        }
                        //if(!isset($data)) return null;
                    }
                    if(isset($roles)) $this->setRoles($roles, $UserId);
                
            return $data;
            } 
            else {
                return null;
                }
        } 
        else {
            /* end */
        $response = Yii::$app->soapClientStudent->load("Authorization",
            [
                'UserId' => '',
                'Login' => $login,
                'PasswordHash' => sha1($password)
            ]
        );

        if (isset($response->return->User) && $response->return->User != null) {
            if (!isset($response->return->User->Roles)) {
                $response->return->User->Roles = [];
                //return null;
            } elseif (!is_array($response->return->User->Roles)) {
                $response->return->User->Roles = [$response->return->User->Roles];
            }

            $data = $response->return->User;
            $this->setRoles($data->Roles, $data->UserId);
            return $data;
        }

          return null;
    }
    }

    protected function setRoles($roles, $user_id)
    {
        $roles_array = [];

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            array_push($roles_array, $role->Role);
        }

        if (!in_array("Abiturient", $roles_array)) {
            array_push($roles_array, "Abiturient");
        }

        Yii::$app->session->set('_' . $user_id, $roles_array);
    }

    public function getRoles($user_id)
    {
        if (Yii::$app->session->has('_' . $user_id)) {
            $roles = Yii::$app->session->get('_' . $user_id);
            //фильтруем роли == null
            $roles = array_filter($roles, function ($item) {
                return !is_null($item);
            });
            return array_values(array_unique($roles));
        }

        return null;
    }

    public function getAllowedRoles($user_id)
    {
        $user = User::findOne($user_id);
        if (empty($user)) {
            return [];
        }
        $roles = ($this->getRoles($user->guid) ?? []);

        if (!empty(Yii::$app->db->getTableSchema('rolerule'))) {
            $_rolerule = Rolerule::find()->one();
            $rolerule = [];
            if ($_rolerule->student == 0) {
                $rolerule[] = 'Student';
            };
            if ($_rolerule->teacher == 0) {
                $rolerule[] = 'Teacher';
            };
            if ($_rolerule->abiturient == 0) {
                $rolerule[] = 'Abiturient';
            };
            if ($_rolerule->curator == 0) {
                $rolerule[] = 'Curator';
            };
            $roleReturn = [];
            for ($i = 0; $i < count($roles); $i++) {
                $InBlackList = false;
                foreach ($rolerule as $rule) {
                    if (isset($roles[$i]) && $roles[$i] === $rule) {
                        $InBlackList = true;
                    }
                }
                if (!$InBlackList) {
                    $roleReturn[] = $roles[$i];
                }
            }
            return $roleReturn;
        }
        return [];
    }

    protected function buildUrl()
    {

        if (substr($this->serviceUrl, -1) != '/') {
            $urlTemplate = $this->serviceUrl . '/';
        } else {
            $urlTemplate = $this->serviceUrl;
        }

        $url = $urlTemplate;


        return $url;
    }

    protected function BuildUserArrayFromXML($data, $login, $hash)
    {
        $xml_response = simplexml_load_string($data);

        if ($xml_response->getName() == 'error') {
            $log = [
                'data' => [
                    'url' => $this->buildUrl(),
                    'login' => $login,
                    'hash' => $hash,
                ],
                'response_content' => $data,
            ];
            Yii::error('Ошибка получения данных о пользователе: ' . PHP_EOL . print_r($log, true));
            return null;
        }
        $user_array = [];
        $user_array['guid'] = (string)$xml_response->id;
        $user_array['username'] = (string)$xml_response->name;
        $user_array['password'] = (string)$xml_response->password;
        $roles = [];
        foreach ($xml_response->roles->role as $role) {
            $roles[] = (string)$role;
        }

        $user_array['roles'] = $roles;
        $regnums = [];
        foreach ($xml_response->recordbooks->recordbook as $recordbook) {
            $regnums[] = (string)$recordbook;
        }
        $user_array['reg_numbers'] = $regnums;

        return $user_array;
    }

    protected function BuildRoles($data, $user_id)
    {
        $xml_response = simplexml_load_string($data);

        if ($xml_response->getName() == 'error') {
            $log = [
                'data' => [
                    'url' => $this->buildUrl(),
                    'user_id' => $user_id,
                ],
                'response_content' => $data,
            ];
            Yii::error('Ошибка получения ролей пользователя: ' . PHP_EOL . print_r($log, true));
            return null;
        }

        $roles = [];
        foreach ($xml_response->roles->role as $role) {
            $roles[] = (string)$role;
        }

        return $roles;
    }
}
