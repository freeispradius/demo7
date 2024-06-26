<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Error - FreeIspRadius</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <style>
        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }
    </style>
</head>

<body class="hold-transition skin-blue">
    <div class="container">
        <section class="content">
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div class="box box-danger box-solid">
                        <section class="content-header">
                            <h1 class="text-center">
                                {$error_title}
                            </h1>
                        </section>
                        <div class="box-body" style="font-size: larger;">
                            <center>
                                <img src="./ui/ui/images/error.png" class="img-responsive hidden-sm hidden-xs">
                            </center>
                            <br>
                            {$error_message}
                            <br>
                            Mikrotik troubleshooting: <br>
                            <ul>
                                <li>First step is to make sure your mikrotik has Internet</li>
                                <li>Make sure your api port in ip>services is 8728</li>
                                <li>Make sure Username and Password are correct</li>
                                <li>Make sure your freeispradius ovpn is connected. Check in interfaces the vpn(ovpn) is named freeispradius</li>
                            </ul>
                            <br>
                            <div class="embed-responsive embed-responsive-16by9">
                                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/7mZJ-eGdq44?si=IjqkT5lU1zhQDJDq" allowfullscreen></iframe>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                <a href="./update.php?step=4" class="btn btn-info btn-sm btn-block">Update Database</a>
                                <a href="{$_url}community#update" class="btn btn-success btn-sm btn-block">Update FreeIspRadius</a>
                            </div>
                            <br>
                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                <a href="https://wa.me/254769023642" target="_blank" class="btn btn-success btn-sm btn-block">Ask Support Line</a>
                                <a href="https://t.me/freeispradius" target="_blank" class="btn btn-primary btn-sm btn-block">Ask Telegram Community</a>
                            </div>
                            <br><br>
                            <a href="javascript::history.back()" onclick="history.back()" class="btn btn-warning btn-block">back</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <img src="./ui/ui/images/error.png" class="img-responsive hidden-md hidden-lg">
                </div>
            </div>
        </section>
    </div>
</body>

</html>
