<?php

require_once('simpletest/autorun.php');
require_once('../panda.php');

class UrlsTest extends UnitTestCase {
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

class SignatureTest extends UnitTestCase {
    function test_generator_simple() {
        $signature = Panda::signature_generator(
            'GET',
            '/videos.json',
            'api.pandastream.com',
            's3cr3t',
            array()
        );
        $this->assertEqual('RZml1S8BcxSTCaTSQVnXj/7QA0vM4M6FqRIUuntTYjk=', $signature);
    }
    
    function test_generator_complex() {
        $signature = Panda::signature_generator(
            'POST',
            '/videos.json',
            'api.eu.pandastream.com',
            's3cre3t', array(
                'upload_redirect_url' => 'http://localhost:44444/panda/simplest/player.php?panda_video_id=$id',
                'cloud_id' => '5385adf38f3e39de1ddcf4c1b81ad056',
                'access_key' => '9c264aba-8d97-df11-b01b-12313c0091c1',
                'timestamp' => '2010-07-16T06:27:54+00:00',
            )
        );
        $this->assertEqual('JKBd6ARhTYqxGeTsqfrPUoOHrvmmz59bkJK37XvrL/U=', $signature);
    }
    
    function test_string_to_sign() {
        $expected =<<<EOF
POST
api.eu.pandastream.com
/videos.json
access_key=9c264aba-8d97-df11-b01b-12313c0091c1&cloud_id=5385adf38f3e39de1ddcf4c1b81ad056&timestamp=2010-07-16T06%3A27%3A54%2B00%3A00&upload_redirect_url=http%3A%2F%2Flocalhost%3A44444%2Fpanda%2Fsimplest%2Fplayer.php%3Fpanda_video_id%3D%24id
EOF;
        $actual = Panda::string_to_sign(
            'POST',
            '/videos.json',
            'api.eu.pandastream.com',
            array(
                'upload_redirect_url' => 'http://localhost:44444/panda/simplest/player.php?panda_video_id=$id',
                'cloud_id' => '5385adf38f3e39de1ddcf4c1b81ad056',
                'access_key' => '9c264aba-8d97-df11-b01b-12313c0091c1',
                'timestamp' => '2010-07-16T06:27:54+00:00',
            )
        );
        $this->assertEqual($expected, $actual);
    }
}
