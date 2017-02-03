<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Response;

use Zend_Controller_Action_HelperBroker;
use Icinga\Application\Logger;
use Icinga\Web\Response;

/**
 * HTTP response in JSON format
 */
class JsonResponse extends Response
{
    /**
     * {@inheritdoc}
     */
    const DEFAULT_CONTENT_TYPE = 'application/json';

    /**
     * Status identifier for failed API calls due to an error on the server
     *
     * @var string
     */
    const STATUS_ERROR = 'error';

    /**
     * Status identifier for rejected API calls most due to invalid data or call conditions
     *
     * @var string
     */
    const STATUS_FAIL = 'fail';

    /**
     * Status identifier for successful API requests
     *
     * @var string
     */
    const STATUS_SUCCESS = 'success';

    /**
     * JSON encoding options
     *
     * @var int
     */
    protected $encodingOptions;

    /**
     * Error message if the API call failed due to a server error
     *
     * @var string|null
     */
    protected $errorMessage;

    /**
     * Fail data for rejected API calls
     *
     * @var array|null
     */
    protected $failData;

    /**
     * API request status
     *
     * @var string
     */
    protected $status;

    /**
     * Success data for successful API requests
     *
     * @var array|null
     */
    protected $successData;

    /**
     * Get the JSON encoding options
     *
     * @return int
     */
    public function getEncodingOptions()
    {
        if ($this->encodingOptions === null) {
            // PHP 5.5+ does never emit a warning and only replaces non-UTF8 strings with
            // NULL if the option JSON_PARTIAL_OUTPUT_ON_ERROR is used. We're using this
            // as the default here to emulate PHP <= 5.4's behaviour.
            return defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0;
        }

        return $this->encodingOptions;
    }

    /**
     * Set the JSON encoding options
     *
     * @param   int $encodingOptions
     *
     * @return  $this
     */
    public function setEncodingOptions($encodingOptions)
    {
        $this->encodingOptions = (int) $encodingOptions;
        return $this;
    }

    /**
     * Get the error message if the API call failed due to a server error
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set the error message if the API call failed due to a server error
     *
     * @param   string $errorMessage
     *
     * @return  $this
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = (string) $errorMessage;
        $this->status = static::STATUS_ERROR;
        return $this;
    }

    /**
     * Get the fail data for rejected API calls
     *
     * @return array|null
     */
    public function getFailData()
    {
        return (! is_array($this->failData) || empty($this->failData)) ? null : $this->failData;
    }

    /**
     * Set the fail data for rejected API calls
     *
     * @param   array $failData
     *
     * @return $this
     */
    public function setFailData(array $failData)
    {
        $this->failData = $failData;
        $this->status = static::STATUS_FAIL;
        return $this;
    }

    /**
     * Get the data for successful API requests
     *
     * @return array|null
     */
    public function getSuccessData()
    {
        return (! is_array($this->successData) || empty($this->successData)) ? null : $this->successData;
    }

    /**
     * Set the data for successful API requests
     *
     * @param   array $successData
     *
     * @return  $this
     */
    public function setSuccessData(array $successData = null)
    {
        $this->successData = $successData;
        $this->status = static::STATUS_SUCCESS;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function outputBody()
    {
        $body = array(
            'status' => $this->status
        );
        switch ($this->status) {
            case static::STATUS_ERROR:
                $body['message'] = $this->getErrorMessage();
            case static::STATUS_FAIL:
                $failData = $this->getFailData();
                if ($failData !== null || $this->status === static::STATUS_FAIL) {
                    $body['data'] = $failData;
                }
                break;
            case static::STATUS_SUCCESS:
                $body['data'] = $this->getSuccessData();
                break;
        }

        // Since we're enabling display_errors in our bootstrapper, PHP <= 5.4 won't emit any warning or log
        // message if it encounters non-UTF8 characters. It will simply replace such strings with NULL.
        $json = json_encode($body, $this->getEncodingOptions());
        if (($errNo = json_last_error()) > 0) {
            Logger::error(
                'Failed to render route "%s" as JSON: %s',
                $this->getRequest()->getUrl()->getAbsoluteUrl(),
                function_exists('json_last_error_msg') ? json_last_error_msg() : "Error #$errNo"
            );

            // JSON_PARTIAL_OUTPUT_ON_ERROR may have not been in use..
            if ($json === false) {
                // Since the headers have already been sent at this stage we
                // can only output NULL to signal an error to the client
                $json = 'null';
            }
        }

        echo $json;
    }

    /**
     * {@inheritdoc}
     */
    public function sendResponse()
    {
        Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
        parent::sendResponse();
        exit;
    }
}
