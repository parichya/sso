<?php

namespace app\model {

    use app\service\ParichyaClient;
    use app\service\R;

    class ParichyaUser extends AbstractUser
    {

        public static $MOBILE_AUTH = false;
        public static $GOOGLE_AUTH = true;
        public static $TWITTER_AUTH = true;
        public static $EMAIL_AUTH = true;
        public static $DIRECT_LOGIN = true;
        public static $RETURN_URL = "/";

        public $provide = null;
        public $access_token = null;
        public $authdata = null;

        public function configure()
        {
            self::$MOBILE_AUTH = false;
            self::$GOOGLE_AUTH = true;
            self::$RETURN_URL = "/";
        }

        public function on_auth_success($user)
        {
            if ($user->admin == 1) {
                $this->role = "ADMIN";
            }
        }

        public function auth($username, $passowrd)
        {
            return true;
        }

        public function setAccessToken($provide, $access_token)
        {
            $this->provide = $provide;
            $this->access_token = $access_token;
            $ostData = array(
                "broker_id" => ParichyaClient::$OTP_BROKER_ID,
                "broker_secret" => ParichyaClient::$OTP_BROKER_SECRET,
                "access_token" => $access_token
            );
            $authdata = json_decode(ParichyaClient::api("POST",
                ParichyaClient::$OTP_SERVER .
                sprintf("/api/auth/social/%s", $provide), $ostData
            ));
            return $this->setAuthData($authdata);

        }

        public function getAuthData()
        {
            return $this->authdata;
        }

        public function setAuthData($authdata)
        {
            $this->authdata = $authdata;
            if (!is_null($authdata) && $authdata->success) {
                $user = R::findOne("user", "parichyacode = ?", array(
                    $authdata->parichyacode
                ));
                if (is_null($user)) {
                    $user = R::dispense("user");
                    $user->parichyacode = $authdata->parichyacode;
                    $user->admin = 0;
                }
                $user->penname = $authdata->penname;
                $user->email = $authdata->email;
                $user->name = $authdata->name;
                $user->admin = $user->admin;
                $user->id = R::store($user);
                $this->on_auth_success($user);
                return $this->setUser($user->id, $user->email, (array)$authdata);
            }
        }

        public function basicAuth()
        {
            $this->configure();
            $authdata = ParichyaClient::authenticate(array(
                "mobile_auth" => self::$MOBILE_AUTH,
                "google_auth" => self::$GOOGLE_AUTH,
                "twitter_auth" => self::$TWITTER_AUTH,
                "email_auth" => self::$EMAIL_AUTH,
                "direct_login" => self::$DIRECT_LOGIN
            ));
            return $this->setAuthData($authdata);
        }

        public function unauth()
        {
            $this->configure();
            $this->setInValid();
            ParichyaClient::logout(self::$RETURN_URL, array(
                "mobile_auth" => self::$MOBILE_AUTH,
                "google_auth" => self::$GOOGLE_AUTH,
                "twitter_auth" => self::$TWITTER_AUTH,
                "email_auth" => self::$EMAIL_AUTH,
                "direct_login" => self::$DIRECT_LOGIN
            ));
        }


    }
}
