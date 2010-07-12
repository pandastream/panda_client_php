<?php

require_once('simpletest/autorun.php');
require_once('../panda.php');

class PandaTest extends UnitTestCase {

    function setup() {
        $this->p = new Panda(array(
            'api_host'   => 'api.pandastream.com',
            'cloud_id'   => 'the-cloud-id',
            'access_key' => 'the-access-key',
            'secret_key' => 'the-secret-key',
        ));
    }

    function test_api_url() {
        $this->assertEqual($this->p->api_url(), 'http://api.pandastream.com/v2');
    }

    function test_api_host_and_port() {
        $this->assertEqual($this->p->api_host_and_port(), 'api.pandastream.com');
        $p2 = new Panda(array(
            'api_host'   => 'api.pandastream.com',
            'api_port'   => '8080',
            'cloud_id'   => 'the-cloud-id',
            'access_key' => 'the-access-key',
            'secret_key' => 'the-secret-key',
        ));
        $this->assertEqual($p2->api_host_and_port(), 'api.pandastream.com:8080');
    }
}