<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

/**
 * Class PropertyModifier
 * @package Icinga\Protocol\Commandpipe
 */
class PropertyModifier
{
    /**
     *
     */
    const STATE_ENABLE = 1;

    /**
     *
     */
    const STATE_DISABLE = 0;

    /**
     *
     */
    const STATE_KEEP = -1;

    /**
     *
     */
    const FLAPPING = "%s_FLAP_DETECTION";

    /**
     *
     */
    const ACTIVE = "%s_CHECK";

    /**
     *
     */
    const PASSIVE = "PASSIVE_%s_CHECKS";

    /**
     *
     */
    const NOTIFICATIONS = "%s_NOTIFICATIONS";

    /**
     *
     */
    const FRESHNESS = "%s_FRESHNESS_CHECKS";

    /**
     *
     */
    const EVENTHANDLER = "%s_EVENT_HANDLER";

    /**
     * @var array
     */
    public $flags = array(
        self::FLAPPING => self::STATE_KEEP,
        self::ACTIVE => self::STATE_KEEP,
        self::PASSIVE => self::STATE_KEEP,
        self::NOTIFICATIONS => self::STATE_KEEP,
        self::FRESHNESS => self::STATE_KEEP,
        self::EVENTHANDLER => self::STATE_KEEP
    );

    /**
     * @param array $flags
     */
    public function __construct(array $flags)
    {
        foreach ($flags as $type => $value) {
            if (isset($this->flags[$type])) {
                $this->flags[$type] = $value;
            }
        }
    }

    /**
     * @param $type
     * @return array
     */
    public function getFormatString($type)
    {
        $cmd = array();
        foreach ($this->flags as $cmdTemplate => $setting) {
            if ($setting == self::STATE_KEEP) {
                continue;
            }
            $commandString = ($setting == self::STATE_ENABLE ? "ENABLE_" : "DISABLE_");
            $targetString = $type;
            if ($type == CommandPipe::TYPE_SERVICE && $cmdTemplate == self::FRESHNESS) {
                // the external command definition is inconsistent here..
                $targetString = "SERVICE";
            }
            $commandString .= sprintf($cmdTemplate, $targetString);
            $cmd[] = $commandString;
        }
        return $cmd;
    }
}
