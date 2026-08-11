<?php

use App\Support\Database\Migration;
use Illuminate\Support\Facades\DB;

/**
 * IPv6 PTR records used to be created without a linked entity because the
 * entity lookup only matched the IPv4 columns; link them to the entity
 * whose v6_address covers them so they show up in entity-scoped listings.
 */
class RelinkIpv6PtrsToEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $entities = DB::table('entities')
            ->whereNotNull('v6_address')
            ->where('v6_address', '!=', '')
            ->orderBy('id')
            ->get(['id', 'v6_address']);

        if ($entities->isEmpty()) {
            return;
        }

        DB::table('pkg_rdns_ptrs')
            ->whereNull('entity_id')
            ->whereRaw('LENGTH(ip) = 16')
            ->orderBy('id')
            ->chunkById(500, function ($ptrs) use ($entities) {
                foreach ($ptrs as $ptr) {
                    foreach ($entities as $entity) {
                        if (!$this->covers($entity->v6_address, $ptr->ip)) {
                            continue;
                        }

                        DB::table('pkg_rdns_ptrs')
                            ->where('id', $ptr->id)
                            ->update(['entity_id' => $entity->id]);
                        break;
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // The original entity_id values (all null) are not recoverable in a
        // meaningful way; leaving the links in place is harmless.
    }

    /**
     * Whether an entities.v6_address value (CIDR or bare address) covers a
     * binary IPv6 address.
     *
     * @param string $v6Address
     * @param string $bin
     *
     * @return bool
     */
    private function covers($v6Address, $bin)
    {
        if (!is_string($bin) || strlen($bin) !== 16) {
            return false;
        }

        $parts = explode('/', trim($v6Address), 2);
        $base = inet_pton($parts[0]);

        if (!is_string($base) || strlen($base) !== 16) {
            return false;
        }

        $prefix = isset($parts[1]) ? (int) $parts[1] : 128;
        if ($prefix < 0 || $prefix > 128) {
            return false;
        }

        $bytes = intdiv($prefix, 8);
        $bits = $prefix % 8;

        if ($bytes > 0 && strncmp($bin, $base, $bytes) !== 0) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $bits)) & 0xff;

        return (ord($bin[$bytes]) & $mask) === (ord($base[$bytes]) & $mask);
    }
}
