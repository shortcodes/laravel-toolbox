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
        $this->json('GET', $this->getRoute('index'),
            $this->prepareData('indexQueryParams')
        )->assertStatus(200);
    }

    public function test_show_object()
    {
        $object = $this->makeObject(true);

        $response = $this->json('GET', $this->getRoute('show', $object->id));

        $response->assertStatus(200)->assertJson([
            'data' => [
                'id' => $object->id,
            ],
        ]);
    }

    public function test_store_object()
    {
        $response = $this->json('POST', $this->getRoute('store'), $this->prepareData());

        $response->assertStatus(201);
        $this->assertNotNull($response->getData()->data->id);
    }

    public function test_update_object()
    {
        $object = $this->makeObject(true);

        $response = $this->json('PATCH', $this->getRoute('update', $object->id), $this->prepareData());

        $response->assertStatus(200);
    }

    public function test_delete_object()
    {
        $object = $this->makeObject(true);

        $response = $this->json('DELETE', $this->getRoute('destroy', $object->id));

        $response->assertStatus(204);

        $this->assertNull($this->model::find($object->id));
    }

    private function makeObject($persist = false)
    {
        $factoryObject = $this->model::factory();

        if ($persist) {
            return $factoryObject->create();
        }

        return $factoryObject->make()->toArray();
    }

    private function getRoute($postfix, $objectId = null)
    {
        return route(Str::kebab(Str::plural(class_basename($this->model))) . '.' . $postfix,
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

}
