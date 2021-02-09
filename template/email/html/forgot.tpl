<!DOCTYPE html>
<html>
<head>
    <title>{$data.title}</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400&display=swap" rel="stylesheet">
    <link href="https://fonts.google.com/specimen/Poppins?selection.family=Open+Sans" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.2/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cantarell|Lato|Montserrat:800|Noto+Sans+HK:900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <style type="text/css">
        body {
            padding: 0;
            background: #F5F5F5;
            font-family: 'Poppins', sans-serif;
        }
        p {
            color: #989898;
        }
        a {
            color: #6F2302;
            font-weight: 500;
        }
        a:hover {
            color: #591C02;
            text-decoration: none;
        }
        .wrapper {
            width: 100%;
            margin: 0px;
            padding: 0px !important;
            position: relative;
        }
        footer.footer {
            background: #e04805;
            margin: 22px auto !important;
            min-height: 200px;
            text-align: left;
            font-size: 18px;
            color: #fff !important;
            /*padding: 14px 0;*/
        }
        .content {
            background: #fff;
            padding: 0;
            margin: auto;
            width: 60%;
        }
        footer.footer ul li {
            display: inline-block !important;
        }
        footer.footer ul li a {
            color: #fff !important;
        }
        footer.footer ul.footer_link {
            padding: 12px;
            margin: 1em auto;
        }
        footer.footer ul.footer_link li {
            padding: 9px;
        }
        footer.footer ul.social_icns {
            padding: 12px;
            margin: 1em auto;
        }
        footer.footer ul.social_icns li {
            /*text-align: right !important;*/
            padding: 15px;
        }
        footer.footer ul.icons_app {
            padding: 12px;
            margin: auto;
            width: 100% !important;
        }
        footer.footer ul.icons_app li {
            /*text-align: right !important;*/
            padding: 6px;
            display: inline-block !important;
        }
        footer.footer ul.icons_app li a img {
            width: 40%;
            display: inline-block !important;
        }
        footer.footer .text-center {
            color: #fff !important;
            font-weight: 600;
            margin: 2em auto;
        }
        footer.footer .app_images {
            width: 100%;
            margin: 1.5em auto;
            padding:  !important;
            position: relative;
        }

        footer.footer .app_images .col1 {
            width: 45%;
            float: right;
        }

        footer.footer .app_images .col2 {
            width: 45%;
            float: right;
        }

        footer.footer .app_images .col1 img {
            width: 90%;
            float: left;
        }
        footer.footer .app_images .col2 img {
            width: 90%; float: right;
        }

        .border {
            width: 92%;
            margin: auto;
            border: 1px solid rgba(60,60,60,0.07) !important;
        }
        .text {
            width: 100%;
            position: relative;
            line-height: 35px;
            font-family: 'Poppins', sans-serif;
        }
        .text p {
            line-height: 25px;
            color: #989898;
        }
        .text h3 {
            color: #591C02;
            font-family: 'Roboto';
            font-weight: 600;
        }
        .text h5 {
            color: #591C02;
            font-family: 'Roboto';
            font-weight: 600;
        }
        button.btn {
            font-weight: 500;
            font-size: 16px;
        }
        span.OTP {
            font-size: 45px;
            letter-spacing: 12px;
            font-weight: 900;
            color: #6F2302;
            word-break: break-all;
        }
        @media (max-width: 640px) {
            .content {
                background: #fff;
                padding: 0;
                margin: auto !important;
                width: 85% !important;
            }
            footer.footer .app_images .col1 {
                width: 45%;
                float: left;
                padding-left: 9px;
            }

            footer.footer .app_images .col2 {
                width: 45%;
                float: right;
                padding-right: 9px;
            }
            footer.footer .app_images {
                width: 100%;
                margin: auto;
                /*padding: 12px !important;*/
            }
            footer.footer {
                text-align: center !important;
            }
            footer.footer ul.social_icns li a {
                padding: 12px;
                /*  float: left !important;*/
                text-align: center !important;
            }
        }
        /*.wrapper {
          width: 100%;
        }*/
    </style>
</head>
<body>
<div class="wrapper">
    <div class="content">
        <div class="container">
            <div class="row pl-5 p-3">
                <div class="col-md-4">
                    <img src="{base_url($header.logo.small.origin)}" class="img-fluid" style="margin-top: 2em; margin-bottom: 1em;" alt="MoneyDrop Logo">
                </div>
                <div class="col-md-8">
                    <!-- this is empty -->
                </div>
            </div>
            <div class="border"></div>
            <div class="text pl-5 p-3">
                <!-- Edit this Name and put the registered username -->
                <h3 class="mt-5">Hello there,</h3>
                <p>Please reset your password using the OTP below:</p>
                <span class="OTP">{$data.otp}</span>

            </div>
            <br>
            <div class="row-gutters pl-5 p-3">
                <p>Note: <em>This OTP will expire at {$data.expire}</em></p>
            </div>
            <div class="border"></div>
            <div class="row-gutters pl-5 p-3">
                <p>If you have further questions, kindly visit our <a href="{base_url($header.link.faq)}">FAQ Page</a>, or tweet at us <a href="{$header.social.twitter.url}">{$header.social.twitter.handle}</a></p>
            </div>
            <div class="row">
                <div class="col-md-6">

                </div>
                <div class="col-md-6">
                </div>
            </div>
            <div class="clearfix"></div>
        </div>

        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-sm-7">
                        <ul class="social_icns pl-4 p-3" style="width: 100%; margin-left: 6px;">
                            <li><a href="{$header.social.facebook.url}"><span class="fa fa-facebook"></span></a></li>
                            <li><a href="{$header.social.twitter.url}"><span class="fa fa-twitter"></span></a></li>
                            <li><a href="{$header.social.instagram.url}"><span class="fa fa-instagram"></span></a></li>
                        </ul>
                    </div>
                    <div class="col-sm-5">
                        <div class="app_images pr-4 p-3">
                            <a href="{$header.app_links.android.url}" class="col1">
                                <img src="{base_url($header.app_links.android.icon)}" class="Play store" >
                            </a>
                            <a href="{$header.app_links.ios.url}" class="col2">
                                <img src="{base_url($header.app_links.ios.icon)}" class="App store" >
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="text-center">
                    <p style="color: #fff;">&copy; {$header.name} {$header.year}</p>
                </div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>