<?php

class TinyUpload
{
    const SHARE = 'share';

    const UNSHARE = 'unshare';

    protected string $basePath;

    public function __construct(protected string $adminToken)
    {
        session_start();
        //
        $this->basePath = __DIR__;
        $this->mkdir($this->path(true));
        $this->mkdir($this->path(false, $adminToken));
    }

    protected function path(bool $isShare, ?string $token = null, ?string $fileName = null)
    {
        $parts = [$this->basePath, 'storage'];
        //
        $parts[] = ($isShare ? static::SHARE : static::UNSHARE);
        //
        if (! $isShare) {
            if ($token !== null) {
                $parts[] = $token;
            }
        }
        if ($fileName !== null) {
            $parts[] = $fileName;
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    protected function mkdir($rawPath)
    {
        if (file_exists($rawPath)) {
            return 200;
        }

        if (mkdir($rawPath, recursive: true)) {
            return 201;
        }

        return 500;
    }

    public function signin(string $token)
    {
        if (is_dir($this->path(false, $token))) {
            $_SESSION['token'] = $token;

            return true;
        }

        return false;
    }

    public function signout()
    {
        session_destroy();
    }

    public function isAdmin(): bool
    {
        return $this->getToken() === $this->adminToken;
    }

    public function getToken()
    {
        return empty($_SESSION['token']) ? null : $_SESSION['token'];
    }

    public function signup(string $token)
    {
        $this->mkdir($this->path(false, $token));
    }

    public function scandir($rawPath)
    {
        return array_diff(scandir($rawPath), ['.', '..']);
    }

    public function filesize($rawPath)
    {
        $bytes = filesize($rawPath);
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 .' '.$sizes[$i];
    }

    public function listFiles($isShare, $token)
    {
        $result = [];
        try {
            $files = $this->scandir($this->path($isShare, $token));
            foreach ($files as $fileName) {
                $path = $this->path($isShare, $token, $fileName);
                $result[] = [
                    'is_share' => $isShare,
                    'token' => $token,
                    'file_name' => $fileName,
                    'size' => $this->filesize($path),
                    'date' => date('Y-m-d H:i:s', filectime($path)),
                ];
            }
        } catch (\Throwable $th) {
        } catch (\Exception $th) {
        }

        return $result;
    }

    public function listTokens()
    {
        $result = [];
        if ($this->getToken()) {
            $result[$this->getToken()] = $this->getToken();
            if ($this->isAdmin()) {
                foreach ($this->scandir($this->path(false)) as $token) {
                    $result[$token] = $token;
                }
            }
        }

        return $result;
    }

    public function list()
    {
        $result = [null => $this->listFiles(true, null)];
        foreach ($this->listTokens() as $token) {
            $result[$token] = $this->listFiles(false, $token);
        }

        return $result;
    }

    public function uploadFile($tmpFile)
    {
        $token = $this->getToken();
        if (empty($token)) {
            return 403;
        }

        try {
            $fileName = $this->normalizeFilename($tmpFile['name']);
            $path = $this->differenceFileName(false, $token, $fileName);
            if (move_uploaded_file($tmpFile['tmp_name'], $path)) {
                return 200;
            }
        } catch (\Throwable $th) {
        } catch (\Exception $th) {
        }

        return 500;
    }

    public function uploadUrl($url)
    {
        $token = $this->getToken();
        if (empty($token)) {
            return 403;
        }

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]);
            $response = curl_exec($curl);
            if ($response) {
                $fileName = $this->normalizeFilename($url);
                $path = $this->differenceFileName(false, $token, $fileName);
                file_put_contents($path, $response);

                return 200;
            }
        } catch (\Throwable $th) {
        } catch (\Exception $th) {
        }

        return 500;
    }

    public function differenceFileName($isShare, $token, $fileName)
    {
        $info = pathinfo($fileName);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.'.$info['extension'] : '';

        $i = 1;
        do {
            $newName = $i === 1 ? ($name.$ext) : ($name." ($i)".$ext);
            $path = $this->path($isShare, $token, $newName);
            $i++;
        } while (file_exists($path));

        return $path;
    }

    public function streamDownload($isShare, $token, $fileName)
    {
        $path = $this->path($isShare, $token, $fileName);

        ob_clean();
        ob_end_flush();

        $headerName = addcslashes($fileName, '"\\');
        $encoded = rawurlencode($fileName);

        header('Content-Type: application/octet-stream');
        header("Content-disposition: attachment; filename=\"{$headerName}\"; filename*=UTF-8''{$encoded}");

        readfile($path);
    }

    public function canDelete($isShare, $token, $fileName)
    {
        $isAllowed = ($this->isAdmin() || ($token && ($this->getToken() === $token)));
        if (! $isAllowed) {
            return false;
        }

        $isExists = in_array($fileName, $this->scandir($this->path($isShare, $token)));
        if (! $isExists) {
            return false;
        }

        return true;
    }

    public function delete($isShare, $token, $fileName)
    {
        if (! $this->canDelete($isShare, $token, $fileName)) {
            return 403;
        }

        if (unlink($this->path($isShare, $token, $fileName))) {
            return 200;
        }

        return 500;
    }

    public function canShare($token, $fileName)
    {
        if (empty($token)) {
            return false;
        }

        $isAllowed = ($this->isAdmin() || ($this->getToken() === $token));
        if (! $isAllowed) {
            return false;
        }

        $isExists = in_array($fileName, $this->scandir($this->path(false, $token)));
        if (! $isExists) {
            return false;
        }

        return true;
    }

    public function share($token, $fileName)
    {
        if (! $this->canShare($token, $fileName)) {
            return 403;
        }

        if (rename(
            $this->path(false, $token, $fileName),
            $this->differenceFileName(true, null, $fileName)
        )) {
            return 200;
        }

        return 500;
    }

    public function canUnshare($token, $fileName)
    {
        if (! empty($token)) {
            return false;
        }

        $isAllowed = ($this->isAdmin());
        if (! $isAllowed) {
            return false;
        }

        $isExists = in_array($fileName, $this->scandir($this->path(true)));
        if (! $isExists) {
            return false;
        }

        return true;
    }

    public function unshare($token, $fileName)
    {
        if (! $this->canUnshare($token, $fileName)) {
            return 403;
        }

        if (rename(
            $this->path(true, null, $fileName),
            $this->differenceFileName(false, $this->getToken(), $fileName)
        )) {
            return 200;
        }

        return 500;
    }

    private function normalizeFilename(string $filename)
    {
        $filename = basename($filename);
        $filename = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/', '_', $filename);
        $filename = rtrim($filename, '. ');
        if ($filename === '' || $filename === '.' || $filename === '..') {
            $filename = 'download';
        }

        return $filename;
    }
}
