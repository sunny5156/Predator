<?php

class Xhgui_Controller_Watch extends Xhgui_Controller
{
    protected $_app;
    protected $_watches;
    protected $_profiles;
    private $_domain = '';

    public function __construct($app, $profiles, $watches)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;
        $this->_watches = $watches;

        $cookie = (isset($_COOKIE['domain'])) ? $_COOKIE['domain'] : '';

        $this->_domain =$this->_app->request()->get('domain');
        $this->_domain = ( ! empty($this->_domain)) ? $this->_domain : $cookie;
    }

    public function get()
    {
        $watched = $this->_watches->getAll();

        $this->_template = 'watch/list.twig';
        $this->set(array(
                       'watched' => $watched,
                       'domain'  => $this->_domain,
                       'host'    => $this->_profiles->getHttpHost()
                   ));
    }

    public function post()
    {
        $app = $this->_app;
        $watches = $this->_watches;

        $saved = false;
        $request = $app->request();
        foreach ((array)$request->post('watch') as $data) {
            $saved = true;
            $watches->save($data);
        }
        if ($saved) {
            $app->flash('success', 'Watch functions updated.');
        }
        $app->redirect($app->urlFor('watch.list'));
    }
}
