<?php

namespace Icinga\Util;

class ChromeHeadless
{
    const CHROMIUM_PATH = 'google-chrome'; // todo (jem): path to the chrome/chromium binaries

    protected $outputPath = '/tmp/chromiumpdf'; // todo (jem): make this make sense

    protected $inputHtml = ''; // todo (jem):

    public function exportPdf()
    {
        if ($this->checkChromeVersion() > 59) {
            $command = new Command(static::CHROMIUM_PATH);
            $command
                ->option('headless', '', true)
                ->option('disable-gpu', '', true)
                ->option('no-sandbox', '', true)
                ->option('print-to-pdf', $this->getOutputPath(), true, '=')
                ->arg($this->getInputHtml());

            $command->execute();
        } else {
            // todo (jem): chrome version too low
        }
    }

    /**
     * @param $outputPath
     * @return $this
     */
    public function setOutputPath($outputPath)
    {
        $this->outputPath = $outputPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputPath()
    {
        return $this->outputPath;
    }

    /**
     * @param $inputHtml
     * @return $this
     */
    public function setInputHtml($inputHtml)
    {
        $this->inputHtml = $inputHtml;

        return $this;
    }

    /**
     * @return string
     */
    public function getInputHtml()
    {
        return $this->inputHtml;
    }

    public function checkChromeVersion()
    {
        $command = new Command(static::CHROMIUM_PATH);
        $command->option('version', '', true);

        // todo (jem): for some reason end() and array_pop() don't work..?
        $version = explode(' ', trim($command->execute()->listen()['stdout']))[2];

        $command->close();

        return $version;
    }
}
