<!DOCTYPE html>
<html>

<head>
    <title>Email Template</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Avenir', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        #app {
            width: 100%;
            min-height: 100vh;
        }

        .email-head {
            text-align: center;
            padding-top: 25px;
            background: #090446;
            padding-bottom: 15%;
        }

        .email-head a img {
            height: 90px;
        }

        .email-text .hd {
            font-weight: 600;
            font-size: 40px;
            margin-bottom: 1.5rem;
        }

        .email-body {
            background-color: #fff;
            min-height: 90vh;
            color: #090446;
            font-size: 1rem;
            line-height: 1.5;
        }

        .email-footer {
            line-height: 1.5;
        }

        .email-text {
            position: relative;
            top: -100px;
            background: #fff;
            padding: 5%;
            border-radius: 15px;
            -webkit-box-shadow: rgb(100 100 111 / 20%) 0px 7px 29px 0px;
            box-shadow: rgb(100 100 111 / 20%) 0px 7px 29px 0px;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .btn:hover {
            background: #feb95f !important;
            background-color: #feb95f !important;
            border-color: #feb95f !important;
            text-decoration: none;
        }

        .btn {
            font-style: normal;
            font-weight: 500;
            font-size: 20px;
            line-height: 25px;
            color: #FFFFFF;
            padding: 12px 30px;
            background: #C2095A;
            border-radius: 10px;
            border: 0;
            text-decoration: none;
            display: inline-block;
        }

        .container {
            padding: 0px 5%;
        }

        .bottom-fade-text {
            opacity: 0.7;
        }

        .email-footer {
            color: #090446;
            opacity: 0.9;
        }

        .footer-socila-link li a {
            color: #666;
            text-decoration: none;
            font-weight: 600;
            color: #090446;
            opacity: 0.9;
            padding-right: 15px;
            padding-left: 0;
            border-right: 2px solid #ccc;
            margin-right: 15px;
        }

        .footer-socila-link li:last-child a {
            border-right: none;
        }

        .footer-socila-link {
            list-style: none;
        }

        .footer-socila-link li {
            display: inline-block;
        }

        .bottom-link {
            color: #090446;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-logo img {
            height: 80px;
            margin-bottom: 30px;
        }

        @media (min-width: 1200px) {
            .container {
                max-width: 1140px;
                margin: auto;
            }
        }

        @media (max-width: 767px) {
            .email-body {
                font-size: 14px;
            }

            .btn {
                font-size: 14px;
            }

            .email-head {
                padding-bottom: 35%;
            }

            .footer-socila-link li a {
                padding-right: 10px;
                margin-right: 10px;
                font-size: 11px;
            }

            .email-footer {
                font-size: 12px;
                line-height: 1.3;
            }

            .email-text {
                padding: 8%;
            }
        }
    </style>
</head>

<body>
    <div id="app" style="width: 100%;min-height: 100vh;">
        <div class="email-section">
            <div class="email-head" style=" text-align: center;
            padding-top: 25px;
            background: #090446;
            padding-bottom: 15%;">
                <a href=""><img style=" height: 90px;" src="{{asset('public/images/logo.png')}}" alt="Mpact"></a>
            </div>
        </div>
        <div class="email-body">
            <div class="container">
                <div class="email-text">
                    <h2 class="hd">Reset your password</h2>
                    
                    <div class="customer-text mb-4">
                        Follow this link to reset your customer account password at Mpact
                        International. If you didn't request a new password, you can safely delete this
                        email.
                    </div>
                    <div class="mb-4">
                        <a class=" btn btn-primary" href="{{$maildata['link']}}">RESET YOUR PASSWORD</a>
                    </div>
                    <div class="bottom-fade-text">
                    If you have any questions, reply to this email or contact us at admin@mpact-int.com
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>