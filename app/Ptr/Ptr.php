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

    /**
     * @param string $value
     */
    public function setIpAttribute($value)
    {
        $this->attributes['ip'] = $value ? inet_pton($value) : null;
    }

    /**
     * @param string $value Hexadecimal representation of IP
     *
     * @return string|void
     */
    public function getIpAttribute($value)
    {
        if (strlen($value)) {
            return inet_ntop($value);
        }
    }
}
