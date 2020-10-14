<?php

namespace Tests\Blueprints;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ApiCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->make())->withHeaders(['X-App-Token' => env('AUTH_KEY')]);
    }

    public function test_index_object()
    {
        $this->checkTestToRun();

        $this->json('GET', $this->getRoute(), $this->prepareData('indexQueryParams'))
            ->assertStatus(200);
    }

    public function test_show_object()
    {
        $this->checkTestToRun();

        $object = $this->makeObject(true);

        $response = $this->json('GET', $this->getRoute($object->id));

        $response->assertStatus(200)->assertJson([
            'data' => [
                'id' => $object->id,
            ],
        ]);
    }

    public function test_store_object()
    {
        $this->checkTestToRun();

        $response = $this->json('POST', $this->getRoute(), $this->prepareData());

        $response->assertStatus(201);
        $this->assertNotNull($response->getData()->data->id);
    }

    public function test_update_object()
    {
        $this->checkTestToRun();

        $object = $this->makeObject(true);

        $response = $this->json('PATCH', $this->getRoute($object->id), $this->prepareData());

        $response->assertStatus(200);
    }

    public function test_destroy_object()
    {
        $this->checkTestToRun();

        $object = $this->makeObject(true);

        $response = $this->json('DELETE', $this->getRoute($object->id));

        $response->assertStatus(204);

        $this->assertNull($this->model::find($object->id));
    }

    private function makeObject($persist = false)
    {
        $factoryObject = $this->model::factory();

        if ($persist) {
            return $factoryObject->create();
        }

        return array_merge($factoryObject->make()->toArray(), $this->customMutator());
    }

    private function getRoute($objectId = null)
    {

        return route(Str::kebab(Str::plural(class_basename($this->model))) . '.' . $this->getPrefix(),
            $objectId ? [Str::snake(class_basename($this->model)) => $objectId] : []
        );
    }

    private function prepareData($method = 'makeObject')
    {
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return [];
    }

    private function checkTestToRun()
    {
        if (isset($this->testOnly) && !in_array($this->getPrefix(), $this->testOnly)) {
            $this->markTestSkipped();
        }

        if (isset($this->testExcept) && in_array($this->getPrefix(), $this->testExcept)) {
            $this->markTestSkipped();
        }
    }

    private function getPrefix()
    {
        return str_replace(['test_', '_object'], ['', ''], $this->getName());
    }

    private function customMutator()
    {

        $mutatorMethodName = $this->getPrefix() . 'CustomMutator';

        if (in_array($this->getPrefix(), ['store', 'update']) && method_exists($this, $mutatorMethodName)) {
            return $this->$mutatorMethodName();
        }

        return [];
    }

}
