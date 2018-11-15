<?php

namespace Alexsoft;

/**
 * Neat and tidy cURL wrapper for PHP
 *
 * @method string get
 * @method string head
 * @method string post
 * @method string put
 * @method string patch
 * @method string delete
 *
 * @package Curl
 * @author  Alex Plekhanov
 * @link    https://github.com/alexsoft/curl
 * @license MIT
 * @version 0.4.0-dev
 */
class Curl
{
    const VERSION = '0.4.0-dev';

    const GET = 'GET';
    const POST = 'POST';
    const HEAD = 'HEAD';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const PATCH = 'PATCH';

    /**
     * cURL handle
     * @var resource
     */
    protected $resource;

    /**
     * Response string from curl_exec
     * @var string
     */
    protected $response;

    /**
     * URL to query
     * @var string
     */
    protected $url;

    /**
     * HTTP Verb
     * @var string
     */
    protected $method;

    /**
     * Key => value of data to send
     * @var array
     */
    protected $data = [];

    /**
     * Key => value of headers to send
     * @var array
     */
    protected $headers = [];

    /**
     * Key => value of cookies to send
     * @var array
     */
    protected $cookies = [];

    /**
     * User agent for query
     * @var string
     */
    protected $userAgent = 'alexsoft/curl';

    /**
     * Array of available HTTP Verbs
     * @var array
     */
    protected $availableMethods = [self::GET, self::POST, self::HEAD, self::PUT, self::DELETE, self::PATCH];

    /**
     * @param $url string URL for query
     */
    public function __construct($url = null)
    {
        $this->url = $url;
    }

    /**
     * Add data for sending
     * @param array $data
     * @return $this
     */
    public function addData(array $data)
    {
        $this->data = array_merge(
            $this->data,
            $data
        );
        return $this;
    }

    /**
     * Add headers for sending
     * @param array $headers
     * @return $this
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge(
            $this->headers,
            $headers
        );
        return $this;
    }

    /**
     * Add cookies for sending
     * @param array $cookies
     * @return $this
     */
    public function addCookies(array $cookies)
    {
        $this->cookies = array_merge(
            $this->cookies,
            $cookies
        );
        return $this;
    }

    /**
     * Methods which can be called:
     * get(), post(), head(), put(), delete(), options()
     * @param $name
     * @param $arguments
     * @return array|NULL
     * @throws \Exception
     */
    function __call($name, $arguments)
    {
        if (!empty($arguments)) {
            $url = $arguments[0];
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $this->url = $url;
            } else {
                throw new \Exception('Wrong url. Give a correct url in a string format.');
            }
        } else {
            if (is_null($this->url)) {
                throw new \Exception('Provide a url either in constructor or while calling verb method.');
            }
        }

        if (in_array(mb_strtoupper($name), $this->availableMethods)) {
            return $this->request(mb_strtoupper($name));
        } else {
            throw new \Exception('Method ' . mb_strtoupper($name) . ' is not supported');
        }
    }

    /**
     * @param $method string method of query
     * @return array|NULL
     */
    protected function request($method)
    {
        $this->resource = curl_init();
        $this->method = $method;
        $this->prepareRequest();
        $this->response = curl_exec($this->resource);
        curl_close($this->resource);
        return $this->parseResponse();
    }

    /**
     * Method which sets all the data, headers, cookies
     * and other options for the query
     */
    protected function prepareRequest()
    {
        // Set data for GET queries
        if ($this->method === static::GET && !empty($this->data)) {
            $url = trim($this->url, '/') . '?';
            $url .= http_build_query($this->data);
        } else {
            $url = $this->url;
        }

        // Set options
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_POST => $this->method === static::POST,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => $this->method === static::HEAD,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (!in_array($this->method, [static::GET, static::HEAD, static::POST])) {
            $options[CURLOPT_CUSTOMREQUEST] = $this->method;
        }

        // Set data for not GET queries
        if (!empty($this->data) && $this->method !== static::GET) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($this->data);
        }

        // Set headers if needed
        if (!empty($this->headers)) {
            $headersToSend = [];
            foreach ($this->headers as $key => $value) {
                $headersToSend[] = "{$key}: {$value}";
            }
            $options[CURLOPT_HTTPHEADER] = $headersToSend;
        }

        // Set cookies if needed
        if (!empty($this->cookies)) {
            $cookiesToSend = [];
            foreach ($this->cookies as $key => $value) {
                $cookiesToSend[] = "{$key}={$value}";
            }
            $options[CURLOPT_COOKIE] = implode('; ', $cookiesToSend);
        }

        curl_setopt_array($this->resource, $options);
    }

    /**
     * Method which parses cURL response
     * @return array|NULL
     */
    protected function parseResponse()
    {
        if (isset($this->response)) {
            list($responseParts['headersString'], $responseParts['body']) = explode("\r\n\r\n", $this->response, 2);
            $responseParts['body'] = htmlspecialchars($responseParts['body']);
            $headers = explode("\r\n", $responseParts['headersString']);

            $cookies = [];
            if (preg_match_all('/Set-Cookie: (.*?)=(.*?)(\n|;)/i', $responseParts['headersString'], $matches)) {
                if (!empty($matches)) {
                    foreach ($matches[1] as $key => $value) {
                        $cookies[$value] = $matches[2][$key];
                    }
                    $responseParts['cookies'] = $cookies;
                }
            }
            unset($responseParts['headersString']);

            $first = true;
            foreach ($headers as $header) {
                if ($first) {
                    list($responseParts['protocol'], $responseParts['statusCode']) = explode(' ', $header, 2);
                    $first = false;
                } else {
                    $tmp = (explode(': ', $header));
                    if ($tmp[0] === 'Set-Cookie') {
                        continue;
                    } else {
                        $responseParts['headersArray'][$tmp[0]] = $tmp[1];
                    }
                }
            }
            return $responseParts;
        } else {
            return null;
        }
    }

    /**
     * Validate that a value is a valid URL.
     * @param  mixed $value
     * @return bool
     */
    protected function isValidUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}