<?php

namespace Packages\Rdns\App\Ptr;

use App\Database\Models;
use App\Entity\Entity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Database storage and retrieval of Ptrs.
 *
 * @property string      ip
 * @property string      ptr
 * @property int         entity_id
 * @property Entity|null entity
 */
class Ptr
    extends Models\Model
{
    use PtrSearch;

    public static $singular = 'Ptr';
    public static $plural = 'Ptrs';
    public static $controller = 'pkg.rdns.ptr';

    public $timestamps = false;

    protected $table = 'pkg_rdns_ptrs';

    protected $casts = [
    ];

    protected $fillable = [
        'ip', 'ptr', 'entity_id',
    ];

    /**
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->ip;
    }

    /**
     * The Entity that the reported IP Address is in.
     *
     * @return BelongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}
