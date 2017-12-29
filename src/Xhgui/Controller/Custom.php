<?php

class Xhgui_Controller_Custom extends Xhgui_Controller
{
    protected $_app;
    protected $_profiles;
    private $_domain = '';

    public function __construct($app, $profiles)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;

        $cookie = (isset($_COOKIE['domain'])) ? $_COOKIE['domain'] : '';

        $this->_domain =$this->_app->request()->get('domain');
        $this->_domain = ( ! empty($this->_domain)) ? $this->_domain : $cookie;
    }

    public function get()
    {
        $this->_template = 'custom/create.twig';
        $this->set(array(
                       'domain' => $this->_domain,
                       'host'   => $this->_profiles->getHttpHost()
                   ));
    }

    public function help()
    {
        $request = $this->_app->request();
        if ($request->get('id')) {
            $res = $this->_profiles->get($request->get('id'));
        } else {
            $res = $this->_profiles->latest();
        }
        $this->_template = 'custom/help.twig';
        $this->set(array(
            'data' => print_r($res->toArray(), 1)
        ));
    }

    public function query()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';

        $query = json_decode($request->post('query'), true);
        $error = array();
        if (is_null($query)) {
            $error['query'] = json_last_error();
        }

        $retrieve = json_decode($request->post('retrieve'), true);
        if (is_null($retrieve)) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            $json = json_encode(array('error' => $error));
            return $response->body($json);
        }

        $query['meta.SERVER.HTTP_HOST'] = $this->_domain;

        $perPage = $this->_app->config('page.limit');

        $res = $this->_profiles->query($query, $retrieve)
            ->limit($perPage);
        $r = iterator_to_array($res);

        $ext = new Xhgui_Twig_Extension($this->_app);

        foreach($r as $key => $val){
            foreach($r[$key]['profile']['main()'] as $k => $v){
                $r[$key]['profile']['main()'][$k] = (in_array($k,array('mu','pmu')))?$ext->formatBytes($v):$ext->formatTime($v);
            }

            $r[$key]['meta']['SERVER']['REQUEST_TIME'] = date($this->_app->config('date.format'),$r[$key]['meta']['SERVER']['REQUEST_TIME']);
        }

        return $response->body(json_encode($r));
    }
}
