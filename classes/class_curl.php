<?php
class CurlRequest
{
    private $curl;
    public $url;
    public $options = [];
    public $headers = [];
    public $info = "";
    public $agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.3";
    public $response_reason = [];
    public $responseHeaders = '';
    public $curl_connect_timeout = 3;
    public $curl_timeout = 10;

    public function __construct($url)
    {
        $this->url = $url;
        $this->curl = curl_init();
    }

    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            foreach ($headers as $header_key => $header) {
                $this->headers[] = $header_key . ':' . $header;
            }
        } else {
            $this->headers[] = $headers;
        }
        return $this;
    }
     public function setHeader($headers)
    {
        if (is_array($headers)) {
            foreach ($headers as $header_key => $header) {
                $this->headers[] = $header_key . ':' . $header;
            }
        } else {
            $this->headers[] = $headers;
        }
        return $this;
    }

    private function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function post($data, $method = 'POST'){

        $this->setOption(CURLOPT_URL, $this->url);
        $this->setOption(CURLOPT_RETURNTRANSFER, TRUE);
        $this->setOption(CURLOPT_ENCODING, "");
        $this->setOption(CURLOPT_MAXREDIRS, 10);
        $this->setOption(CURLOPT_USERAGENT, $this->agent);
        $this->setOption(CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout);
        $this->setOption(CURLOPT_TIMEOUT, $this->curl_timeout);
        $this->setOption(CURLOPT_SSL_VERIFYPEER, FALSE);
        $this->setOption(CURLOPT_HEADER, TRUE);
        $this->setOption(CURLINFO_HEADER_OUT, TRUE);
        $this->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        if (!empty($this->headers)) {
            $this->setOption(CURLOPT_HTTPHEADER, $this->headers);
        }

        if(strtoupper($method) == 'POST'){
            $this->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->setOption(CURLOPT_POST, TRUE);
            $this->setOption(CURLOPT_POSTFIELDS, $data);
        }
        elseif(strtoupper($method) == 'PUT') {
            $this->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
            if (is_array($data)){
                $this->setOption(CURLOPT_POSTFIELDS, http_build_query($data));
            }else{
                $this->setOption(CURLOPT_POSTFIELDS, $data);
            }
        }elseif(strtoupper($method) == 'DELETE'){
            $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        }else{}

        return $this->execute();
    }

    private function execute()
    {

        try {
            foreach ($this->options as $option => $value) {
                curl_setopt($this->curl, $option, $value);
            }
            $response = curl_exec($this->curl);

            if ($response === false) {
                error_log('CURL request failed: ' . $this->url);
                return ['status_code' => 500, 'body' => ''];
            }

            $this->info = curl_getinfo($this->curl);
            $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);
            $parsedHeaders = $this->parseResponseHeaders($headers);
            $this->response_reason['header'] = $parsedHeaders;
            return [
                'status_code' => $statusCode,
                'body' => $responseBody ?: '',
            ];

        } catch (Exception $e) {
            error_log('CURL request failed: '.$this->url.' - '. $e->getMessage(), $e->getCode());
            // throw new Exception('cURL request failed: ' . $e->getMessage(), $e->getCode());
            return ['status_code' => 500, 'body' => ''];
        } finally {
            curl_close($this->curl);
        }
    }



    public function getRequest(){
        return $this->info;
    }   
    public function getResponseReason(){
        return $this->response_reason['header']['reason'];
    }

    protected function parseResponseHeaders($headers)
    {
        $lines = array_filter(explode("\r\n", $headers));
        preg_match('#^HTTP/([\d\.]+)\s(\d+)\s(.*?)$#i', array_shift($lines), $match);
        $out = array(
            'protocol_version' => $match[1],
            'status'           => (int) $match[2],
            'reason'           => $match[3],
            'headers'          => array(),
        );
        $this->addHeaderToArray($out['headers'], $lines, null);
        return $out;
    }


    protected function addHeaderToArray(&$builder, $name, $values, $append = true)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                if (is_int($key)) {
                    list($key, $value) = array_map('trim', explode(':', $value, 2));
                }
                $this->addHeaderToArray($builder, $key, $value, $append);
            }
            return;
        }
        $normalizedKey = $this->normalizeHeaderKey($name);

        if (!$append || !isset($builder[$normalizedKey])) {
            $builder[$normalizedKey] = array();
        }

        foreach ((array) $values as $value) {
            if (!is_string($value) && !is_numeric($value)) {
                throw new InvalidArgumentException('Header value must be a string or array of string.');
            }
            $builder[$normalizedKey][] = array(
                'key'   => $name,
                'value' => trim($value)
            );
        }
    }

    protected function normalizeHeaderKey($key)
    {
        return strtr(strtolower($key), '_', '-');
    }

}
?>