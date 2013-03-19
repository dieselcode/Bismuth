<?php

namespace Bismuth\Core;

use \Bismuth\Core\Auth\Auth,
    \Bismuth\Core\Response;
use Bismuth\Tools\Object;

class Api
{

    const HTTP_GET      = 'GET';
    const HTTP_HEAD     = 'HEAD';
    const HTTP_DELETE   = 'DELETE';
    const HTTP_POST     = 'POST';
    const HTTP_PUT      = 'PUT';
    const HTTP_PATCH    = 'PATCH';

    const TRANSFER_JSON     = 'application/json';
    const TRANSFER_FORMENC  = 'application/x-www-form-urlencoded';

    const RETURN_JSON_ARRAY  = 1;
    const RETURN_JSON_OBJECT = 0;

    protected $transferType = self::TRANSFER_JSON;
    protected $returnStyle  = self::RETURN_JSON_OBJECT;
    protected $endpointUrl  = '';
    protected $authObj      = null;
    protected $cacheObj     = null;

    protected $sendHeaders  = array();

    protected $response = '';
    protected $headers  = '';

    protected $headerHooks = array();

    // used for conditional requests
    protected $lastETag     = null;
    protected $lastModified = null;


    public function __construct(Auth $authObj, $cacheObj = null)
    {
        $this->authObj = $authObj;
        $this->cacheObj = $cacheObj;
    }

    public function __call($method, $args)
    {
        // get the method constant
        $method = constant('self::HTTP_' . strtoupper($method));

        $args = array(
            'request' => $args[0],
            'params'  => (!empty($args[1]) ? $args[1] : array()),
            'input'   => (!empty($args[2]) ? $args[2] : array())
        );

        switch ($method) {

            case self::HTTP_GET:
            case self::HTTP_HEAD:
            case self::HTTP_DELETE:
                return $this->request($method, $args['request'], $args['params']);
                break;

            case self::HTTP_POST:
            case self::HTTP_PATCH:
            case self::HTTP_PUT:
                if (empty($args['input'])) {
                    throw new \Exception('Missing POST/PUT/PATCH parameters');
                }

                return $this->request($method, $args['request'], $args['params'], $args['input']);
                break;

        }

        return false;
    }

    public function addHeaderHook($header, $callback) // can be closure
    {
        $this->headerHooks[$header] = $callback;
    }

    public function setEndpointUrl($url)
    {
        $this->endpointUrl = $url;
    }

    public function addHeader($header, $value)
    {
        $this->sendHeaders[$header] = $value;
    }

    public function setTransferType($transferType = self::TRANSFER_JSON)
    {
        $this->transferType = $transferType;
    }

    public function setReturnStyle($returnStyle = self::RETURN_JSON_OBJECT)
    {
        $this->returnStyle = $returnStyle;
    }

    public function request($method, $url, $params = array(), $input = array())
    {
        $remoteURL      = $this->prepareRequest($url, $params);
        $queryString    = !empty($params) ? utf8_encode(http_build_query($params, '', '&')) : '';
        $inputString    = !empty($input)  ? utf8_encode(http_build_query($input,  '', '&')) : '';
        $context        = array('http' => array());

        switch ($this->transferType) {
            case self::TRANSFER_JSON:
                $this->addHeader('Content-Type', self::TRANSFER_JSON);

                if ($method == self::HTTP_GET) {
                    $remoteURL = $remoteURL . (!empty($queryString) ? '?'.$queryString : '');
                }

                if (!empty($input)) {
                    $context['http']['content'] = json_encode($input);
                }
                break;
            case self::TRANSFER_FORMENC:
                $this->addHeader('Content-Type', self::TRANSFER_FORMENC);

                $remoteURL = $remoteURL . (!empty($queryString) ? '?'.$queryString : '');

                if (!empty($input)) {
                    $context['http']['content'] = $inputString;
                }
                break;
        }

        /**
         * Cache check
         */
        if (!empty($this->cacheObj)) {
            $cache = $this->cacheObj->getCache($remoteURL);

            if ($cache !== false) {
                // ensure the data is valid
                if (($cache instanceof Response) && $cache->isCached()) {
                    // return the Response object
                    return $cache;
                }
            }
        }

        $context['http']['method'] = $method;
        $context['http']['timeout'] = 3;

        $auth = $this->authObj->getAuthString();

        if (!empty($auth)) {
            $this->addHeader('Authorization', $auth);
        }

        $context['http']['header'] = $this->buildHeaders($this->sendHeaders);

        $ctx = stream_context_create($context);

        /**
         * TODO: Capture timeouts and null exceptions here.  GitHub has a habit of this...
         *  - Pass an exception to be cached, and upon retrieval of the cache, attempt to renew the cache object
         */
        $this->response = @json_decode(file_get_contents($this->endpointUrl . $remoteURL, false, $ctx), $this->returnStyle);
        $this->headers = $this->parseHeaders(join("\r\n", array_values($http_response_header)) . "\r\n\r\n");

        // call our header hooks if we have any
        if (!empty($this->headerHooks)) {
            foreach ($this->headerHooks as $head => $callback) {
                if (array_key_exists($head, $this->headers)) {
                    if (!$callback instanceof \Closure) {
                        call_user_func_array($callback, array($this->headers[$head], $this->response));
                    } else {
                        $callback($this->headers[$head], $this->response);
                    }
                }
            }
        }

        $responseObj = new Response($this->headers, $this->response);

        if (!empty($this->cacheObj)) {
            $cachedResponse = $responseObj;
            $cachedResponse->setCached(true);
            $this->cacheObj->setCache($remoteURL, $cachedResponse, true);
        }

        // return the live result
        return $responseObj;
    }

    protected function prepareRequest($url, $params = null)
    {
        $hasMatch = preg_match_all('/(:(\w+))/', $url, $matches);

        if (!empty($params)) {
            ksort($params);
            $paramKeys = array_keys($params);
            $paramValues = array_values($params);

            $urlKeys = array_keys(array_flip($matches[2]));
            sort($urlKeys);

            $urlParams = array_keys(array_flip($matches[1]));
            sort($urlParams);

            if ($paramKeys === $urlKeys) {
                return str_replace($urlParams, $paramValues, $url);
            }
        }

        return $url;
    }

    public static function parseHeaders($response)
    {
        $parts = explode("\r\n\r\n", $response, 2);

        if (count($parts) != 2) {
            $parts = array($parts, '');
        }

        list($headers, ) = $parts;

        $return = array();
        foreach (explode("\r\n", $headers) as $header) {
            $parts = explode(': ', $header, 2);
            if (count($parts) == 2) {
                list($name, $value) = $parts;
                if (!isset($return[$name])) {
                    $return[$name] = $value;
                } else {
                    if (is_array($return[$name])) {
                        $return[$name][] = $value;
                    } else {
                        $return[$name] = array($return[$name], $value);
                    }
                }
            }
        }

        // parse the status line even further
        list($return['HTTP_CODE'], $return['HTTP_STATUS']) = explode(' ', $return['Status'], 2);

        return $return;
    }

    protected function buildHeaders($header_arr)
    {
        $headers = '';
        foreach ($header_arr as $k => $v) {
            $headers .= $k . ': ' . $v . "\r\n";
        }

        return $headers;
    }

    /**
     * TODO: This is highly dependent on the server having a proper timezone
     */
    public function timeISO8601()
    {
        return gmdate('c', time());
    }

}

?>