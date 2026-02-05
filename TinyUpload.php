<?php

class TinyUpload
{
    protected string $basePath;

    public function __construct(
        protected string $adminToken,
        protected string $publicToken
    ) {
        session_start();
        //
        set_time_limit(0);
        ignore_user_abort(true);
        //
        $this->basePath = __DIR__.DIRECTORY_SEPARATOR.'storage';
        $this->mkdir($adminToken);
        $this->mkdir($publicToken);
    }

    public function path(...$path)
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath, ...$path]);
    }

    public function mkdir(...$path)
    {
        $p = $this->path(...$path);

        if (file_exists($p)) {
            return 200;
        }

        if (mkdir($p, recursive: true)) {
            return 201;
        }

        return 500;
    }

    public function login($token)
    {
        if ($this->isPublic($token)) {
            return false;
        }

        if ($this->adminToken === $token) {
            $_SESSION['token'] = $token;

            return true;
        }
        if (is_dir($this->path($token))) {
            $_SESSION['token'] = $token;

            return true;
        }

        return false;
    }

    public function logout()
    {
        session_destroy();
    }

    public function isAdmin(): bool
    {
        return $this->getToken() === $this->adminToken;
    }

    public function isPublic($token): bool
    {
        return $token === $this->publicToken;
    }

    public function getToken()
    {
        if (empty($_SESSION['token'])) {
            return null;
        }

        return $_SESSION['token'];
    }

    public function createToken($token)
    {
        $this->mkdir($token);
    }

    public function scandir($path)
    {
        return array_diff(scandir($path), ['.', '..']);
    }

    public function filesize($path)
    {
        $b = filesize($path);

        $kb = $b / 1024;
        if ($kb < 1) {
            return number_format($b, 2).' b';
        }

        $mb = $kb / 1024;
        if ($mb < 1) {
            return number_format($kb, 2).' kb';
        }

        $gb = $mb / 1024;
        if ($gb < 1) {
            return number_format($mb, 2).' mb';
        }

        return number_format($b, 2) / 1024;
    }

    public function listFiles($token)
    {
        $result = [];
        try {
            $dirs = scandir($this->path($token));
            foreach (array_diff($dirs, ['.', '..']) as $fileName) {
                $path = $this->path($token, $fileName);
                $result[$fileName] = [
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
        $result = [$this->publicToken => $this->publicToken];
        if ($this->getToken()) {
            $result[$this->getToken()] = $this->getToken();
            if ($this->isAdmin()) {
                foreach ($this->scandir($this->path()) as $token) {
                    $result[$token] = $token;
                }
            }
        }

        return $result;
    }

    public function list()
    {
        $result = [];
        foreach ($this->listTokens() as $token) {
            $result[$token] = $this->listFiles($token);
        }

        return $result;
    }

    public function uploadFile($tmpFile)
    {
        $token = $this->getToken();
        if (empty($token)) {
            return null;
        }
        if ($this->isPublic($token)) {
            return null;
        }

        try {
            $fileName = basename($tmpFile['name']);
            $path = $this->path($token, $fileName);

            if (move_uploaded_file($tmpFile['tmp_name'], $path)) {
                return $fileName;
            }
        } catch (\Throwable $th) {
            exit($th->getMessage());
        } catch (\Exception $th) {
            exit($th->getMessage());
        }

        return null;
    }

    public function uploadUrl($url)
    {
        $token = $this->getToken();
        if (empty($token)) {
            return null;
        }
        if ($this->isPublic($token)) {
            return null;
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
                $fileName = basename(parse_url($url, PHP_URL_PATH));
                $path = $this->notExistsFileName($token, $fileName);
                file_put_contents($path, $response);

                return pathinfo($path);
            }
        } catch (\Throwable $th) {
        }

        return null;
    }

    public function notExistsFileName($token, $fileName)
    {
        $info = pathinfo($fileName);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.'.$info['extension'] : '';

        $i = 1;
        do {
            $newName = $i === 1 ? ($name.$ext) : ($name." ($i)".$ext);
            $path = $this->path($token, $newName);
            $i++;
        } while (file_exists($path));

        return $path;
    }

    public function streamDownload($token, $fileName)
    {
        $path = $this->path($token, $fileName);
        ob_clean();
        ob_end_flush();
        header('Content-Type: application/octet-stream');
        header('Content-disposition: attachment; filename="'.$fileName.'"');
        readfile($path);
    }

    public function deletePermission($token)
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->getToken() === $token) {
            return true;
        }

        return false;
    }

    public function deleteExist($token, $fileName)
    {
        return in_array($fileName, $this->scandir($this->path($token)));
    }

    public function canDelete($token, $fileName)
    {
        if (! $this->deletePermission($token)) {
            return false;
        }

        if (! $this->deleteExist($token, $fileName)) {
            return false;
        }

        return true;
    }

    public function delete($token, $fileName)
    {
        if (! $this->canDelete($token, $fileName)) {
            return 403;
        }

        if (unlink($this->path($token, $fileName))) {
            return 200;
        }

        return 500;
    }

    public function moveToPermission($token)
    {
        if ($this->isPublic($token)) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if ($this->getToken() === $token) {
            return true;
        }

        return false;
    }

    public function moveToExist($token, $fileName)
    {
        return in_array($fileName, $this->scandir($this->path($token)));
    }

    public function canMoveTo($token, $fileName)
    {
        if (! $this->moveToPermission($token)) {
            return false;
        }

        if (! $this->moveToExist($token, $fileName)) {
            return false;
        }

        return true;
    }

    public function moveTo($token, $fileName)
    {
        if (! $this->canMoveTo($token, $fileName)) {
            return 403;
        }

        if (rename(
            $this->path($token,  $fileName),
            $this->notExistsFileName($this->publicToken, $fileName)
        )) {
            return 200;
        }

        return 500;
    }

    public function moveFromPermission($token)
    {
        if (! $this->isPublic($token)) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return false;
    }

    public function moveFromExist($token, $fileName)
    {
        return in_array($fileName, $this->scandir($this->path($token)));
    }

    public function canMoveFrom($token, $fileName)
    {
        if (! $this->moveFromPermission($token)) {
            return false;
        }

        if (! $this->moveFromExist($token, $fileName)) {
            return false;
        }

        return true;
    }

    public function moveFrom($token, $fileName)
    {
        if (! $this->canMoveFrom($token, $fileName)) {
            return 403;
        }

        if (rename(
            $this->path($this->publicToken,  $fileName),
            $this->notExistsFileName($this->getToken(), $fileName)
        )) {
            return 200;
        }

        return 500;
    }
}
