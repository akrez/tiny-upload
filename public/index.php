<?php

set_time_limit(0);

include '../TinyUpload.php';

$tinyUpload = new TinyUpload('asd');
if (! empty($_POST['signin'])) {
    $tinyUpload->signin($_POST['signin']);
    header('Refresh:0');
}
if (! empty($_POST['signout'])) {
    $tinyUpload->signout();
    header('Refresh:0');
}
if (! empty($_POST['upload_url'])) {
    $tinyUpload->uploadUrl($_POST['upload_url']);
    header('Refresh:0');
}
if (! empty($_POST['signup'])) {
    $tinyUpload->signup($_POST['signup']);
    header('Refresh:0');
}
if (! empty($_POST['delete_file'])) {
    $info = json_decode(base64_decode($_POST['delete_file']), true);
    $tinyUpload->delete($info['is_share'], $info['token'], $info['file_name']);
    header('Refresh:0');
}
if (! empty($_POST['share'])) {
    $info = json_decode(base64_decode($_POST['share']), true);
    $tinyUpload->share($info['token'], $info['file_name']);
    header('Refresh:0');
}
if (! empty($_POST['unsahre'])) {
    $info = json_decode(base64_decode($_POST['unsahre']), true);
    $tinyUpload->unshare($info['token'], $info['file_name']);
    header('Refresh:0');
}
if (! empty($_GET['stream_download'])) {
    $info = json_decode(base64_decode($_GET['stream_download']), true);
    $tinyUpload->streamDownload($info['is_share'], $info['token'], $info['file_name']);
    exit;
}
if (! empty($_FILES['file']['tmp_name'])) {
    $tinyUpload->uploadFile($_FILES['file']);
    header('Refresh:0');
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

            <div class="row mt-3">
                <div class="col-12">
                    <form method="POST">
                        <div class="input-group">
                            <span class="input-group-text">Token</span>
                            <?php if ($tinyUpload->getToken()) { ?>
                                <input type="password" class="form-control" disabled="disabled">
                                <button class="btn btn-danger" type="submit" name="signout" value="signout">Signout</button>
                            <?php } else { ?>
                                <input type="password" class="form-control" name="signin">
                                <button class="btn btn-primary" type="submit">Signin</button>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($tinyUpload->getToken()) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST">
                            <div class="input-group">
                                <span class="input-group-text">Url</span>
                                <input type="text" class="form-control" name="upload_url">
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
                                <button class="btn btn-primary" type="submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <?php if ($tinyUpload->isAdmin()) { ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="POST">
                            <div class="input-group">
                                <span class="input-group-text">Token</span>
                                <input type="text" class="form-control" name="signup">
                                <button class="btn btn-success" type="submit">Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

            <div class="row mt-3">
                <div class="col-12">
                    <table class="table table-bordered table-sm align-middle font-monospace">
                        <tbody>
                        <?php foreach ($tinyUpload->list() as $tokenName => $tokens) { ?>
                             <tr class="table-secondary ">
                                <td><?php if (empty($tokenName)) {
                                    echo '🟢 Share';
                                } elseif ($tokenName === $tinyUpload->getToken()) {
                                    echo '🟡 Yours';
                                } else {
                                    echo '🟠 Others';
                                } ?></td>
                                <td colspan="99"><?= $tokenName ?></td>
                            </tr>
                        <?php foreach ($tokens as $file) { ?>
                            <tr>
                                <td>
                                    <a href="./?stream_download=<?= base64_encode(json_encode($file)) ?>"><?= $file['file_name'] ?></a>
                                </td>
                                <td><?= $file['date'] ?></td>
                                <td><?= $file['size'] ?></td>
                                <td>
                                    <?php if ($tinyUpload->canShare($file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="share" value="<?= base64_encode(json_encode($file)) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Share</button>
                                        </form>
                                    <?php } ?>
                                    <?php if ($tinyUpload->canUnshare($file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="unsahre" value="<?= base64_encode(json_encode($file)) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">UnShare</button>
                                        </form>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if ($tinyUpload->canDelete($file['is_share'], $file['token'], $file['file_name'])) { ?>
                                        <form method="POST">
                                            <input type="hidden" name="delete_file" value="<?= base64_encode(json_encode($file)) ?>">
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
</body>

</html>