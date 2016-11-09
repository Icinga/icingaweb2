<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Response;

use Zend_Controller_Action_HelperBroker;
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
    protected $encodingOptions = 0;

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
        echo json_encode($body, $this->getEncodingOptions());
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
