<?php

namespace HttpHandler;

class HttpHandler {

    private $QueryResult;
    private $PostData;
    private $Query;
    private $Headers;
    public $AllowEmptyUrlQuery;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {
        $this->QueryResult  = null;
        $this->PostData     = null;
        $this->Query        = null;
        $this->Headers      = [
            'Content-Type: application/json',
        ];
        $this->AllowEmptyUrlQuery = false;
    }


    /**
     * GetHeaders
     *
     * @return void
     */
    public function GetHeaders(?string $header = null) {
        if (isset($this->QueryResult['headers']) && !empty($this->QueryResult['headers'])) {
            if (!empty($header)) {
                foreach ($this->QueryResult['headers'] as $key=>$headers) {
                    if ($key == $header) {
                        return $headers;
                    }
                }
            } else {
                return $this->QueryResult['headers'];
            }
        }
        return null;
    }


    /**
     * GetBody
     *
     * @return array
     */
    public function GetBody(): ?array {
        return (isset($this->QueryResult['body']) && !empty($this->QueryResult['body'])? $this->QueryResult['body']: null);
    }

    /**
     * SetPostData
     *
     * @param  array $postdata
     *
     * @return void
     */
    public function SetPostData(?array $postdata) {
        $this->PostData = \json_encode($postdata, true);
        $this->AddHeader('Content-Length:'.strlen($this->PostData));
    }

    /**
     * SetQuery
     *
     * @param  array $query
     *
     * @return void
     */
    public function SetQuery(array $query) {
        $this->Query = $query;
    }

    /**
     * AddHeader
     *
     * @param  string $header
     *
     * @return void
     */
    public function AddHeader(string $header) {
        \array_push($this->Headers, $header);
    }

    /**
     * request
     *
     * @param  string $method
     * @param  string $url
     *
     * @return array
     */
    public function request(string $method, string $url): ?array {
        $url = $this->AddUrlQuerys($url, $this->AllowEmptyUrlQuery);
        $ch = curl_init($url);
        (!empty($this->PostData)? $this->PostData: null);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        (!empty($this->PostData)? curl_setopt($ch, CURLOPT_POSTFIELDS, $this->PostData): null);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->Headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $result = curl_exec($ch);

        $hsize      = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers    = substr($result, 0, $hsize);
        $body       = substr($result, $hsize);

        $this->QueryResult['headers']   = $this->ParseHeaders($headers);
        $this->QueryResult['body']      = (!empty($body) && $body? \json_decode($body, true): null);
        return $this->QueryResult;
    }

    public function GetHttpResultCode(): int {
        $headers = $this->QueryResult;
        $code = 000;
        if (!empty($headers)) {
            foreach ($headers['headers'] as $name=>$header) {
                if ($name == 'http_code') {
                    $code = (int)explode(' ', $header)[1];
                    break;
                }
            }
        }
        return $code;
    }

    /**
     * ParseHeaders
     *
     * @param  string $headerContent
     *
     * @return array
     */
    private function ParseHeaders(string $headerContent): array {
        $headers = array();
        $arrRequests = explode("\r\n\r\n", $headerContent);
        for ($index = 0; $index < count($arrRequests) -1; $index++) {
            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0) {
                    $headers[$index]['http_code'] = $line;
                } else {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }
    
        return (!empty($headers[0])? $headers[0]: null);
    }

    /**
     * AddUrlQuerys
     *
     * @param  string  $url
     * @param  bool    $AllowEmptyQuery
     *
     * @return string
     */
    private function AddUrlQuerys(string $url, bool $AllowEmptyQuery = false): string {
        if (!empty($this->Query)){
            $url = $url.'?';
            foreach ($this->Query as $name=>$query) {
                if (!empty($query) || $AllowEmptyQuery) {
                    $query = urlencode($query);
                    $url = $url."&$name=$query";
                }
            }
        }
        return $url;
    }

}

?>