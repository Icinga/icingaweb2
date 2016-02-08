<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Nrpe;

class Packet
{
    const QUERY    = 0x01;
    const RESPONSE = 0x02;

    protected $version = 0x02;
    protected $type;
    protected $body;
    protected static $randomBytes;

    public function __construct($type, $body)
    {
        $this->type = $type;
        $this->body = $body;
        $this->regenerateRandomBytes();
    }

    // TODO: renew "from time to time" to allow long-running daemons
    protected function regenerateRandomBytes()
    {
        self::$randomBytes = '';
        for ($i = 0; $i < 4096; $i++) {
            self::$randomBytes .= pack('N', mt_rand());
        }
    }

    public static function createQuery($body)
    {
        $packet = new Packet(self::QUERY, $body);
        return $packet;
    }

    protected function getFillString($length)
    {
        $max = strlen(self::$randomBytes) - $length;
        return substr(self::$randomBytes, rand(0, $max), $length);
    }

    // TODO: WTF is SR? And 2324?
    public function getBinary()
    {
        $version  = pack('n', $this->version);
        $type     = pack('n', $this->type);
        $dummycrc = "\x00\x00\x00\x00";
        $result   = "\x00\x00";
        $result   = pack('n', 2324);
        $body     = $this->body
                  . "\x00"
                  . $this->getFillString(1023 - strlen($this->body))
                  . 'SR';

        $crc = pack(
            'N',
            crc32($version . $type . $dummycrc . $result . $body)
        );
        $bytes = $version . $type . $crc . $result . $body;
        return $bytes;
    }

    public function __toString()
    {
        return $this->body;
    }
}
