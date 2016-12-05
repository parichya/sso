<?php

namespace app\model {

    use app\service\ParichyaClient;
    use app\service\R;


    class ParichyaUser extends AbstractUser
    {

        public static $MOBILE_AUTH = false;
        public static $GOOGLE_AUTH = true;
        public static $RETURN_URL = "/";

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

        public function basicAuth()
        {
            $this->configure();
            $authdata = ParichyaClient::authenticate(array(
                "mobile_auth" => self::$MOBILE_AUTH,
                "google_auth" => self::$GOOGLE_AUTH
            ));

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

        public function unauth()
        {
            $this->configure();
            $this->setInValid();
            ParichyaClient::logout(self::$RETURN_URL, array(
                "mobile_auth" => self::$MOBILE_AUTH,
                "google_auth" => self::$GOOGLE_AUTH
            ));
        }


    }
}
