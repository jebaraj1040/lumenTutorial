<?php
if (empty($transactionCode) === false && $transactionCode == 200) {
    $status_msg = "<img src=$successImg class='pay-congratz'>Your Payment Transaction was Successful!";
} elseif (
    empty($transactionCode) === false &&
    ($transactionCode == '502' || $transactionCode == '400'
        || $transactionCode == '402' || $transactionCode == '403'
        || $transactionCode == '405' || $transactionCode == '503')
) {

    $status_msg = "<img src=$failureImg class='pay-failimg'>Looks like the payment transaction is failed.";
} elseif (empty($transactionCode) === false && ($transactionCode == '422')) {
    $status_msg =
        "<img src=$failureImg class='pay-failimg'>Looks like the payment
        transaction is failed. Payment amount is mismatch !!!";
} elseif (empty($transactionCode) === false && ($transactionCode == '401')) {
    $status_msg =
        "<img src=$failureImg class='pay-failimg'>Looks like the payment
        transaction is failed. User authentication failed !!!";
} else {
    $status_msg = "<img src=$failureImg>Something went Wrong!";
}
$sec = 10;
header('Refresh: 3; URL=' . $redirectURL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>SHFL-Journey</title>
    <link rel="icon" type="image/x-icon" href="<?php $websiteURL ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo $websiteURL . 'product-journey/payment/payment.css' ?>">
    <style>
        header {
            display: flex;
            box-shadow: 0px 0px 3px #ccc;
            border: none !important;
        }

        header .head-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
        }

        .cops_other_logo {
            max-width: 130px;
        }

        .summary-pay-sec {
            height: calc(100vh - 170px);
        }

        .summary-pay-sec .row,
        .summary-pay-sec {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .jumbotron.text-center {
            text-align: center;
            display: flex;
            flex-direction: column;
        }

        .jumbotron p {
            font-weight: bold;
            margin-top: 2%;
        }

        .pay-congratz {
            width: 100px;
            margin: 0 auto;
            display: block;
            margin-bottom: 10px;
        }

        .jumbotron h2 {
            font-size: 20px;
            padding: 10px 0;
        }

        .pay-failimg {
            width: 65px;
            margin: 0 auto;
            display: block;
            margin-bottom: 30px;
        }

        @media only screen and (max-width: 480px) {
            .jumbotron.text-center {
                padding: 10px 30px;
            }

            .jumbotron h2 {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .jumbotron p {
                margin-top: 0;
                font-size: 14px;
            }

            .pay-congratz,
            .pay-failimg {
                margin-bottom: 10px;
            }


        }

        footer .footer_section {
            background: #202525;
            text-align: center;
            padding: 15px 0;
            margin: 40px 0 0;
            border-top: 1px solid #bbbbbb;
        }

        footer .footer_section p {
            font-size: 12px;
            font-family: GilmerMedium, sans-serif;
            color: #fff;
            margin: 0;
        }

        .gap-10 {
            gap: 10px;
        }

        .calltxt_other p {
            font-family: GilmerRegular, sans-serif;
            margin: 0;
        }

        .calltxt_other a {
            font-size: 16px;
            font-family: GilmerBold, sans-serif;
            color: #009cb5;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="main_sec bg_back">
        <div class="main_right_section cops_other_div">
            <header>
                <div class="head-wrap container">
                    <div class="cops_other_logo">
                        <a title="Home">
                            <img src="<?php echo $websiteURL . 'assets/images/shfl-logo.svg' ?> " alt=""></a>
                    </div>
                    <div class="cops_other_right d-flex align-item-center gap-10">
                        <img src="
                        <?php echo $websiteURL . 'assets/housing-journey/images/icons/call.svg' ?>" alt="call" title="Call Us">
                        <div class="calltxt_other">
                            <p>Toll Free Number</p>
                            <a class="black-txt"> 1800-103-6116</a>
                        </div>
                    </div>
                </div>
            </header>
            <main class="maincontainer">
                <section class="summary-pay-sec container">
                    <div class="">
                        <div class="jumbotron text-center">
                            <?php echo $status_msg; ?> <br />
                            <h2>You are being re-directed in a few seconds.</h2>
                            <p> PLEASE DO NOT PRESS BACK OR REFRESH OR CLOSE THIS WINDOW.</p>
                        </div>
                    </div>
                </section>
            </main>
            <footer>
                <div class="footer_section">
                    <p>&#169;
                        Copyright © 2024 - All Rights Reserved - Official
                        website of Shriram Housing Finance Corporation of India.</p>
                </div>
            </footer>
        </div>
    </div>
</body>