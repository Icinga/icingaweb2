<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */
namespace Icinga\Web;

/**
 * Class UserAgent
 *
 * This class helps to get user agent information like OS type and browser name
 *
 * @package Icinga\Web
 */
class UserAgent
{
    /**
     * $_SERVER['HTTP_USER_AGENT'] output string
     *
     * @var string|null
     */
    private $agent;

    public function __construct($agent = null)
    {
        $this->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        if ($agent) {
            $this->agent = $agent->http_user_agent;
        }
    }

    /**
     * Return $_SERVER['HTTP_USER_AGENT'] output string of given or current device
     *
     * @return string
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * Get Browser name
     *
     * @return string Browser name or unknown if not found
     */
    public function getBrowser()
    {
        // key => regex value
        $browsers = [
            "Internet Explorer"    => "/MSIE(.*)/i",
            "Seamonkey"            => "/Seamonkey(.*)/i",
            "MS Edge"              => "/Edg(.*)/i",
            "Opera"                => "/Opera(.*)/i",
            "Opera Browser"        => "/OPR(.*)/i",
            "Chromium"             => "/Chromium(.*)/i",
            "Firefox"              => "/Firefox(.*)/i",
            "Google Chrome"        => "/Chrome(.*)/i",
            "Safari"               => "/Safari(.*)/i"
        ];
        //TODO find a way to return also the version of the browser
        foreach ($browsers as $browser => $regex) {
            if (preg_match($regex, $this->agent)) {
                return $browser;
            }
        }

        return 'unknown';
    }

    /**
     * Get Operating system information
     *
     * @return string os information
     */
    public function getOs()
    {
        // get string before the first appearance of ')'
        $device = strstr($this->agent, ')', true);
        if (! $device) {
            return 'unknown';
        }

        // return string after the first appearance of '('
        return  substr($device, strpos($device, '(') + 1);
    }
}
