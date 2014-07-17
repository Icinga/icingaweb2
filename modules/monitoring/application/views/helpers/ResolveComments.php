<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

// TODO: Throw away as soon as our backends are handling things better
class Zend_View_Helper_ResolveComments extends Zend_View_Helper_Abstract
{

    public function resolveComments($infos)
    {
        $ret = array();
        if (is_array($infos)) {
            foreach ($infos as $info) {
                if (! is_array($info) || empty($info)) continue;
                if (is_int(key($info))) {
                    // livestatus
                    $ret[] = '[' . $info[1] . '] ' . $info[2];
                } else {
                    // statusdat - doesn't seem to work?!
                    $ret[] = '[' . $info['author'] . '] '
                           . (isset($info['comment'])
                            ? $info['comment']
                            : $info['comment_data']
                            );
                }
            }
        } else {
            // ido
            $ret = preg_split('~\|~', $infos);
        }
        return $ret;
    }
}
