<?php

namespace Packages\Rdns\App\Ptr;

use App\Database\Models\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;

trait PtrSearch
{
    use Searchable {
        findWord as searchFindWord;
    }

    /**
     * The ip column stores the binary (inet_pton) form, so it cannot be
     * matched with a plain text LIKE; it is handled in findWord() instead.
     * Columns are table-qualified because the client listing joins entities,
     * which also has an ip column.
     *
     * @var array
     */
    protected $searchCols = [
        'pkg_rdns_ptrs.ptr',
    ];

    /**
     * Do a raw plain text search with a query string.
     */
    public function findWord(Builder $query, string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        $query = $this->searchFindWord($query, $search);

        return $this->matchIp($query, $search);
    }

    /**
     * @param Builder $query
     * @param string  $search
     *
     * @return Builder
     */
    private function matchIp(Builder $query, string $search): Builder
    {
        $ipCol = $this->getTable() . '.ip';

        // A complete IPv4/IPv6 address in any notation: exact binary match.
        if (filter_var($search, FILTER_VALIDATE_IP)) {
            return $query->orWhere($ipCol, inet_pton($search));
        }

        // Partial address: compare against the presentation form of the stored
        // binary, which is exactly the text shown in the UI (INET6_NTOA renders
        // the same canonical form as PHP's inet_ntop).
        $driver = $query->getQuery()->getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $query->orWhereRaw(
                "INET6_NTOA({$ipCol}) LIKE ?",
                ['%' . strtolower($search) . '%']
            );
        }

        return $query;
    }
}
