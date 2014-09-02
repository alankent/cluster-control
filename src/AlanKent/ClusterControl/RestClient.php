<?php

namespace AlanKent\ClusterControl;

class RestClient {

    private $server;
    private $curlHandle;
    private $debug;

    /**
     * Constructor.
     * @param string $server Base URL for etcd server to connect to.
     */
    function __construct($server, $debug)
    {
        $this->server = $server;
        $this->curlHandle = curl_init();
        $this->debug = $debug;
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

        if ($this->debug) {
            echo "$method $fullUrl $json\n";
        }

        $options = array(
            CURLOPT_URL => $fullUrl,
            CURLOPT_CUSTOMREQUEST => $method, // GET POST PUT PATCH DELETE HEAD OPTIONS
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_TIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => true // Etcd in cluster relocates request to the master
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
                $colon = strpos($line, ':');
                $key = strtolower(trim(substr($line, 0, $colon)));
                $value = trim(substr($line, $colon + 1));
                $headers[$key] = $value;
            }
        }

        if ($this->debug) {
            if (isset($headers['x-etcd-index'])) {
                $idx = $headers['x-etcd-index'];
                echo "  X-Etcd-Index = $idx\n";
            }
            echo "  BODY = $bodyText\n";
        }

        return ['body' => json_decode($bodyText, true), 'headers' => $headers];
    }
}
