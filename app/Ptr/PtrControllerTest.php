<?php

namespace Packages\Rdns\App\Ptr;

use App\Entity\Entity;
use App\Ip\IpAddressRangeContract;
use App\Ip\IpAddressV4;
use App\Server\Port\ServerPortWithEntitiesTestNetwork;
use Illuminate\Foundation\Testing\TestResponse;
use Packages\Rdns\App\RdnsTestCase;

class PtrControllerTest extends RdnsTestCase {
  const PERMISSIONS = [
    'read' => 'network.entities.read',
    'write' => 'network.entities.write',
  ];

  /**
   * @var ServerPortWithEntitiesTestNetwork
   */
  private $network;

  /**
   * @var Ptr
   */
  private $ptr;

  /**
   * @var string
   */
  private $url = 'pkg/rdns/ptr';

  /**
   * @var IpAddressRangeContract
   */
  private $ipRange;

  /**
   * @return Entity
   */
  private $externalEntity;

  /**
   * @var Ptr
   */
  private $externalPtr;

  protected function createTestNetworks(): \Generator {
    $this->ipRange = IpAddressV4::range('1.1.1.1', '1.1.1.3');
    yield ($this->network = new ServerPortWithEntitiesTestNetwork());

    $entity = $this->network->entity();
    $entity->setIPv4Range($this->ipRange);
    $entity->save();

    $this->externalEntity = $this->factory('testing', Entity::class)->create();

    $this->ptr = new Ptr([
      'ptr' => 'test_int_ptr_name',
      'ip' => $this->startEntityRange(),
      'entity_id' => $entity->getKey(),
    ]);
    $this->ptr->save();

    $this->externalPtr = new Ptr();
    $this->externalPtr->ptr = 'test_ext_ptr_name';
    $this->externalPtr->ip = '8.8.8.8';
    $this->externalPtr->entity_id = $this->externalEntity->getKey();
    $this->externalPtr->save();

    $this->dns = $this->mockService(DnsRecordService::class);
  }

  public function tearDown(): void {
    $this->deletePtrs();
    $this->externalEntity->delete();
    parent::tearDown();
  }

  public function testAdminWithNoPerms() {
    // TODO: this is probably not precisely correct permission behavior.
    $this->asAdminWithPermissions([], function () {
      $this->canReadInsideEntityRange();
      $this->canReadOutsideEntityRange();
      $this->cannotCreateInsideEntityRange();
      $this->cannotCreateOutsideEntityRange();
      $this->canUpdateInsideEntityRange();
      $this->canUpdateOutsideEntityRange();
      $this->canDeleteInsideEntityRange();
      $this->canDeleteOutsideEntityRange();
    });
  }

  public function testAdminReadOnly() {
    // TODO: this is probably not precisely correct permission behavior.
    $this->asAdminWithPermissions([static::PERMISSIONS['read']], function () {
      $this->canReadInsideEntityRange();
      $this->canReadOutsideEntityRange();
      $this->canCreateInsideEntityRange();
      $this->canCreateOutsideEntityRange();
      $this->canUpdateInsideEntityRange();
      $this->canUpdateOutsideEntityRange();
      $this->canDeleteInsideEntityRange();
      $this->canDeleteOutsideEntityRange();
    });
  }

  public function testAdminWithPerms() {
    $this->asAdminWithPermissions(static::PERMISSIONS, function () {
      $this->canReadInsideEntityRange();
      $this->canReadOutsideEntityRange();
      $this->canCreateInsideEntityRange();
      $this->canCreateOutsideEntityRange();
      $this->canUpdateInsideEntityRange();
      $this->canUpdateOutsideEntityRange();
      $this->canDeleteInsideEntityRange();
      $this->canDeleteOutsideEntityRange();
    });
  }

  public function testClientWithoutAccess() {
    $this->asClient(function () {
      $this->cannotReadInsideEntityRange();
      $this->cannotReadOutsideEntityRange();
      $this->cannotCreateInsideEntityRange();
      $this->cannotCreateOutsideEntityRange();
      $this->cannotUpdateInsideEntityRange();
      $this->cannotUpdateOutsideEntityRange();
      $this->cannotDeleteInsideEntityRange();
      $this->cannotDeleteOutsideEntityRange();
    });
  }

  public function testClientWithAccess() {
    $this->asClientWithAccessTo($this->network->server(), function () {
      $this->canReadInsideEntityRange();
      $this->cannotReadOutsideEntityRange();
      $this->canCreateInsideEntityRange();
      $this->cannotCreateOutsideEntityRange();
      $this->canUpdateInsideEntityRange();
      $this->cannotUpdateOutsideEntityRange();
      $this->canDeleteInsideEntityRange();
      $this->cannotDeleteOutsideEntityRange();
    });
  }

  public function testListUpdatesOnCreate() {
    $this->asAdminWithPermissions(static::PERMISSIONS, function () {
      $this->get($this->url());
      $this->assertResponseOk();
      $this->assertResponseResultCount(2);

      $this->canCreateInsideEntityRange();

      $this->get($this->url());
      $this->assertResponseOk();
      $this->assertResponseResultCount(3);
    });
  }

  public function testPtrNotAssociatedWithIpOnCreate(){
    $this->asAdminWithPermissions(static::PERMISSIONS, function () {
      $this->mockDns('test_create', '1.1.1.3', 1);
      $ip = "9.9.9.9";
      $this->tryCreate(['ip' => $ip]);
      $this->assertResponseStatus(409);
      $this->assertMessageContains("Invalid PTR. Please ensure that test_create has an A or AAAA DNS record to 9.9.9.9.");
    });
  }

  protected function tryCreate(array $data = []): TestResponse {
    return $this->post($this->url(), $data + ['ptr' => 'test_create']);
  }

  private function startEntityRange(): string {
    return (string) $this->ipRange->start();
  }

  private function endEntityRange(): string {
    return (string) $this->ipRange->end();
  }

  protected function canCreateInsideEntityRange(): TestResponse {
    $this->mockDns('test_create', '1.1.1.3', 1);

    $ip = $this->endEntityRange();
    $result = $this->assertExpectedDispatched([Events\PtrCreated::class], function () use ($ip) {
      return $this->tryCreate(['ip' => $ip]);
    });

    $this->assertResponseOk();
    $this->seeJson(['ptr' => 'test_create', 'ip' => $ip]);
    return $result;
  }

  protected function canCreateOutsideEntityRange(): TestResponse {
    $this->mockDns('test_create', '5.5.5.5', 1);

    $ip = '5.5.5.5';
    $result = $this->assertExpectedDispatched([Events\PtrCreated::class], function () use ($ip) {
      return $this->tryCreate(['ip' => $ip]);
    });

    $this->assertResponseOk();
    $this->seeJson([
      'ptr' => 'test_create',
      'ip' => $ip,
    ]);
    return $result;
  }

  protected function canReadInsideEntityRange(): void {
    $this->get($this->url());
    $this->assertResponseOk();
    $this->seeInternalPTR();

    $this->get($this->url() . "/" . $this->ptr->id);
    $this->assertResponseOk();
    $this->seeInternalPTR();
  }

  private function seeInternalPTR(): void {
    $this->seeJson([
      'ptr' => 'test_int_ptr_name',
      'ip' => $this->startEntityRange(),
    ]);
  }

  private function seeExternalPTR(): void {
    $this->seeJson([
      'ptr' => 'test_ext_ptr_name',
      'ip' => '8.8.8.8',
    ]);
  }

  protected function canReadOutsideEntityRange(): void {
    $this->get($this->url());
    $this->assertResponseOk();
    $this->seeExternalPTR();

    $this->get($this->url() . "/" . $this->externalPtr->id);
    $this->assertResponseOk();
    $this->seeExternalPTR();
  }

  protected function cannotReadInsideEntityRange(): void {
    $this->get($this->url())
    ->assertSuccessful()
    ->assertJson(['data'=>[]]);

    $this->get($this->url() . "/" . $this->ptr->id);
    $this->assertResponseStatusOneOf([404, 403]);
  }

  protected function cannotReadOutsideEntityRange(): void {
    $this->get($this->url())
    ->assertSuccessful()
    ->assertJson(['data'=>[]]);

    $this->get($this->url() . "/" . $this->externalPtr->id);
    $this->assertResponseStatusOneOf([404, 403]);
  }

  protected function cannotCreateInsideEntityRange(): TestResponse {
    $this->mockDns('test_create', '1.1.1.3', 0);
    $resp = $this->tryCreate(['ip' => $this->endEntityRange()]);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotCreateOutsideEntityRange(): TestResponse {
    $this->mockDns('test_create', '5.5.5.5', 0);
    $resp = $this->tryCreate(['ip' => '5.5.5.5']);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotUpdateInsideEntityRange(): TestResponse {
    $this->mockDns('test_edit', '1.1.1.1', 0);
    $resp = $this->patch($this->url($this->ptr), [
      'ptr' => 'test_edit',
    ]);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotUpdateOutsideEntityRange(): TestResponse {
    $this->mockDns('test_edit', '8.8.8.8', 0);
    $resp = $this->patch($this->url($this->externalPtr), [
      'ptr' => 'test_edit',
    ]);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function canUpdateInsideEntityRange(): TestResponse {
    $this->mockDns('test_edit', '1.1.1.1', 1);
    $resp = $this->assertExpectedDispatched([Events\PtrPtrUpdated::class], function () {
      return $this->patch($this->url($this->ptr), [
        'ptr' => 'test_edit',
      ]);
    });
    $this->assertResponseOk();
    $this->assertEquals('test_edit', $resp->getData()->data->ptr);
    $this->assertEquals($this->ptr->ip, $resp->getData()->data->ip);
    return $resp;
  }

  protected function canUpdateOutsideEntityRange(): TestResponse {
    $this->mockDns('test_edit', '8.8.8.8', 1);
    $resp = $this->assertExpectedDispatched([Events\PtrPtrUpdated::class], function () {
      return $this->patch($this->url($this->externalPtr), [
        'ptr' => 'test_edit',
      ]);
    });
    $this->assertResponseOk();
    $this->assertEquals('test_edit', $resp->getData()->data->ptr);
    $this->assertEquals($this->externalPtr->ip, $resp->getData()->data->ip);
    return $resp;
  }

  protected function canDeleteInsideEntityRange(): TestResponse {
    $resp = $this->assertExpectedDispatched([Events\PtrDeleted::class], function () {
      return $this->delete($this->url($this->ptr));
    });
    $this->assertResponseOk();
    return $resp;
  }

  protected function canDeleteOutsideEntityRange(): TestResponse {
    $resp = $this->assertExpectedDispatched([Events\PtrDeleted::class], function () {
      return $this->delete($this->url($this->externalPtr));
    });
    $this->assertResponseOk();
    return $resp;
  }

  protected function cannotDeleteInsideEntityRange(): TestResponse {
    $resp = $this->delete($this->url($this->ptr));
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotDeleteOutsideEntityRange(): TestResponse {
    $resp = $this->delete($this->url($this->externalPtr));
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  private function deletePtrs(): void {
    Ptr::query()->delete();
  }

  /**
   * @param null $ptr
   * @return string
   */
  protected function url($ptr = null) {
    return $ptr ? sprintf('%s/%d', $this->url, $ptr->id) : $this->url;
  }

  private function mockDns($host, $ip, $times) {
    $this->dns
      ->shouldReceive('getARecords')
      ->with($host)
      ->times($times)
      ->andReturn(
        array(
          array("host"=> $host, "ip" => $ip, "type" => "A")
        )
      );
  }
}
