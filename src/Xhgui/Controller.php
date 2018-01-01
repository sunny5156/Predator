<?php

class Xhgui_Controller
{
    protected $_templateVars = array();
    protected $_template     = null;


    public function set($vars)
    {
        $request = $this->_app->request();

        $cookie = (isset($_COOKIE['domain'])) ? $_COOKIE['domain'] : '';

        $domain = $request->get('domain');
        $domain = ( ! empty($domain)) ? $domain : $cookie;

        if ( ! empty($domain)) {
            setcookie('domain', $domain, time() + 86400, '/');
        }

        $this->_templateVars = array_merge($this->_templateVars, $vars);
    }

    public function templateVars()
    {
        return $this->_templateVars;
    }

    public function render()
    {
        $this->_app->render($this->_template, $this->_templateVars);
    }
}
