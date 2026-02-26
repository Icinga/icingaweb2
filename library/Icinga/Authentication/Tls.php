<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\File\Storage\LocalFileStorage;

class Tls
{
    /**
     * Get the absolute local filesystem path of the file with the certificates of the given TLS root CA collection
     *
     * The consumer of this interface can rely on the file's existence
     * if they make use of {@link Icinga\Application\Hook\TlsRootCACertificateCollectionHook} respectively.
     *
     * @param   string  $name
     *
     * @return  string
     */
    public static function rootCaCertsFile($name)
    {
        return LocalFileStorage::common('tls/rootcacollections')->resolvePath(bin2hex($name) . '.pem');
    }

    /**
     * Get the absolute local filesystem path of the file with the certificate
     * and the private key of the given TLS client identity
     *
     * The consumer of this interface can rely on the file's existence
     * if they make use of {@link Icinga\Application\Hook\TlsClientIdentityHook} respectively.
     *
     * @param   string  $name
     *
     * @return  string
     */
    public static function clientIdsFile($name)
    {
        return LocalFileStorage::common('tls/clientidentities')->resolvePath(bin2hex($name) . '.pem');
    }
}
