<?php

namespace Packages\Rdns\App\Ptr\Zone;

use App\Api;
use Illuminate\Http\Request;
use Packages\Rdns\App\Ptr\PtrService;
use Packages\Rdns\App\Ptr\PtrFilterService;
use Packages\Rdns\App\Ptr\PtrRepository;

/**
 * Handle HTTP requests regarding PTR Zone.
 */
class ZoneController
    extends Api\Controller
{
    /**
     * @var PtrFilterService
     */
    private $filter;

    /**
     * @var PtrRepository
     */
    private $items;

    /**
     * @var Api\ApiAuthService
     */
    private $auth;

    /**
     * @var PtrService
     */
    private $ptr;

    /**
     * ZoneController constructor.
     *
     * @param PtrFilterService $filter
     * @param PtrRepository $items
     * @param Api\ApiAuthService $auth
     * @param PtrService $ptr
     */
    public function __construct(
        PtrFilterService $filter,
        PtrRepository $items,
        Api\ApiAuthService $auth,
        PtrService $ptr
    ) {
        $this->filter = $filter;
        $this->items = $items;
        $this->auth = $auth;
        $this->ptr = $ptr;
    }

    public function store(Request $request)
    {
        $this->filter->viewable($this->items->query());

        $this->auth->only([
            'admin',
            'integration',
        ]);

        $file = $request->file('file')->openFile();
        $ptrs = 0;
        $findPtr = '/([0-9a-fA-F]{1,3}(?:\.[0-9a-fA-F]{1,3})*)\s+IN\s+PTR\s+(.+)/S';
        // Reverse zone origins: 1-3 reversed octets for IPv4 (/8 through /24),
        // 1-31 reversed nibbles for IPv6.
        $findOriginV4 = '/\$ORIGIN\s+((?:[0-9]{1,3}\.){1,3})in-addr\.arpa/Si';
        $findOriginV6 = '/\$ORIGIN\s+((?:[0-9a-f]\.){1,31})ip6\.arpa/Si';
        $validHostname = '/^[a-zA-Z0-9._-]+\.?$/';
        $originLabels = null;
        $isV6 = false;
        $skipped = 0;

        while (!$file->eof()) {
            $line = $file->fgets();

            if ($originLabels === null) {
                if (preg_match($findOriginV4, $line, $matches)) {
                    $originLabels = explode('.', rtrim($matches[1], '.'));
                } elseif (preg_match($findOriginV6, $line, $matches)) {
                    $originLabels = explode('.', rtrim($matches[1], '.'));
                    $isV6 = true;
                }

                continue;
            }

            if (preg_match($findPtr, $line, $matches)) {
                $ptrValue = trim($matches[2]);

                if (!preg_match($validHostname, $ptrValue) || strlen($ptrValue) > 253) {
                    $skipped++;
                    continue;
                }

                $ip = $this->compileIp(
                    explode('.', $matches[1]),
                    $originLabels,
                    $isV6
                );

                if ($ip === null) {
                    $skipped++;
                    continue;
                }

                $ptrs++;
                $this->ptr->create($ip, $ptrValue);
            }
        }

        if ($originLabels === null) {
            return response()->error('Could not find $ORIGIN line. Please make sure the $ORIGIN is included.');
        }

        $message = sprintf('Zone imported: %d PTRs added.', $ptrs);

        if ($skipped > 0) {
            $message .= sprintf(' %d records skipped (invalid hostname or record name).', $skipped);
        }

        return response()->success($message);
    }

    /**
     * Build the full IP address from a PTR record name and the zone origin.
     * Reverse zone labels are least-significant first, with the record name
     * completing the address that the origin starts.
     *
     * @param array<string> $recordLabels
     * @param array<string> $originLabels
     * @param bool          $isV6
     *
     * @return string|null null when the labels do not form a valid address
     */
    private function compileIp(array $recordLabels, array $originLabels, $isV6)
    {
        $reversed = array_merge($recordLabels, $originLabels);

        if ($isV6) {
            if (count($reversed) !== 32) {
                return null;
            }

            foreach ($reversed as $nibble) {
                if (!preg_match('/^[0-9a-fA-F]$/', $nibble)) {
                    return null;
                }
            }

            $hex = implode('', array_reverse($reversed));
            $ip = implode(':', str_split($hex, 4));

            // Normalize to the compressed form the rest of the app displays.
            return inet_ntop(inet_pton($ip));
        }

        if (count($reversed) !== 4) {
            return null;
        }

        foreach ($reversed as $octet) {
            if (!ctype_digit($octet) || (int) $octet > 255) {
                return null;
            }
        }

        return implode('.', array_reverse($reversed));
    }
}
