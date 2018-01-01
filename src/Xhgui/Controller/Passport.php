<?php

class Xhgui_Controller_Passport extends Xhgui_Controller
{
    protected $_app;
    protected $_profiles;
    private   $_domain = '';
    private $_username = '';


    public function __construct($app, $profiles)
    {
        $this->_app      = $app;
        $this->_profiles = $profiles;

        $this->_username = (isset($_COOKIE['username'])) ? Xhgui_Util::authcode($_COOKIE['username'], 'DECODE') : '';


        $cookie = (isset($_COOKIE['domain'])) ? $_COOKIE['domain'] : '';

        $this->_domain = $this->_app->request()->get('domain');
        $this->_domain = ( ! empty($this->_domain)) ? $this->_domain : $cookie;
    }

    public function index()
    {
        if(!empty($this->_username)){
            $this->_app->redirect($this->_app->urlFor('home'));
        }

        $this->_template = 'passport.twig';
    }

    public function signin()
    {
        $url = '';
        $url = '';

        try {
            $app     = $this->_app;
            $request = $app->request();

            $passport = $app->config('passport');

            $username = $request->post('username');
            $password = $request->post('password');

            if (empty($username) || empty($password)) {
                throw new \Exception('Your username/password is missing.');
            }

            if ( ! isset($passport[$username])) {
                throw new \Exception('Login failed.');
            }

            if ($passport[$username] != $password) {
                throw new \Exception('Login failed.');
            }

            setcookie('username', Xhgui_Util::authcode($username, 'ENCODE'), time() + 86400, '/');

            $url = 'home';
        } catch (\Exception $e) {
            $url = 'passport.index';
            $app->flash('error', $e->getMessage());
        }

        $app->redirect($app->urlFor($url));
    }

    public function logout()
    {
        $app = $this->_app;
        setcookie('username', '', 0, '/');
        $app->redirect($app->urlFor('passport.index'));
    }
}
