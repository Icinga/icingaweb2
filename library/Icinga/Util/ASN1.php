<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use DateInterval;
use DateTime;
use InvalidArgumentException;

/**
 * Parsers for ASN.1 types
 */
class ASN1
{
    /**
     * Parse the given value based on the "3.3.13. Generalized Time" syntax as specified by IETF RFC 4517
     *
     * @param   string  $value
     *
     * @return  DateTime
     *
     * @throws  InvalidArgumentException
     *
     * @see https://tools.ietf.org/html/rfc4517#section-3.3.13
     */
    public static function parseGeneralizedTime($value)
    {
        $generalizedTimePattern = <<<EOD
/\A
    (?P<YmdH>
        [0-9]{4}                    # century year
        (?:0[1-9]|1[0-2])           # month
        (?:0[1-9]|[12][0-9]|3[0-1]) # day
        (?:[01][0-9]|2[0-3])        # hour
    )
    (?:
        (?P<i>[0-5][0-9])           # minute
        (?P<s>[0-5][0-9]|60)?       # second or leap-second
    )?
    (?:[.,](?P<frac>[0-9]+))?       # fraction
    (?P<tz>                         # g-time-zone
            Z
        |
            [-+]
            (?:[01][0-9]|2[0-3])    # hour
            (?:[0-5][0-9])?         # minute
    )
\z/x
EOD;

        $matches = array();

        if (preg_match($generalizedTimePattern, $value, $matches)) {
            $dateTimeRaw = $matches['YmdH'];
            $dateTimeFormat = 'YmdH';

            if ($matches['i'] !== '') {
                $dateTimeRaw .= $matches['i'];
                $dateTimeFormat .= 'i';

                if ($matches['s'] !== '') {
                    $dateTimeRaw .= $matches['s'];
                    $dateTimeFormat .= 's';
                    $fractionOfSeconds = 1;
                } else {
                    $fractionOfSeconds = 60;
                }
            } else {
                $fractionOfSeconds = 3600;
            }

            $dateTimeFormat .= 'O';

            if ($matches['tz'] === 'Z') {
                $dateTimeRaw .= '+0000';
            } else {
                $dateTimeRaw .= $matches['tz'];

                if (strlen($matches['tz']) === 3) {
                    $dateTimeRaw .= '00';
                }
            }

            $dateTime = DateTime::createFromFormat($dateTimeFormat, $dateTimeRaw);

            if ($dateTime !== false) {
                if (isset($matches['frac'])) {
                    $dateTime->add(new DateInterval(
                        'PT' . round((float) ('0.' . $matches['frac']) * $fractionOfSeconds) . 'S'
                    ));
                }

                return $dateTime;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Failed to parse %s based on the ASN.1 standard (GeneralizedTime)',
            var_export($value, true)
        ));
    }
}
