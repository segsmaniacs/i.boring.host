<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SeaweedStorage
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function assign()
    {
        try {
            $res = $this->client->request('GET', env('SEAWEED_MOTHER') . '/dir/assign');
        } catch (GuzzleException $e) {
            dd($e);
        }
        if ($res->getStatusCode() != 200) {
            return false;
        }

        $file = json_decode($res->getBody());

        if ($file) {
            return $file;
        }

        return false;
    }


    public function upload($file, $filePath)
    {
        try {
            $res = $this->client->request('POST', $file->url . '/' . $file->fid, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                    ]
                ]
            ]);
        } catch (GuzzleException $e) {
            dd($e);
        }

        if ($res->getStatusCode() != 200 && $res->getStatusCode() != 201) {
            return false;
        }

        $res = json_decode($res->getBody());

        return [
            'assign' => $file,
            'file' => $res
        ];
    }

    public function delete($fileId)
    {
        $volumeUrl = $this->getVolumeUrl(explode(',',$fileId)[0]);

        if (!$volumeUrl) {
            return false;
        }
        try {
            $res = $this->client->request('DELETE', $volumeUrl . '/' . $fileId);
        } catch (GuzzleException $e) {
            dd($e);
        }

        if (!$res->getStatusCode() === 200 && !$res->getStatusCode() === 202) {
            return false;
        }

        return true;
    }

    protected function getVolumeUrl($volumeId)
    {
        try {
            $res = $this->client->request('GET', env('SEAWEED_MOTHER') . '/dir/lookup?volumeId=' . $volumeId);
        } catch (GuzzleException $e) {
            dd($e);
        }

        if ($res->getStatusCode() !== 200) {
            return false;
        }

        $res = json_decode($res->getBody());

        return $res->locations[0]->url;
    }

    public function getImageContents($image)
    {
        $volumeID = explode(',', $image->image)[0];

        try {
            $volume = $this->client->request('GET', env('SEAWEED_MOTHER') . "/dir/lookup?volumeId=" . $volumeID);
        } catch (GuzzleException $e) {
            return $e;
        }

        $locationURL = json_decode($volume->getBody())->locations[0]->url;
//        dd('http://' . $locationURL . '/' . $image->image);
//        try {
//            $contents = $this->client->request('http://' . $locationURL . '/' . $image->image);
//        } catch (GuzzleException $e) {
//            return $e;
//        }
//
//        return $contents->getBody();

        return file_get_contents('http://' . $locationURL . '/' . $image->image);
    }
}