<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 96px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">Laravel 5</div>
                <img src="http://localhost:8000/api/v1/media/users/profile/max/1452936876_johninvictus.png" alt="image" width="100px" height="100px">

                <img src="data:image/jpeg;base64,{{ base64_encode('http://localhost:8000/api/v1/media/users/profile/max/1452936876_johninvictus.png" alt="image" width="100px" height="100px') }}">
            </div>
        </div>
    </body>
</html>
