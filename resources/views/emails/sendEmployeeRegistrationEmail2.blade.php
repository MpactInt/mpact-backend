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
            font-family: 'Avenir', lato, Arial !important;
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
            font-family: 'Avenir', lato, Arial !important;
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
                    <h2 class="hd">Welcome to Mpact International!</h2>
                    <h2>WORKPLACE INCLUSION AND EMPOWERMENT MADE REAL</h2>
                    <br>
                    <h2>But First ..</h2>
                    <br>
                    <!-- <div class="customer-name mb-4">Hi {{$maildata['name']}},</div> -->
                    
                    <div style="">
                        <div style="float:left;margin: 30px;"> 
                            <a href=""><img style=" height: 90px; border-radius: 50%;" src="{{asset('public/welcome-notes/building_psychological.jpg')}}" alt="Mpact"></a>
                        </div>
                        <h2>An Introduction</h2>
                        Mpact International is a personalized experience platform providing tools, tips, and every day nudges to help you be more of you – at work or at play. Your company has opted to join our platform and has invited you to register. 
                        <br> <br>    
                        Click the link below to get started.
                        <br />
                        <div class="mb-4" style="margin: 10px;">
                            <a class=" btn btn-primary" href="{{$maildata['link']}}">HEAD TO THE PLATFORM</a>
                        </div>
                    </div>
                    <br />
                    <div style="">
                        <div style="float:left;margin: 30px;"> 
                            <a href=""><img style=" height: 90px; border-radius: 50%;" src="{{asset('public/welcome-notes/lights.jpg')}}" alt="Mpact"></a>
                        </div>
                        <h2>Tell Us About You</h2>
                        Be sure to complete your profile to tell us more about you. Take the assessment to learn more about hidden aspects of yourself that impact your interactions. Write our care team to let us know what you want to learn.
                        <br />
                        <div class="mb-4" style="margin: 10px;">
                            <a class=" btn btn-primary" href="{{$maildata['link']}}">HEAD TO THE PLATFORM</a>
                        </div>
                    </div>
                    <br />
                    <div style="">
                        <div style="float:left;margin: 30px;"> 
                            <a href=""><img style=" height: 90px; border-radius: 50%;" src="{{asset('public/welcome-notes/purple_people.jpg')}}" alt="Mpact"></a>
                        </div>
                        <h2>Start Exploring</h2>
                        The platform is designed to engage at whatever level feels right for you – whether you choose to read, listen, or watch. We encourage you to attend an onboarding session to get acquainted with the platform. 
                        <br />
                        <div class="mb-4" style="margin: 10px;">
                            <a class=" btn btn-primary" href="{{$maildata['link']}}">HEAD TO THE PLATFORM</a>
                        </div>
                    </div>
                    <br />
                    <div class="bottom-fade-text">
                        If you did not sign up for this account. you can Ignore this email
                        and the account will be deleted automatically after 10 days.
                    </div>
                </div>
            </div>
        </div>
        <div class="email-footer">
            <div class="container">
                <div class="footer-logo">
                    <a href=""><img src="{{asset('public/images/logo.png')}}"></a>
                </div>
                <ul class="footer-socila-link mb-4 border-btm-list">
                    <li><a href="">LinkedIn</a></li>
                    <li><a href="">Twitter</a></li>
                    <li><a href="">Intagram</a></li>
                </ul>
                <p class="mb-4">If you have questions or need help, don -t hesitate to contact our <a href="" class="bottom-link">support team!</a> </p>
                <p class="mb-4">Maxi International Inc.<br /> 7040 Avenida Encesas Suite 104 Carlsbad, California 92011, USA</p>
                <ul class="footer-socila-link mb-4">
                    <li><a href="">Terms & conditions</a></li>
                    <li><a href="">Privacy policy</a></li>
                    <li><a href="">Contact us</a></li>
                </ul>
                <p class="mb-4">This message was sent to [Customer ernar I. tf you dons wart to receive these emails from Mpact International in the future, you can <a href="" class="bottom-link">edit your profile</a> or <a href="" class="bottom-link">unsubscribe</a> </p>
            </div>
        </div>
    </div>
</body>

</html>