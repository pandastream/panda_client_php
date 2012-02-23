<?php

class Panda {
    public function __construct($args) {
        $known_options = array(
            'cloud_id' => null,
            'access_key' => null,
            'secret_key' => null,
            'api_host' => 'api.pandastream.com', // Use api.eu.pandastream.com if your account is in the EU
            'api_port' => 443,
        );
        foreach ($known_options as $option => $default) {
            $this->$option = isset($args[$option]) ? $args[$option] : $default;
        }
        $this->api_version = 2;
    }


    //
    // REST client
    //
    
    public function get($request_path, $params = array()) {
        return $this->http_request('GET', $request_path, $params);
    }

    public function post($request_path, $params = array()) {
        return $this->http_request('POST', $request_path, null, $params);
    }

    public function put($request_path, $params = array()) {
        return $this->http_request('PUT', $request_path, null, $params);
    }

    public function delete($request_path, $params = array()) {
        return $this->http_request('DELETE', $request_path, $params);
    }

    public function api_protocol() {
        if ($this->api_port == 443) {
            return 'https';
        }else{
            return 'http';
        }
    }

    public function api_url() {
        return $this->api_protocol() . '://' . $this->api_host_and_port() . $this->api_base_path();
    }
    
    public function api_host_and_port() {
        $ret = $this->api_host;
        if ($this->api_port != 80) {
            $ret .= ":{$this->api_port}";
        }
        return $ret;
    }
    
    public function api_base_path() {
        return "/v{$this->api_version}";
    }

    private function http_request($verb, $path, $query = null, $data = null) {
        $verb = strtoupper($verb);
        $path = self::canonical_path($path);
        $suffix = '';
        $signed_data = null;

        if ($verb == 'POST' || $verb == 'PUT') {
            $signed_data = $this->signed_params($verb, $path, $data);
            if(isset($data["file"])) {
                $signed_data["file"] = "@". $data["file"];
            }
        }
        else {
            $signed_query_string = $this->signed_query($verb, $path, $query);
            $suffix = '?' . $signed_query_string;
        }

        $url = $this->api_url() . $path . $suffix;
        
        $curl = curl_init($url);
        if ($signed_data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $signed_data);
        }
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $verb);
        curl_setopt($curl, CURLOPT_PORT, $this->api_port);
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($curl, CURLOPT_VERBOSE, 1);

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    //
    // Authentication
    //

    public function signed_query($verb, $request_path, $params = array(), $timestamp = null) {
        return self::array2query($this->signed_params($verb, $request_path, $params, $timestamp));
    }
    
    public function signed_params($verb, $request_path, $params = array(), $timestamp = null) {

        $auth_params = $params;
        unset($auth_params["file"]);

        $auth_params['cloud_id'] = $this->cloud_id;
        $auth_params['access_key'] = $this->access_key;
        $auth_params['timestamp'] = $timestamp ? $timestamp : date('c');
        $auth_params['signature'] = $this->generate_signature($verb, $request_path, $auth_params);
        return $auth_params;
    }
    
    public static function signature_generator($verb, $request_path, $host, $secret_key, $params = array()) {
        $string_to_sign = self::string_to_sign($verb, $request_path, $host, $params);
        $context = hash_init('sha256', HASH_HMAC, $secret_key);
        hash_update($context, $string_to_sign);
        return base64_encode(hash_final($context, true));
    }
    
    public function generate_signature($verb, $request_path, $params = array()) {
        return self::signature_generator($verb, $request_path, $this->api_host, $this->secret_key, $params);
    }
    
    public function string_to_sign($verb, $request_path, $host, $params = array()) {
        $request_path = self::canonical_path($request_path);
        $query_string = self::canonical_querystring($params);
        $_verb = strtoupper($verb);
        $_host = strtolower($host);
        $string_to_sign = "$_verb\n$_host\n$request_path\n$query_string";
        return $string_to_sign;
    }
    
    //
    // Misc
    //

    private static function canonical_path($path) {
        return '/' . trim($path, " \t\n\r\0\x0B/");
    }
    
    private static function canonical_querystring($params = array()) {
        ksort($params, SORT_STRING);
        return self::array2query($params);
    }

    private static function urlencode($str) {
        $ret = urlencode($str);
        $ret = str_replace("%7E", "~", $ret);
        $ret = str_replace("+", "%20", $ret);
        return $ret;
    }
    
    private static function array2query($array) {
        $pairs = array();
        foreach ($array as $key => $value) {
            $pairs[] = self::urlencode($key) . '=' . self::urlencode($value);
        }
        return join('&', $pairs);
    }
}

?>
