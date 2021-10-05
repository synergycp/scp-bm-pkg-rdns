<?php

namespace Packages\Rdns\App\Ptr;

use App\Entity\Entity;
use App\Ip\IpAddressRangeContract;
use App\Ip\IpAddressV4;
use App\Server\Port\ServerPortWithEntitiesTestNetwork;
use Illuminate\Http\JsonResponse;
use Packages\Rdns\App\RdnsTestCase;

function dns_get_record($ptr, $type = null) {
    $ips = array(
        array('host' => 'test_int_ptr_name','ip'=> '1.1.1.1', 'type' => 'A'),
        array('host' => 'test_create','ip'=> '1.1.1.1', 'type' => 'A'),
        array('host' => 'test_int_ptr_name','ip'=> '1.1.1.2', 'type' => 'A'),
        array('host' => 'test_create','ip'=> '1.1.1.2', 'type' => 'A'),
        array('host' => 'test_int_ptr_name','ip'=> '1.1.1.3', 'type' => 'A'),
        array('host' => 'test_create','ip'=> '1.1.1.3', 'type' => 'A'),
        array('host' => 'test_int_ptr_name','ip'=> '5.5.5.5', 'type' => 'A'),
        array('host' => 'test_create','ip'=> '5.5.5.5', 'type' => 'A'),
        array('host' => 'test_ext_ptr_name','ip'=> '8.8.8.8', 'type' => 'A'),
        array('host' => 'test_create','ip'=> '8.8.8.8', 'type' => 'A'),
    );
    $array = array();
    foreach($ips as $ip){      
      if($ip['host'] == $ptr){
        array_push($array, $ip);
      }
    }
    return $array;
}

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
  }

  public function tearDown() {
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

  public function testInvalidPtrIpOnCreate(){
    $this->asAdminWithPermissions(static::PERMISSIONS, function () {
      $ip = "9.9.9.9";
      $this->tryCreate(['ip' => $ip]);
      $this->assertResponseStatus(409);
    });
  }

  protected function tryCreate(array $data = []): JsonResponse {
    return $this->post($this->url(), $data + ['ptr' => 'test_create']);
  }

  private function startEntityRange(): string {
    return (string) $this->ipRange->start();
  }

  private function endEntityRange(): string {
    return (string) $this->ipRange->end();
  }

  protected function canCreateInsideEntityRange(): JsonResponse {
    $this->expectsEvents(Events\PtrCreated::class);
    $ip = $this->endEntityRange();
    $result = $this->tryCreate(['ip' => $ip]);

    $this->assertResponseOk();
    $this->seeJson(['ptr' => 'test_create', 'ip' => $ip]);
    return $result;
  }

  protected function canCreateOutsideEntityRange(): JsonResponse {
    $this->expectsEvents(Events\PtrCreated::class);
    $result = $this->tryCreate(['ip' => ($ip = '5.5.5.5')]);

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

  private function dontSeeInternalPTR(): void {
    $this->dontSeeJson([
      'ptr' => 'test_int_ptr_name',
      'ip' => $this->startEntityRange(),
    ]);
  }

  private function dontSeeExternalPTR(): void {
    $this->dontSeeJson([
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
    $this->get($this->url());
    $this->assertResponseOk();
    $this->dontSeeInternalPTR();

    $this->get($this->url() . "/" . $this->ptr->id);
    $this->assertResponseStatusOneOf([404, 403]);
  }

  protected function cannotReadOutsideEntityRange(): void {
    $this->get($this->url());
    $this->assertResponseOk();
    $this->dontSeeExternalPTR();

    $this->get($this->url() . "/" . $this->externalPtr->id);
    $this->assertResponseStatusOneOf([404, 403]);
  }

  protected function cannotCreateInsideEntityRange(): JsonResponse {
    $resp = $this->tryCreate(['ip' => $this->endEntityRange()]);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotCreateOutsideEntityRange(): JsonResponse {
    $resp = $this->tryCreate(['ip' => '5.5.5.5']);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotUpdateInsideEntityRange(): JsonResponse {
    $resp = $this->patch($this->url($this->ptr), [
      'ptr' => 'test_edit',
    ]);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotUpdateOutsideEntityRange(): JsonResponse {
    $resp = $this->patch($this->url($this->externalPtr), [
      'ptr' => 'test_edit',
    ]);
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function canUpdateInsideEntityRange(): JsonResponse {
    $this->expectsEvents(Events\PtrPtrUpdated::class);
    $resp = $this->patch($this->url($this->ptr), [
      'ptr' => 'test_edit',
    ]);
    $this->assertResponseOk();
    $this->assertEquals('test_edit', $resp->getData()->data->ptr);
    $this->assertEquals($this->ptr->ip, $resp->getData()->data->ip);
    return $resp;
  }

  protected function canUpdateOutsideEntityRange(): JsonResponse {
    $this->expectsEvents(Events\PtrPtrUpdated::class);

    $resp = $this->patch($this->url($this->externalPtr), [
      'ptr' => 'test_edit',
    ]);
    $this->assertResponseOk();
    $this->assertEquals('test_edit', $resp->getData()->data->ptr);
    $this->assertEquals($this->externalPtr->ip, $resp->getData()->data->ip);
    return $resp;
  }

  protected function canDeleteInsideEntityRange(): JsonResponse {
    $this->expectsEvents(Events\PtrDeleted::class);
    $resp = $this->delete($this->url($this->ptr));
    $this->assertResponseOk();
    return $resp;
  }

  protected function canDeleteOutsideEntityRange(): JsonResponse {
    $this->expectsEvents(Events\PtrDeleted::class);

    $resp = $this->delete($this->url($this->externalPtr));
    $this->assertResponseOk();
    return $resp;
  }

  protected function cannotDeleteInsideEntityRange(): JsonResponse {
    $resp = $this->delete($this->url($this->ptr));
    $this->assertResponseStatusOneOf([404, 403]);
    return $resp;
  }

  protected function cannotDeleteOutsideEntityRange(): JsonResponse {
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
}
