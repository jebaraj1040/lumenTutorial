<?php
require_once __DIR__ . '/../vendor/autoload.php';
(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/
$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/
$app->configure('app');
$app->configure('database');
$app->configure('journey/http-status');
$app->configure('crm/http-status');
$app->configure('journey/sms');
$app->configure('journey/productType');
$app->configure('constants/payment');
$app->configure('constants/apiSource');
$app->configure('constants/apiSourcePage');
$app->configure('constants/apiType');
$app->configure('constants/masterApiSource');
$app->configure('constants/masterApiStatus');
$app->configure('constants/masterApiType');
$app->configure('constants/payment');
$app->configure('constants/paymentStatus');
$app->configure('constants/apiStatus');
$app->configure('constants/otpMessage');
$app->configure('constants/productStepHandle');
$app->configure('constants/productName');
$app->configure('constants/processingFee');
$app->configure('constants/propertyCurrentStatus');
$app->configure('constants/employmentConstitutionType');
$app->configure('constants/productCode');
$app->configure('constants/smsAndEmailTemplateCode');
$app->configure('constants/customerQuery');
$app->configure('mail');
// dhanasekar
$app->configure('tinker');
/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/
$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'otpAuth' => App\Http\Middleware\OtpMiddleware::class,
    'journeyAuthToken' => App\Http\Middleware\JourneyAuthToken::class,
    'websiteAuthToken' => App\Http\Middleware\WebsiteAuthToken::class,
    'coreAuthToken' => App\Http\Middleware\CoreAuthToken::class,
    'localization' => App\Http\Middleware\Localization::class,
    'crmAuth' => App\Http\Middleware\CrmMiddleware::class,
    'ccAuthToken' => App\Http\Middleware\CCAuthToken::class,
    'ccInfoAuthToken' => App\Http\Middleware\CCInfoAuthToken::class,
    'paymentAuthToken' => App\Http\Middleware\PaymentAuthToken::class,
    'CheckOtpLimit' =>  App\Http\Middleware\CheckOtpLimit::class,
    'serviceAuthToken' =>  App\Http\Middleware\ServiceAuthToken::class

]);
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\LogServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class,);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class,);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(MongoDB\Laravel\MongoDBServiceProvider::class);
// dhanasekar
$app->register(\Laravel\Tinker\TinkerServiceProvider::class);

$app->withFacades();
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/
$app->router->group([
    'namespace' => 'App\Services',
], function ($router) {
    require_once __DIR__ . '/../routes/web.php';
    require_once __DIR__ . '/../routes/api/v1/housing-journey.php';
    require_once __DIR__ . '/../routes/api/v1/otp.php';
    require_once __DIR__ . '/../routes/api/v1/crm.php';
    require_once __DIR__ . '/../routes/api/v1/core.php';
    require_once __DIR__ . '/../routes/api/v1/website.php';
    require_once __DIR__ . '/../routes/api/v1/payment.php';
});
return $app;
