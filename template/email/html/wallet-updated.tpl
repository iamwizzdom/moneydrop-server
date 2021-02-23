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
        div.footer {
            background: #e04805;
            padding: 2em;
            margin: 22px auto;
            min-height: 200px;
        }
        .content {
            background: #fff;
            padding: 0;
            margin: auto;
            width: 65%;
        }
        div.footer ul li {
            display: inline-block;
            padding: 9px;
        }
        div.footer ul li a {
            text-align: right !important;
            color: #fff !important;
        }
        div.footer ul.footer_link {
            width: 100% !important;
            margin: 5px auto;
            text-align: left;
        }
        div.footer ul.footer_link li {
            display: inline-block;
            padding: 6px;
        }
        div.footer ul.footer_link li a {
            text-align: center !important;
            color: #fff !important;
        }
        div.footer .text-center {
            color: #fff;
            font-weight: 600;
            margin: 2em auto;
        }
        .border {
            width: 92%;
            margin: auto;
            border-top: 1px dotted #F5F5F5 !important;
        }
        .text {
            width: 100%;
            position: relative;
            line-height: 35px;
            font-family: 'Poppins', sans-serif;
        }
        .text h3 {
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
        }
        @media (max-width: 640px) {
            .content {
                background: #fff;
                padding: 0;
                margin: auto !important;
                width: 85% !important;
            }
            div.footer {
                text-align: center;
            }
            ul.social_icns li a {
                padding: 12px;
                text-align: center !important;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="content">
        <div class="row pl-5 p-3">
            <div class="col-md-2">
                <img src="{$data.logo}" class="img-fluid" style="margin-top: 2em; margin-bottom: 1em;" alt="MoneyDrop Logo">
            </div>
            <div class="col-md-8">

            </div>
        </div>
        <div class="border"></div>
        <!-- <div class="clearfix"></div> -->
        <!-- <div class="clearfix"></div> -->
        <div class="text pl-5 p-3">
            <!-- Edit this Name and put the registered username -->
            <h3 class="mt-5">Hello {$data.name}</h3>
            <p>Your wallet on our platform was {$data.action} <b>{$data.currency} {$data.amount}</b>.</p>
            <h3>Your total available balance:</h3>
            <h2>{$data.currency} {$data.balance}</h2>
        </div>
        <div class="row p-3">
            <div class="col-md-3"></div>
            <div class="col-md-9"></div>
        </div>
        <br>
        <div class="border"></div>
        <div class="row-gutters pl-5 p-3">
            <!-- click that takes users to a how it works page for afrihow -->
            <p>Thanks for choosing {$header.name}</p>
        </div>

        <div class="footer mb-0">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-12">
                        <ul class="footer_link">
                            <li><a href="https://moneydrop.com/contact">Contact Us</a></li>
                            <li><a href="https://moneydrop.com/terms">Terms</a></li>
                            <li><a href="https://moneydrop.com/privacy">Privacy</a></li>
                        </ul>
                    </div>
                    <div class="col-md-6 col-12">
                        <ul class="social_icns">
                            <li><a href="https://facebook.com/#"><span class="fa fa-facebook"></span></a></li>
                            <li><a href="https://twitter.com/#"><span class="fa fa-twitter"></span></a></li>
                            <li><a href="https://instagram.com/#"><span class="fa fa-instagram"></span></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="text-center">
                    <p>&copy; MoneyDrop {$header.year}</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>