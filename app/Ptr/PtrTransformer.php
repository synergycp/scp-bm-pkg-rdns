<?php

namespace Packages\Rdns\App\Ptr;

use App\Api\Transformer;

class PtrTransformer
extends Transformer
{
    /**
     * @param Ptr $item
     *
     * @return array
     */
    public function item(Ptr $item)
    {
        return $item->expose('id', 'ip', 'ptr', 'name') + [
            'entity' => $this->itemEntity($item),
        ];
    }

    public function itemPreload($items)
    {
        $items->load('entity.owner.server');
    }

    /**
     * @param Ptr $item
     *
     * @return array
     */
    public function resource(Ptr $item)
    {
        return $this->item($item) + [
        ];
    }

    private function itemEntity(Ptr $item)
    {
        if (!$item->entity) {
            return null;
        }

        return $item->entity->expose([
            'id',
            'name',
        ]) + [
            'server' => $this->entityServer($item->entity),
        ];
    }

    /**
     * The server the entity is assigned to, when its owner is a server
     * port (entities can also be unassigned).
     *
     * @param \App\Entity\Entity $entity
     *
     * @return array|null
     */
    private function entityServer($entity)
    {
        $owner = $entity->getOwner();

        if (!$owner || !method_exists($owner, 'server') || !$owner->server) {
            return null;
        }

        return $owner->server->expose(['id', 'name']);
    }
}
