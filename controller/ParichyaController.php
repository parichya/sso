<?php
/**
 * Created by IntelliJ IDEA.
 * User: LT
 * Date: 04/06/17
 * Time: 5:10 PM
 */

namespace app\controller {

    class ParichyaController extends AbstractController
    {

        /**
         * @RequestMapping(url="view/parichya/hello",type="template")
         * @RequestParams(true)
         */
        public function auth($model, $email = null, $password = null, $provider = null)
        {
            \app\service\Smarty::setTemplateDir("../view");
            if ($this->user->isValid()) {
                echo "Hey Baby";
            } else {
                echo "Hey Stranger";
            }
            return "hello";

        }

        /**
         * @RequestMapping(url="api/parichya/auth/{provider}", type="json")
         * @RequestParams(true)
         */
        public function social($provider = null, $access_token = null)
        {
            $this->user->setAccessToken($provider, $access_token);
            return $this->user->getAuthData();
        }

        /**
         * @RequestMapping(url="view/parichya/auth/{provider}", type="json")
         * @RequestParams(true)
         */
        public function socialView($provider = null, $access_token = null, $url = null)
        {
            if ($this->user->setAccessToken($provider, $access_token) && !empty($url)) {
                $this->header("Location", $url);
                exit();
            }
        }

    }

}
