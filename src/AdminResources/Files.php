<?php

namespace Dcblogdev\MsGraph\AdminResources;

use Dcblogdev\MsGraph\Facades\MsGraphAdmin;
use Exception;
use GuzzleHttp\Client;

class Files extends MsGraphAdmin
{
    private $userId;

    public function userid($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    public function getFiles($siteid, $path)
    {
        $path =  '/sites/'.$siteid.'/drive/root:'.$path.':/children';

        return MsGraphAdmin::get($path);
    }

    public function createFolder($name, $siteid, $path)
    {
        $path =  '/sites/'.$siteid.'/drive/root:'.$path.':/children';

        return MsGraphAdmin::post($path, [
            'name'                              => $name,
            'folder'                            => new \stdClass(),
            '@microsoft.graph.conflictBehavior' => 'rename',
        ]);

    }

    public function upload($name, $uploadPath, $path = null, $siteid, $behavior = 'rename')
    {
        $uploadSession = $this->createUploadSession($name, $path, $siteid, $behavior);
        $uploadUrl     = $uploadSession['uploadUrl'];

        $fragSize       = 320 * 1024;
        $file           = file_get_contents($uploadPath);
        $fileSize       = strlen($file);
        $numFragments   = ceil($fileSize / $fragSize);
        $bytesRemaining = $fileSize;
        $i              = 0;
        $ch             = curl_init($uploadUrl);
        while ($i < $numFragments) {
            $chunkSize = $numBytes = $fragSize;
            $start     = $i * $fragSize;
            $end       = $i * $fragSize + $chunkSize - 1;
            $offset    = $i * $fragSize;
            if ($bytesRemaining < $chunkSize) {
                $chunkSize = $numBytes = $bytesRemaining;
                $end       = $fileSize - 1;
            }
            if ($stream = fopen($uploadPath, 'r')) {
                // get contents using offset
                $data = stream_get_contents($stream, $chunkSize, $offset);
                fclose($stream);
            }

            $content_range = ' bytes '.$start.'-'.$end.'/'.$fileSize;
            $headers       = [
                'Content-Length' => $numBytes,
                'Content-Range'  => $content_range,
            ];

            $client   = new Client;
            $response = $client->put($uploadUrl, [
                'headers' => $headers,
                'body'    => $data,
            ]);

            $bytesRemaining = $bytesRemaining - $chunkSize;
            $i++;
        }

        return $response;
    }

    protected function createUploadSession($name, $path = null, $siteid, $behavior = 'rename')
    {
        //$path = $path === null ? $type."/drive/root:/$name:/createUploadSession" : $type.'/drive/root:'.$this->forceStartingSlash($path)."/$name:/createUploadSession";
        $path = '/sites/'.$siteid.'/drive/root:'.$path.'/' .$name.':/createUploadSession';

        return MsGraphAdmin::post($path, [
            'item' => [
                '@microsoft.graph.conflictBehavior' => $behavior,
                'name'                              => $name,
            ],
        ]);
    }


    public function getDrives()
    {
        if ($this->userId == null) {
            throw new Exception('userId is required.');
        }

        return MsGraphAdmin::get('users/'.$this->userId.'/drives');
    }

    public function downloadFile($id)
    {
        if ($this->userId == null) {
            throw new Exception('userId is required.');
        }

        $id = MsGraphAdmin::get('users/'.$this->userId.'/drive/items/'.$id);

        return redirect()->away($id['@microsoft.graph.downloadUrl']);
    }

    public function deleteFile($id)
    {
        if ($this->userId == null) {
            throw new Exception('userId is required.');
        }

        return MsGraphAdmin::delete('users/'.$this->userId.'/drive/items/'.$id);
    }
}
