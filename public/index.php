<?php
include '../TinyUpload.php';

$tinyUpload = new TinyUpload('asd', 'public');
if (! empty($_POST['signin'])) {
    $tinyUpload->signin($_POST['signin']);
    header('Refresh:0');
}
if (! empty($_POST['signout'])) {
    $tinyUpload->signout();
    header('Refresh:0');
}
if (! empty($_POST['url'])) {
    $tinyUpload->uploadUrl($_POST['url']);
    header('Refresh:0');
}
if (! empty($_POST['signup'])) {
    $tinyUpload->signup($_POST['signup']);
    header('Refresh:0');
}
if (! empty($_POST['delete_file'])) {
    $tokenAndFileName = json_decode(base64_decode($_POST['delete_file']), true);
    $tinyUpload->delete($tokenAndFileName['token'], $tokenAndFileName['file_name']);
    header('Refresh:0');
}
if (! empty($_POST['move_to_public'])) {
    $tokenAndFileName = json_decode(base64_decode($_POST['move_to_public']), true);
    $tinyUpload->moveTo($tokenAndFileName['token'], $tokenAndFileName['file_name']);
    header('Refresh:0');
}
if (! empty($_POST['move_from_public'])) {
    $tokenAndFileName = json_decode(base64_decode($_POST['move_from_public']), true);
    $tinyUpload->moveFrom($tokenAndFileName['token'], $tokenAndFileName['file_name']);
    header('Refresh:0');
}
if (! empty($_GET['stream_download'])) {
    $tokenAndFileName = json_decode(base64_decode($_GET['stream_download']), true);
    $tinyUpload->streamDownload($tokenAndFileName['token'], $tokenAndFileName['file_name']);
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>Yeap!</title>
    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
                                <input type="text" class="form-control" name="url">
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
                    <table class="table table-bordered table-sm align-middle">
                        <tbody>
                        <?php foreach ($tinyUpload->list() as $tokenName => $tokens) { ?>
                            <tr class="table-secondary">
                                <td class="font-monospace" colspan="99"><?= $tokenName ?></td>
                            </tr>
                            <?php foreach ($tokens as $file) { ?>
                                    <tr class="">
                                        <td class="font-monospace">
                                            <a href="./?stream_download=<?= base64_encode(json_encode([
                                                'token' => $tokenName,
                                                'file_name' => $file['file_name'],
                                            ])) ?>"><?= $file['file_name'] ?></a>
                                        </td>
                                        <td class="font-monospace"><?= $file['date'] ?></td>
                                        <td class="font-monospace"><?= $file['size'] ?></td>
                                        <td class="font-monospace">
                                            <?php if ($tinyUpload->canDelete($tokenName, $file['file_name'])) { ?>
                                                <form method="POST">
                                                    <input type="hidden" name="delete_file" value="<?= base64_encode(json_encode([
                                                        'token' => $tokenName,
                                                        'file_name' => $file['file_name'],
                                                    ])) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            <?php } ?>
                                        </td>
                                        <td class="font-monospace">
                                            <?php if ($tinyUpload->canMoveTo($tokenName, $file['file_name'])) { ?>
                                                <form method="POST">
                                                    <input type="hidden" name="move_to_public" value="<?= base64_encode(json_encode([
                                                        'token' => $tokenName,
                                                        'file_name' => $file['file_name'],
                                                    ])) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Move To Public</button>
                                                </form>
                                            <?php } ?>
                                            <?php if ($tinyUpload->canMoveFrom($tokenName, $file['file_name'])) { ?>
                                                <form method="POST">
                                                    <input type="hidden" name="move_from_public" value="<?= base64_encode(json_encode([
                                                        'token' => $tokenName,
                                                        'file_name' => $file['file_name'],
                                                    ])) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Move From Public</button>
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