<?php

class Xhgui_Controller_Waterfall extends Xhgui_Controller
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

    public function index()
    {
        $request = $this->_app->request();
        $search = array();
        $keys = array("remote_addr", 'request_start', 'request_end');
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = trim($request->get($key));
            }
        }

        $search['domain'] = $this->_domain;

        $result = $this->_profiles->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => true
        ));

        $paging = array(
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => 'asc',
            'direction' => $result['direction']
        );

        $this->_template = 'waterfall/list.twig';
        $this->set(array(
            'runs' => $result['results'],
            'search' => $search,
            'paging' => $paging,
            'date_format' => $this->_app->config('date.format'),
            'base_url' => 'waterfall.list',
            'domain' => $this->_domain,
            'host'   => $this->_profiles->getHttpHost()
        ));
    }

    public function query()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $search = array();
        $keys = array("remote_addr", 'request_start', 'request_end');
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }

        $search['domain'] = $this->_domain;

        $result = $this->_profiles->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => TRUE
        ));
        $datas = array();
        foreach ($result['results'] as $r) {
            $duration = $r->get('main()', 'wt');
            $start = $r->getMeta('SERVER.REQUEST_TIME_FLOAT');
            $title = $r->getMeta('url');
            $datas[] = array(
                'id' => (string)$r->getId(),
                'title' => $title,
                'start' => $start * 1000,
                'duration' => $duration / 1000 // Convert to correct scale
            );
        }
        $response->body(json_encode($datas));
        $response['Content-Type'] = 'application/json';
    }

}
