<?php

namespace Fyi\Cms\Http;

use GuzzleHttp\Client;
use Fyi\Cms\Exceptions\SDK\MissingTokenException;

class Rest
{
    protected $client;
    protected $base_url;
    protected $requestHeaders;

    public function __construct(string $base_url)
    {
        $this->base_url = $base_url;
        $this->requestHeaders = [];
        $this->client = new Client([
            'base_uri' => $this->base_url
        ]);
    }

    /**
     * Set Base url for the http client
     *
     */
    protected function setBaseUrl(string $base_url)
    {
        $this->base_url = $base_url;

        $this->client->setDefaultOption('base_uri', $this->base_url);
    }

    /**
     * Set Headers for the http client
     *
     */
    protected function setRequestHeaders(array $headers = [])
    {
        $this->requestHeaders = $headers;
    }

    /**
     * Add Header to the http client
     *
     */
    protected function addRequestHeader(string $key, string $value)
    {
        $this->requestHeaders[$key] = $value;
    }

    /**
     * Send a GET request with query parameters.
     *
     *
     * @param string $path           Request path
     * @param array  $parameters     GET parameters
     * @param array  $requestHeaders Request Headers
     */
    protected function get(string $path, array $parameters = [], array $requestHeaders = [])
    {
        try {
            if (isset($this->requestHeaders["Authorization"])) {
                if (count($parameters) > 0) {
                    $path .= '?' . http_build_query($parameters);
                }

                $response = $this->client->request('GET', $path, ['headers' => $this->requestHeaders]);
                $code     = $response->getStatusCode();
                $body     = json_decode($response->getBody()->getContents());

                return $this->convert_object_to_array($body);
            } else {
                throw new \Fyi\Cms\Exceptions\SDK\MissingTokenException;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exc) {
            throw new \Fyi\Cms\Exceptions\CmsAPIException(json_decode($exc->getResponse()->getBody()->getContents()));
        }
    }

    /**
     * Send a POST request with parameters.
     *
     * @param string $path Request path
     * @param array  $parameters POST parameters
     * @param array  $requestHeaders Request headers
     * @param array  $extra Request headers
     */
    protected function post(string $path, array $parameters = [], array $requestHeaders = [], array $extra = [])
    {
        try {
            if (isset($this->requestHeaders["Authorization"]) || $path === "init") {
                if ((str_contains($path, "init") !== false))
                    $options = [
                        'multipart' => $this->createRequestBody($parameters),
                        'headers' => $this->requestHeaders
                    ];
                else
                    $options = [
                        'form_params' => $parameters,
                        'headers' => $this->requestHeaders
                    ];

                if (!empty($extra)) {
                    $options = array_merge($options, $extra);
                }

                $response = $this->client->request('POST', $path, $options);
                $code     = $response->getStatusCode();
                $status   = $response->getReasonPhrase();

                if ($status !== "OK") {
                    throw new \Fyi\Cms\Exceptions\CmsAPIException($response->getBody()->getContents());
                }

                $body = json_decode($response->getBody()->getContents());
                return $this->convert_object_to_array($body);
            } else {
                throw new \Fyi\Cms\Exceptions\SDK\MissingTokenException;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exc) {
            throw new \Fyi\Cms\Exceptions\CmsAPIException(json_decode($exc->getResponse()->getBody()->getContents()));
        }
    }

    /**
     * Send a PUT request with parameters.
     *
     * @param string $path           Request path
     * @param array  $parameters     PUT parameters
     * @param array  $requestHeaders Request headers
     */
    protected function put(string $path, array $parameters = [], array $requestHeaders = [])
    {
        try {
            if (isset($this->requestHeaders["Authorization"])) {
                $options = [
                    'form_params' => $parameters,
                    'headers' => $this->requestHeaders
                ];

                $response = $this->client->request('PUT', $path, $options);
                $code     = $response->getStatusCode();
                $status   = $response->getReasonPhrase();

                if ($status !== "OK") {
                    throw new \Fyi\Cms\Exceptions\CmsAPIException($response->getBody()->getContents());
                }

                $body = json_decode($response->getBody()->getContents());
                return $this->convert_object_to_array($body);
            } else {
                throw new \Fyi\Cms\Exceptions\SDK\MissingTokenException;
            }
        } catch (\GuzzleHttp\Exception\ClientException $exc) {
            throw new \Fyi\Cms\Exceptions\CmsAPIException(json_decode($exc->getResponse()->getBody()->getContents()));
        }
    }

    /**
     * Send a DELETE request with parameters.
     *
     * @param string $path           Request path
     * @param array  $requestHeaders Request headers
     */
    protected function delete(string $path, array $requestHeaders = [])
    {
        if (isset($this->requestHeaders["Authorization"])) {
            $options = [
                'headers' => $this->requestHeaders
            ];

            $response = $this->client->request('DELETE', $path, $options);
            $code     = $response->getStatusCode();
            $status = $response->getReasonPhrase();

            if ($status !== "OK") {
                throw new \Fyi\Cms\Exceptions\CmsAPIException($response["error"]["message"], $code);
            }

            $body = json_decode($response->getBody()->getContents());
            return $this->convert_object_to_array($body);
        } else {
            throw new \Fyi\Cms\Exceptions\SDK\MissingTokenException;
        }
    }

    /**
     * Prepare a set of key-value-pairs to be encoded as multipart/form-data.
     *
     * @param array $parameters Request parameters
     */
    private function createRequestBody(array $parameters)
    {
        $resources = [];

        foreach ($parameters as $key => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                $resources[] = [
                    'name'     => $key,
                    'contents' => $value,
                ];
            }
        }

        return $resources;
    }

    function convert_object_to_array($obj)
    {
        if (is_object($obj)) $obj = (array) $obj;
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = $this->convert_object_to_array($val);
            }
        } else $new = $obj;

        return $new;
    }
}
