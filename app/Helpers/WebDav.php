<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WebDav
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(env('WEBDAV_USERNAME').':'.env('WEBDAV_PASSWORD'))
            ],
            'base_uri' => env('WEBDAV_URL')
        ]);

    }

    /**
     * Read a file from the web dav server
     *
     * @param $path
     * @return \Exception|GuzzleException|\Psr\Http\Message\StreamInterface
     */
    public function readFile($path)
    {
        try {
            $request = $this->client->request('GET', $path);
        } catch (GuzzleException $e) {
            return $e;
        }

        return $request->getBody();
    }

    /**
     * Creates a file on the web dav server
     *
     * @param $path
     * @param $content
     * @return bool|\Exception|GuzzleException
     */
    public function createFile($path, $content)
    {
        try {
            $request = $this->client->request('PUT', $path, [
                'body' => $content
            ]);
        } catch (GuzzleException $e) {
            return $e;
        }

        return $request->getStatusCode() == 200 || $request->getStatusCode() == 201;
    }


    /**
     * Deletes a file from the web dav server
     *
     * @param $path
     * @return bool|\Exception|GuzzleException
     */
    public function deleteFile($path)
    {
        try {
            $request = $this->client->request('DELETE', $path);
        } catch (GuzzleException $e) {
            return $e;
        }

        return $request->getStatusCode() == 200 || $request->getStatusCode() == 201;
    }

    /**
     * Creates a directory on the web dav server
     *
     * @param $dir
     * @return bool|\Exception|GuzzleException
     */
    public function makeDir($dir)
    {
        try {
            $request = $this->client->request('MKCOL', $dir);
        } catch (GuzzleException $e) {
            return $e;
        }

        return $request->getStatusCode() == 200 || $request->getStatusCode() == 201;
    }
}