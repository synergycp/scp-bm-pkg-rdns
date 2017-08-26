<?php

namespace Packages\Rdns\App\Ptr\Tests;

use Packages\Testing\App\Test\TestCase;

use App\Group\Group;
use App\Entity\Entity;
use Faker\Factory;

class PtrControllerTest extends TestCase
{
    /**
     * @var Group
     */
    private $group;

    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var Factory
     */
    private $faker;

    /**
     * @var mixed Response after created
     */
    private $ptr;

    /**
     * @var string
     */
    private $url = 'pkg/rdns/ptr';

    public function setUp()
    {
        parent::setUp();

        $this->authAdmin();

        $this->faker = Factory::create();

        $this->group = $this->factory('testing', Group::class)->create();
        $this->entity = $this->factory('testing', Entity::class)->create([
            'group_id' => $this->group->id,
        ]);
    }

    public function tearDown()
    {
        $this->deletePtr();
        $this->entity->delete();
        $this->group->delete();
        parent::tearDown();
    }

    public function testCreate()
    {
        $this->post($this->url(), [
            'ptr' => $this->faker->firstName,
            'ip' => $this->entity->range_end,
        ]);

        $this->ptr = $this->response->getData();
        $this->assertResponseOk();
    }

    public function testList()
    {
        $this->get($this->url());
        $this->assertResponseOk();
        $resp = $this->response->getData();
        $total = $resp->data->total;

        $this->testCreate();

        $this->get($this->url());
        $this->assertResponseOk();
        $resp = $this->response->getData();
        $this->assertEquals(++$total, $resp->data->total);
    }

    public function testShow()
    {
        $this->testCreate();

        $ptr = $this->response->getData();
        $this->get($this->url($ptr->data));
        $this->assertResponseOk();
    }

    public function testUpdate()
    {
        $this->testCreate();

        $ptr = $this->response->getData();

        $ptrAttr = $this->faker->firstName;
        $ipAttr = $this->faker->ipv4;

        $this->patch($this->url($ptr->data), [
            'ptr' => $ptrAttr,
            'ip' => $ipAttr,
        ]);

        $this->assertResponseOk();
        $resp = $this->response->getData();
        $this->assertEquals($ptrAttr, $resp->data->ptr);
        $this->assertEquals($ipAttr, $resp->data->ip);
    }

    public function deletePtr()
    {
        $this->delete($this->url($this->ptr->data));
        $this->assertResponseOk();
    }

    /**
     * @param null $ptr
     * @return string
     */
    protected function url($ptr = null)
    {
        return $ptr
            ? sprintf('%s/%d', $this->url, $ptr->id)
            : $this->url;
    }
}
