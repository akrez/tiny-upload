<?php

set_time_limit(0);

include '../TinyUpload.php';

$tinyUpload = new TinyUpload('asd');

$action = isset($_POST['action']) ? $_POST['action'] : null;
if ($action) {
    if (! empty($action['signin'])) {
        $tinyUpload->signin($action['signin']);
    }
    if (! empty($action['signout'])) {
        $tinyUpload->signout();
    }
    if (! empty($action['signup'])) {
        $tinyUpload->signup($action['signup']);
    }
    if (! empty($action['rename_file']['file'])) {
        $name = (empty($action['rename_file']['name']) ? '' : $action['rename_file']['name']);
        $info = json_decode(base64_decode($action['rename_file']['file']), true);
        $tinyUpload->renameFile($name, $info['is_share'], $info['token'], $info['file_name']);
    }
    if (! empty($action['delete_file'])) {
        $info = json_decode(base64_decode($action['delete_file']), true);
        $tinyUpload->deleteFile($info['is_share'], $info['token'], $info['file_name']);
    }
    if (! empty($action['delete_dir'])) {
        $info = json_decode(base64_decode($action['delete_dir']), true);
        $tinyUpload->deleteDir($info['token']);
    }
    if (! empty($action['share'])) {
        $info = json_decode(base64_decode($action['share']), true);
        $tinyUpload->share($info['token'], $info['file_name']);
    }
    if (! empty($action['unsahre'])) {
        $info = json_decode(base64_decode($action['unsahre']), true);
        $tinyUpload->unshare($info['token'], $info['file_name']);
    }
    if (! empty($action['upload_url'])) {
        $tinyUpload->uploadUrl($action['upload_url']);
    }
    if (! empty($action['upload_file'])) {
        if (! empty($_FILES['file']['tmp_name'])) {
            $tinyUpload->uploadFile($_FILES['file']);
        }
    }
    header('Refresh:0');
}
if (! empty($_GET['stream_download'])) {
    $info = json_decode(base64_decode($_GET['stream_download']), true);
    $tinyUpload->streamDownload($info['is_share'], $info['token'], $info['file_name']);
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <title>Akrez Tiny Upload</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./favicon.ico">
    <link href="./assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container text-center">
    <div class="row">
        <div class="col-sm-1">
        </div>
        <div class="col-sm-10">

            <?php if ($tinyUpload->canSignin()) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST">
                            <div class="input-group">
                                <span class="input-group-text">Token</span>
                                <input type="password" class="form-control" name="action[signin]">
                                <button class="btn btn-primary" type="submit">Signin</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <?php if ($tinyUpload->canSignout()) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST">
                            <div class="input-group">
                                <span class="input-group-text">Token</span>
                                <input type="password" class="form-control" disabled="disabled">
                                <button class="btn btn-danger" type="submit" name="action[signout]" value="signout">Signout</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>
                
            <?php if ($tinyUpload->canUpload()) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST">
                            <div class="input-group">
                                <span class="input-group-text">Url</span>
                                <input type="text" class="form-control" name="action[upload_url]">
                                <button class="btn btn-primary" type="submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" class="form-control" name="file">
                                <input type="hidden" name="action[upload_file]" value="file">
                                <button class="btn btn-primary" type="submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <?php if ($tinyUpload->canSignup()) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST">
                            <div class="input-group">
                                <span class="input-group-text">Token</span>
                                <input type="text" class="form-control" name="action[signup]">
                                <button class="btn btn-success" type="submit">Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="Table responsive Class">
                    <table class="table table-bordered table-sm align-middle font-monospace">
                        <tbody>
                        <?php foreach ($tinyUpload->list() as $tokenName => $tokens) { ?>
                             <tr class="table-secondary">
                                <td class="fw-bold"><?= ($tokenName ? $tokenName : '&nbsp;') ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    <?php if ($tinyUpload->canDeleteDir($tokenName)) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="action[delete_dir]" value="<?= base64_encode(json_encode(['token' => $tokenName])) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger w-100">Delete</button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php foreach ($tokens as $file) { ?>
                            <tr>
                                <td>
                                    <a class="text-decoration-none" href="./?stream_download=<?= base64_encode(json_encode($file)) ?>"><?= $file['file_name'] ?></a>
                                </td>
                                <td><?= $file['size'] ?></td>
                                <td><?= $file['date'] ?></td>
                                <td>
                                    <?php if ($tinyUpload->canRenameFile($file['is_share'], $file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" name="action[rename_file][name]">
                                                <input type="hidden" name="action[rename_file][file]" value="<?= base64_encode(json_encode($file)) ?>">
                                                <button class="btn btn-primary" type="submit">Rename</button>
                                            </div>
                                        </form>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if ($tinyUpload->canShare($file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="action[share]" value="<?= base64_encode(json_encode($file)) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Share</button>
                                        </form>
                                    <?php } ?>
                                    <?php if ($tinyUpload->canUnshare($file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="action[unsahre]" value="<?= base64_encode(json_encode($file)) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">UnShare</button>
                                        </form>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if ($tinyUpload->canDeleteFile($file['is_share'], $file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="action[delete_file]" value="<?= base64_encode(json_encode($file)) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger w-100">Delete</button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>