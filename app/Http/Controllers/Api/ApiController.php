<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use BitWasp\Buffertools\Buffer;
use EthereumRawTx\Transaction;
use Web3\Web3;

/**
 * Class HealthController
 */
class ApiController extends Controller
{

    public function faucet($address)
    {
        if (!self::isValidAddress($address)) {
            return [
                'status' => 'error',
                'message' => 'Provided ethereum address is invalid. Please try again.'
            ];
        }

        $web3 = new Web3(config('app.settings.provider_url'));
        $eth = $web3->eth;
        $txcount = null;

        // Get the nonce of the sender
        $eth->getTransactionCount(self::addHexPrefix(config('app.settings.sender_pubkey')), function($e, $r) use (&$txcount) {
            if ($e) {
                return false;
            }

            $txcount = intval($r->toString());
            return true;
        });

        if ($txcount === null) {
            return [
                'status' => 'error',
                'message' => 'Something went wrong. Please try again.'
            ];
        }

        $nonce = Buffer::int($txcount);
        $to = Buffer::hex(self::removeHexPrefix($address));
        $sendValueRaw = gmp_strval(gmp_mul(config('app.settings.faucet_value'), gmp_pow(10, 18)));
        $value = Buffer::int($sendValueRaw);
        $data = null;
        $gasPrice = Buffer::int(0);
        $gasLimit = Buffer::int(21000);
        $pk = Buffer::hex(config('app.settings.sender_privkey'));
        $networkId = Buffer::int(0);

        // Prepare transaction object
        $tx = new Transaction(
            $to,
            $value,
            $data,
            $nonce,
            $gasPrice,
            $gasLimit
        );

        // Sign raw transaction
        $raw = $tx->getRaw($pk, $networkId);

        $txSend = [
            'status' => 'error',
            'message' => 'Something went wrong. Please try again.'
        ];

        // Send raw transaction
        if ($raw && $raw->getHex()) {
             $eth->sendRawTransaction(self::addHexPrefix($raw->getHex()), function($e, $r) use (&$txSend) {
                if ($e) {
                    $txSend['message'] = 'Transaction failed. Please try again.';
                    return false;
                }

                $txSend['status'] = 'ok';
                $txSend['message'] = $r;
                return true;
            });
        }

        return $txSend;
    }

    /**
     * Simple check if ETH address is valid
     *
     * @param      $address
     *
     * @return bool
     */
    public static function isValidAddress($address)
    {
        if (!self::hasHexPrefix($address)) {
            return false;
        }

        if (strlen($address) !== 42) {
            return false;
        }

        return ctype_xdigit(self::removeHexPrefix($address));
    }


    /**
     * Check for 0x prefix
     *
     * @param $str
     *
     * @return bool
     */
    public static function hasHexPrefix($str)
    {
        return substr($str, 0, 2) === '0x';
    }

    /**
     * Remove 0x prefix
     *
     * @param $str
     *
     * @return bool|string
     */
    public static function removeHexPrefix($str)
    {
        if (!self::hasHexPrefix($str)) {
            return $str;
        }

        return substr($str, 2);
    }

    /**
     * Add 0x prefix
     *
     * @param $str
     *
     * @return string
     */
    public static function addHexPrefix($str)
    {
        if (self::hasHexPrefix($str)) {
            return $str;
        }

        return '0x'.$str;
    }
}
