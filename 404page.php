<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>404 Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Arvo">
    <style>
        body {
            margin: 0;
            font-family: 'Arvo', serif;
            background: #fff;
        }

        .page_404 {
            padding: 40px 0;
            text-align: center;
        }

        /* 404 heading on top */
        .page_404 h1 {
            font-size: 100px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        /* GIF section */
        .four_zero_four_bg {
            background-image: url(https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif);
            height: 400px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
        }

        /* Content under gif */
        .contant_box_404 {
            margin-top: 20px;
        }

        .contant_box_404 h3 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #222;
        }

        .contant_box_404 p {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
        }

        /* Button */
        .link_404 {
            color: #fff !important;
            padding: 12px 25px;
            background: #39ac31;
            border-radius: 25px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
        }

        .link_404:hover {
            background: #2e8b26;
            text-decoration: none;
        }

        @media (max-width: 992px) {
            .page_404 h1 {
                font-size: 80px;
            }

            .contant_box_404 h3 {
                font-size: 24px;
            }
        }

        @media (max-width: 768px) {
            .four_zero_four_bg {
                height: 300px;
            }

            .page_404 h1 {
                font-size: 60px;
            }

            .contant_box_404 h3 {
                font-size: 22px;
            }

            .contant_box_404 p {
                font-size: 15px;
            }

            .link_404 {
                font-size: 14px;
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .four_zero_four_bg {
                height: 220px;
            }

            .page_404 h1 {
                font-size: 45px;
            }

            .contant_box_404 h3 {
                font-size: 20px;
            }

            .contant_box_404 p {
                font-size: 14px;
            }

            .link_404 {
                font-size: 13px;
                padding: 8px 18px;
            }
        }
    </style>
</head>

<body>
    <section class="page_404">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="col-sm-10 col-sm-offset-1 text-center">

                        <!-- 404 Heading above gif -->
                        <h1>404</h1>

                        <!-- Gif -->
                        <div class="four_zero_four_bg"></div>

                        <!-- Text and Button -->
                        <div class="contant_box_404">
                            <h3>Looks like you're lost</h3>
                            <p>The page you are looking for is not available!</p>
                            <a href="index.php" class="link_404">Go to Home</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>