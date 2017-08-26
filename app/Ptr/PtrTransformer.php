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
        $items->load('entity');
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
        return !$item->entity ? null : $item->entity->expose([
            'id',
            'name',
        ]);
    }
}
