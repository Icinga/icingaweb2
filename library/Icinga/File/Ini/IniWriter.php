<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Ini;

use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ProgrammingError;
use Icinga\File\Ini\Dom\Directive;
use Icinga\File\Ini\Dom\Document;
use Icinga\File\Ini\Dom\Section;
use Zend_Config_Exception;
use Icinga\Application\Config;

/**
 * A INI file adapter that respects the file structure and the comments of already existing ini files
 */
class IniWriter
{
    /**
     * Stores the options
     *
     * @var array
     */
    protected $options;

    /**
     * The configuration object to write
     *
     * @var Config
     */
    protected $config;

    /**
     * The mode to set on new files
     *
     * @var int
     */
    protected $fileMode;

    /**
     * The path to write to
     *
     * @var string
     */
    protected $filename;

    /**
     * Create a new INI writer
     *
     * @param Config    $config    The configuration to write
     * @param string    $filename  The file name to write to
     * @param int       $filemode  Octal file persmissions
     *
     * @link http://framework.zend.com/apidoc/1.12/files/Config.Writer.html#\Zend_Config_Writer
     */
    public function __construct(Config $config, $filename, $filemode = 0660, $options = array())
    {
        $this->config = $config;
        $this->filename = $filename;
        $this->fileMode = $filemode;
        $this->options = $options;
    }

    /**
     * Render the Zend_Config into a config filestring
     *
     * @return  string
     */
    public function render()
    {
        if (file_exists($this->filename)) {
            $oldconfig = Config::fromIni($this->filename);
            $content = trim(file_get_contents($this->filename));
        } else {
            $oldconfig = Config::fromArray(array());
            $content = '';
        }
        $doc = IniParser::parseIni($content);
        $this->diffPropertyUpdates($this->config, $doc);
        $this->diffPropertyDeletions($oldconfig, $this->config, $doc);
        $doc = $this->updateSectionOrder($this->config, $doc);
        return $doc->render();
    }

    /**
     * Write configuration to file and set file mode in case it does not exist yet
     *
     * @param string $filename
     * @param bool $exclusiveLock
     *
     * @throws Zend_Config_Exception
     */
    public function write($filename = null, $exclusiveLock = false)
    {
        $filePath = isset($filename) ? $filename : $this->filename;
        $setMode = false === file_exists($filePath);

        if (file_put_contents($filePath, $this->render(), $exclusiveLock ? LOCK_EX : 0) === false) {
            throw new Zend_Config_Exception('Could not write to file "' . $filePath . '"');
        }

        if ($setMode) {
            // file was newly created
            $mode = $this->fileMode;
            if (is_int($this->fileMode) && false === @chmod($filePath, $this->fileMode)) {
                throw new Zend_Config_Exception(sprintf('Failed to set file mode "%o" on file "%s"', $mode, $filePath));
            }
        }
    }

    /**
     * Update the order of the sections in the ini file to match the order of the new config
     *
     * @return Document     A new document with the changed section order applied
     */
    protected function updateSectionOrder(Config $newconfig, Document $oldDoc)
    {
        $doc = new Document();
        $dangling = $oldDoc->getCommentsDangling();
        if (isset($dangling)) {
            $doc->setCommentsDangling($dangling);
        }
        foreach ($newconfig->toArray() as $section => $directives) {
            $doc->addSection($oldDoc->getSection($section));
        }
        return $doc;
    }

    /**
     * Search for created and updated properties and use the editor to create or update these entries
     *
     * @param Config     $newconfig  The config representing the state after the change
     * @param Document   $doc
     *
     * @throws ProgrammingError
     */
    protected function diffPropertyUpdates(Config $newconfig, Document $doc)
    {
        foreach ($newconfig->toArray() as $section => $directives) {
            if (! is_array($directives)) {
                Logger::warning('Section-less property ' . (string)$directives . ' was ignored.');
                continue;
            }
            if (!$doc->hasSection($section)) {
                $domSection = new Section($section);
                $doc->addSection($domSection);
            } else {
                $domSection = $doc->getSection($section);
            }
            foreach ($directives as $key => $value) {
                if ($value === null) {
                    continue;
                }

                if ($value instanceof ConfigObject) {
                    throw new ProgrammingError('Cannot diff recursive configs');
                }
                if ($domSection->hasDirective($key)) {
                    $domSection->getDirective($key)->setValue($value);
                } else {
                    $dir = new Directive($key);
                    $dir->setValue($value);
                    $domSection->addDirective($dir);
                }
            }
        }
    }

    /**
     * Search for deleted properties and use the editor to delete these entries
     *
     * @param Config    $oldconfig  The config representing the state before the change
     * @param Config    $newconfig  The config representing the state after the change
     * @param Document  $doc
     *
     * @throws ProgrammingError
     */
    protected function diffPropertyDeletions(Config $oldconfig, Config $newconfig, Document $doc)
    {
        // Iterate over all properties in the old configuration file and remove those that don't
        // exist in the new config
        foreach ($oldconfig->toArray() as $section => $directives) {
            if (! is_array($directives)) {
                Logger::warning('Section-less property ' . (string)$directives . ' was ignored.');
                continue;
            }

            if ($newconfig->hasSection($section)) {
                $newSection = $newconfig->getSection($section);
                $oldDomSection = $doc->getSection($section);
                foreach ($directives as $key => $value) {
                    if ($value instanceof ConfigObject) {
                        throw new ProgrammingError('Cannot diff recursive configs');
                    }
                    if (null === $newSection->get($key) && $oldDomSection->hasDirective($key)) {
                        $oldDomSection->removeDirective($key);
                    }
                }
            } else {
                $doc->removeSection($section);
            }
        }
    }
}
