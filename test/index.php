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
    
    function test_https() {
        $this->p2 = new Panda(array(
            'api_host'   => 'api.pandastream.com',
            'cloud_id'   => 'the-cloud-id',
            'access_key' => 'the-access-key',
            'secret_key' => 'the-secret-key',
            'api_port'   => '443',
        ));
        $this->assertEqual($this->p2->api_url(), 'https://api.pandastream.com:443/v2');
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
        $expected = "POST\napi.eu.pandastream.com\n/videos.json\naccess_key=9c264aba-8d97-df11-b01b-12313c0091c1&cloud_id=5385adf38f3e39de1ddcf4c1b81ad056&timestamp=2010-07-16T06%3A27%3A54%2B00%3A00&upload_redirect_url=http%3A%2F%2Flocalhost%3A44444%2Fpanda%2Fsimplest%2Fplayer.php%3Fpanda_video_id%3D%24id";
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

class UnderlyingCrypto extends UnitTestCase {
    private function generate_context() {
        $context = hash_init('sha256', HASH_HMAC, 'secret');
        hash_update($context, "May I be authenticated\r\nFor the grace of HMAC\nAnd a SHA256 hash");
        return $context;
    }
    
    private function read_data($filename) {
        $path = dirname(__FILE__) . '/fixtures/' . $filename;
        $handle = fopen($path, 'rb');
        $ret = fread($handle, filesize($path));
        fclose($handle);
        return $ret;
    }

    function test_sha256_bin() {
        $context = $this->generate_context();
        $actual = hash_final($context, true);
        $expected = $this->read_data('sha256.bin');
        $this->assertEqual($expected, $actual);
    }

    function test_sha256_hex() {
        $context = $this->generate_context();
        $hex_actual = hash_final($context, false);
        $this->assertEqual('cb5058f33dbf931a3278110a6d079497f9b283705dade807a4b7f789179d0a42', $hex_actual);
    }

    function test_base64_bin() {
        $this->assertEqual('y1BY8z2/kxoyeBEKbQeUl/myg3BdregHpLf3iRedCkI=', base64_encode($this->read_data('sha256.bin')));
    }
    
    function test_base64_ascii() {
        $this->assertEqual('YWJjZGVmZ2hpams=', base64_encode('abcdefghijk'));
    }
}
