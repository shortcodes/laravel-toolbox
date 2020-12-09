<?php

namespace Shortcodes\Toolbox\Traits\Tests;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

abstract class FormRequestTest extends TestCase
{
    protected $model;
    private $request;
    private $errors;
    protected $asUser = null;

    public function as(User $user)
    {
        $this->asUser = $user;

        return $this;
    }

    public function getUser()
    {
        if ($this->asUser) {
            return $this->asUser;
        }

        if (method_exists($this, 'asUser')) {
            return $this->asUser();
        }

        return null;
    }

    public function assertValidRequest()
    {
        try {
            $this->request->validateResolved();

            $this->assertTrue(true);
        } catch (ValidationException $exception) {
            $this->assertTrue(false);
        }

        return $this;
    }

    public function assertInvalidRequest()
    {
        try {
            $this->request->validateResolved();

            $this->assertFalse(true);
        } catch (ValidationException $exception) {

            $this->errors = $exception->errors();

            $this->assertTrue(true);
        }

        return $this;
    }

    public function assertAutorizedRequest()
    {
        try {
            $this->request->validateResolved();

            $this->assertTrue(true);
        } catch (UnauthorizedException $exception) {
            $this->assertTrue(false);
        } catch (AuthorizationException $exception) {
            $this->assertTrue(false);
        }

        return $this;
    }

    public function assertUnautorizedRequest()
    {
        try {
            $this->request->validateResolved();

            $this->assertTrue(false);
        } catch (UnauthorizedException $exception) {
            $this->assertTrue(true);
        } catch (AuthorizationException $exception) {
            $this->assertTrue(true);
        }

        return $this;
    }

    public function assertInvalidParameter($invalidParameters)
    {
        foreach (is_array($invalidParameters) ? $invalidParameters : [$invalidParameters] as $k => $rule) {

            if (is_array($rule)) {
                $rule = $k;
            }

            if (!isset($this->errors[$rule])) {
                $this->assertTrue(false);
            }
        }

        $this->assertTrue(true);


        return $this;
    }

    public function assertValidParameter($invalidParameters)
    {
        foreach (is_array($invalidParameters) ? $invalidParameters : [$invalidParameters] as $k => $rule) {

            if (is_array($rule)) {
                $rule = $k;
            }

            if (isset($this->errors[$rule])) {
                $this->assertTrue(false);
            }
        }

        $this->assertTrue(true);


        return $this;
    }

    public function prepareRequest($payload, $model = null)
    {
        $this->request = new $this->model([], [], [], [], [], ($model ? ['REQUEST_URI' => $this->getModelPath() . '/' . $model->id] : []));
        $this->request->setContainer(app());
        $this->request->setRedirector(app(\Illuminate\Routing\Redirector::class));
        $this->request->setUserResolver(function () {
            return $this->getUser();
        });

        $this->request->merge($payload);

        if ($model) {
            $this->resolveRoute();
        }

        return $this;
    }

    private function getModelPath()
    {
        return Str::kebab(Str::plural($this->getModelClassName()));
    }

    private function getModelClassName()
    {
        $explodedName = explode('_', Str::snake(class_basename($this->model)));
        unset($explodedName[0], $explodedName[count($explodedName)]);

        return implode('_', $explodedName);
    }

    private function resolveRoute()
    {
        $this->request->setRouteResolver(function () {
            return (new Route('PATCH', $this->getModelPath() . '/{' . $this->getModelClassName() . '}', []))->bind($this->request);
        });
    }


}
