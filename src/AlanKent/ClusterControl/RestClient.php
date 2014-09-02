<?php

namespace AlanKent\ClusterControl;

class RestClient {

    private $server;
    private $curlHandle;

    /**
     * Constructor.
     * @param string $server Base URL for etcd server to connect to.
     */
    function __construct($server)
    {
        $this->server = $server;
        $this->curlHandle = curl_init();
    }

    /**
     * Perform a HTTP request against the etcd server.
     * @param string $method GET, POST, DELETE etc (the HTTP verb).
     * @param string $uri The path to append to the base server URL.
     * @param null|string|array $query A query string or an array of name/value pairs.
     * @param null|string $json The optional JSON encoded payload to send to etcd.
     * @return array Contains 'body' holding the JSON decoded response and 'headers' holding an
     * array of name/value pairs for the headers.
     */
    public function curl($method, $uri, $query = null, $json = null)
    {
        $fullUrl = $this->server . $uri;

        // Add query parameters (if any).
        if (isset($query)) {
            if (is_array($query)) {
                $sep = "?";
                foreach ($query as $n => $v) {
                    $fullUrl .= $sep . urlencode($n) . '=' . urlencode($v);
                    $sep = "&";
                }
            } else {
                $fullUrl .= "?" . $query;
            }
        }

        $options = array(
            CURLOPT_URL => $fullUrl,
            CURLOPT_CUSTOMREQUEST => $method, // GET POST PUT PATCH DELETE HEAD OPTIONS
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2
        );

        curl_setopt_array($this->curlHandle, $options);

        $response = curl_exec($this->curlHandle);

        $headersEnd = strpos($response, "\r\n\r\n");

        $headers = [];
        $headerText = substr($response, 0, $headersEnd);
        $bodyText = substr($response, $headersEnd + 4);

        foreach (explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                $colon = strpos(':', $line);
                $key = strtolower(trim(substr($line, 0, $colon)));
                $value = trim(substr($line, $colon + 1));
                $headers[$key] = $value;
            }
        }

        return ['body' => json_decode($bodyText, true), 'headers' => $headers];
    }
}