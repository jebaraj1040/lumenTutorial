<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Fjalla+One|Open+Sans" rel="stylesheet">
</head>

<body style="margin: 0 !important; padding: 0 !important;font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';">
    <div style="max-width: 600px;">
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">
            <a href="{{$urls}}" target="_blank" style="font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">{{$payLoad['snippet']}}</a>
        </p>
        @if($payLoad['email_template_handle'] != "exception-email-core")
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">Dear <?php echo ucwords(strtolower($name)) ?>,</p>
        @endif
        @if($payLoad['email_template_handle'] == "payment-l1")
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;"> You are just moments away from securing your loan from Shriram Housing Finance. To finalize your loan application with {{$payLoad['app_data']['quote_id']}}, we kindly request your prompt attention to complete the payment process by clicking on the link below:</p>
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;"> {{$urls}}</p>
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;"> This secure portal will facilitate your payment, allowing us to confirm your application swiftly.</p>
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;"> Should you require any assistance or have queries regarding the payment procedure, our dedicated support team is readily available. Reach out to us through any of the following options:</p>

        <ul style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">
            <li>Call us at 1800-102-4345</li>
            <li>Email us at contact@shriramhousing.in</li>
            <li>Chat with us on our website</li>
        </ul>

        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">Thank you for choosing Shriram Housing Finance Limited for your housing loan needs. We are committed to supporting you in fulfilling your aspirations and realizing your dreams.</p>
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">Thanks,<br>
            Shriram Housing Finance</p>
        @elseif($payLoad['email_template_handle'] == "document-upload")

        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">You are just a step away from realizing your dream of owning a home with Shriram Housing Finance Limited. However, to expedite the process, we require your cooperation in submitting the necessary KYC (Know Your Customer) documents for your application with the reference {{$payLoad['app_data']['quote_id']}}. To make this process seamless, please click on the link below to upload your documents securely:</p>
        <p style="margin-bottom:15px; font-size: 14px;  line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;"> {{$urls}}</p>

        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;"> If you encounter any difficulties or have questions regarding the KYC submission, our dedicated support team is ready to assist you. Reach out to us through any of the following options:</p>

        <ul style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">
            <li>Call us at 1800-102-4345</li>
            <li>Email us at contact@shriramhousing.in</li>
            <li>Chat with us on our website</li>
        </ul>
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">Thank you for choosing Shriram Housing Finance Limited and we are excited to be part of your homeownership journey.</p>
        <p style="margin-bottom:15px; font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">Thanks,<br>
            Shriram Housing Finance</p>
        @elseif($payLoad['email_template_handle'] == "exception-email-core")
        <div class="row" style="max-width: 100%;display: -ms-flexbox;display: flex; -ms-flex-wrap: wrap;flex-wrap: wrap;">
            <div class="col-xs-12 col-md-12" style="-ms-flex: 0 0 45%;max-width: 100%;padding-right: 15px; padding-left: 15px;">
                <p style="margin-top:10px; font-size: 14px;">{{$payLoad['api_url']}} {{$payLoad['error_message']}}</p>
            </div>
        </div>
        @endif
        @if($payLoad['email_template_handle'] != "exception-email-core")
        <p style="margin-bottom:15px; font-size: 12px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">Click to
            <a href="{{$payLoad['unsubscribe_url']}}" style="font-size: 14px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">unsubscribe</a>
        </p>
        @endif
        <p style="font-size: 12px; line-height:18px; font-family: 'Helvetica', 'Arial', sans-serif;">This is an auto-generated mail. Please do not reply</p>
    </div>
</body>

</html>