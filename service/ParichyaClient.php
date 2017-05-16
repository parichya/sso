<?php
/**
 * Created by IntelliJ IDEA.
 * User: lalittanwar
 * Date: 02/10/16
 * Time: 11:45 AM
 */

namespace app\service {


    class ParichyaClient
    {

        public static $OTP_SERVER = "http://me.parichya.com/"; //"http://sso.parichya.com/";
        public static $OTP_BROKER_ID = ""; //"BROKER_PUBLIC_KEY";
        public static $OTP_BROKER_SECRET = "******"; //"http://sso.parichya.com/";
        public static $OTP_SESSION_DATA = null;
        public static $RETURN_URL;
        public static $OTP_AUTH_INFO = "user_data";
        public static $OTP_AUTH_TOKEN = "session_token";
        public static $OTP_RETURN_URL_KEY = "broker_url";
        public static $VALID_SESSION_KEY = "parichya-auth-done";

        public static function rx_setup($options = array())
        {
            if (class_exists('\Config')) {
                $options = \Config::getSection("PARICHYA_CONFIG");
                self::setUp($options);
            }
        }

        public static function setUp($options = array())
        {
            self::$OTP_SERVER = isset($options["SERVER"]) ? $options["SERVER"] : self::$OTP_SERVER;
            self::$OTP_BROKER_ID = isset($options["API_KEY"]) ? $options["API_KEY"] : self::$OTP_SERVER;
            self::$OTP_BROKER_SECRET = isset($options["API_SECRET"]) ? $options["API_SECRET"] : self::$OTP_SERVER;
        }

        public static function getSessionToken()
        {
            if (isset($_SESSION["otp-" . self::$OTP_AUTH_TOKEN])) {
                return $_SESSION["otp-" . self::$OTP_AUTH_TOKEN];
            }
            return "";
        }


        public static function authenticate($options = array(), $session_token = null)
        {

            self::$OTP_SESSION_DATA = isset($_SESSION[self::$OTP_AUTH_INFO]) ? $_SESSION[self::$OTP_AUTH_INFO] : null;

            //var_dump(self::$OTP_SESSION_DATA);
            if (isset($_REQUEST[self::$OTP_AUTH_TOKEN])) {
                $session_token = $_REQUEST[self::$OTP_AUTH_TOKEN];
                self::$OTP_SESSION_DATA = null;
            }

            if (self::$OTP_SESSION_DATA == null) {
                if (!isset($_REQUEST[self::$OTP_AUTH_TOKEN]) && $session_token == null) {
                    $otpAuthOptions = array(
                        "broker_id" => self::$OTP_BROKER_ID
                    );
                    if (!isset($options[self::$OTP_RETURN_URL_KEY])) {
                        self::$RETURN_URL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        $otpAuthOptions[self::$OTP_RETURN_URL_KEY] = self::$RETURN_URL;
                    } else {
                        $otpAuthOptions[self::$OTP_RETURN_URL_KEY] = $options[self::$OTP_RETURN_URL_KEY];
                    }
                    $_SESSION[self::$OTP_RETURN_URL_KEY] = self::$RETURN_URL;

                    $redirectUrl = (self::$OTP_SERVER . "hybrid?broker_id=" . $otpAuthOptions["broker_id"] .
                        "&" . self::$OTP_RETURN_URL_KEY . "d=" . base64_encode($otpAuthOptions[self::$OTP_RETURN_URL_KEY])).
                        "&configd=".base64_encode(json_encode($options))."&direct_login=".$options["direct_login"];
                    header("Location: " . $redirectUrl);
                    //header("Location: " . self::$OTP_SERVER . "/login?" . http_build_query($otpAuthOptions));

                    die();
                } else {
                    $ostData = array(
                        "broker_id" => self::$OTP_BROKER_ID,
                        "broker_secret" => self::$OTP_BROKER_SECRET,
                        "session_token" => $session_token
                    );
                    //echo self::api("POST", self::$OTP_SERVER . "/api/getdata",$ostData );
                    //print_line("sending...post".self::$OTP_SERVER . "/getdata");
                    self::$OTP_SESSION_DATA = json_decode(self::api("POST", self::$OTP_SERVER . "/api/getdata",$ostData ));
                    $_SESSION["otp-" . self::$OTP_AUTH_TOKEN] = $session_token;
                    $_SESSION[self::$OTP_AUTH_INFO] = serialize(self::$OTP_SESSION_DATA);
                    $_SESSION[self::$VALID_SESSION_KEY] = TRUE;
                }
            } else {
                self::$OTP_SESSION_DATA = unserialize($_SESSION[self::$OTP_AUTH_INFO]);
            }
            return self::$OTP_SESSION_DATA;
        }

        public static function logout($redirectUrl,$options = array())
        {

            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            if (isset($_SESSION[self::$VALID_SESSION_KEY]) && $_SESSION[self::$VALID_SESSION_KEY] == TRUE) {
                unset($_SESSION[self::$VALID_SESSION_KEY]);
                header("Location: " . self::$OTP_SERVER . "/logout?" . http_build_query(array(
                        "broker_id" => self::$OTP_BROKER_ID,
                        "broker_urld" => base64_encode("http://$_SERVER[HTTP_HOST]$redirectUrl"),
                        "command" => "logout",
                        "configd" => base64_encode(json_encode($options))
                    )));
            } else {
                header("Location: ".$redirectUrl."?token=".microtime());
            }
        }

        public static function authUserName($options)
        {
            self::$OTP_SESSION_DATA = json_decode(self::api("POST", self::$OTP_SERVER . "/api/getbyauthdata", array(
                "username" => $options["username"],
                "password" => $options["password"]
            )));

            $_SESSION[self::$OTP_AUTH_INFO] = serialize(self::$OTP_SESSION_DATA);
            return self::$OTP_SESSION_DATA;
        }

        public static function api($method, $url, $data = false)
        {
            $curl = curl_init();

            switch ($method) {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);

                    if ($data)
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_PUT, 1);
                    break;
                default:
                    if ($data)
                        $url = sprintf("%s?%s", $url, http_build_query($data));
            }

            // Optional Authentication:
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            curl_close($curl);

            return $result;
        }
    }

    ParichyaClient::rx_setup();

}


