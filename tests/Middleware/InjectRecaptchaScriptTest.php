<?php

namespace DarkGhostHunter\Captchavel\Tests;

use Orchestra\Testbench\TestCase;

class InjectRecaptchaScriptTest extends TestCase
{

    protected function getPackageAliases($app)
    {
        return [
            'ReCaptcha' => 'DarkGhostHunter\Captchavel\Facades\ReCaptcha'
        ];
    }

    protected function getPackageProviders($app)
    {
        return ['DarkGhostHunter\Captchavel\CaptchavelServiceProvider'];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['env'] = 'local';

        $app->make('config')->set('captchavel.enable', true);
        $app->make('config')->set('captchavel.secret', 'test-secret');
        $app->make('config')->set('captchavel.key', 'test-key');

        $this->afterApplicationCreated(function () {

            $router = $this->app->make('router');

            $router->get('test-get', function () {
                return response()->make(/** @lang HTML */ <<<EOT
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <form action="test-action" data-recaptcha="true">
    </form>
</body>
</html>
EOT
                );
            });

            $router->get('invalid-html', function () {
                return response()->make('Hellow');
            });

            $router->get('json', function () {
                return response()->json('<head></head>');
            });

        });
    }

    public function testInjectsScriptAutomatically()
    {
        $this->get('test-get')
            ->assertSee('Start Captchavel Script')
            ->assertSee('api.js?render=test-key&onload=captchavelCallback');
    }

    public function testDoesntInjectsOnInvalidHtml()
    {
        $this->get('invalid-html')
            ->assertDontSee('Start Captchavel Script')
            ->assertDontSee('api.js?render=test-key&onload=captchavelCallback');
    }

    public function testDoesntInjectsOnJson()
    {
        $this->get('json')
            ->assertDontSee('Start Captchavel Script')
            ->assertDontSee('api.js?render=test-key&onload=captchavelCallback');
    }

    public function testDoesntInjectsOnAjax()
    {
        $this->get('test-get', [
            'X-Requested-With' => 'XMLHttpRequest'
        ])
            ->assertDontSee('Start Captchavel Script')
            ->assertDontSee('api.js?render=test-key&onload=captchavelCallback');
    }

    public function testDoesntInjectsOnException()
    {
        $response = $this->get('route-doesnt-exists-will-trigger-exception');

        $response->assertDontSee('Start Captchavel Script')
            ->assertDontSee('api.js?render=test-key&onload=captchavelCallback');
    }

}
