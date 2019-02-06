<?php

namespace SynapEssentials\OMLBLBundle\Implementations\SignatureValidator;

use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\OMLBundle\Services\ServiceQue;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;

//use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;

/**
 * Description of SignatureValidator
 *
 * @author dell
 */
class SignatureValidator
{

    private $serviceContainer;

    /**
     * Get seviceContainer and set as instance member.
     * 
     * @param \Symfony\Component\DependencyInjection\Container $serviceContainer
     */
    public function __construct(\Symfony\Component\DependencyInjection\Container $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;

        $this->serviceQue = ServiceQue::getInstance($this->serviceContainer);
    }

    /**
     * This function listen the event passed from Signature Data Type and passed base64 encoded data type
     * @param type $event
     * @throws SynapExceptions
     */
    public function listen($event)
    {
        $propertyValue = $event->getArgument("propertyValue");
        
        // Fetch SynapUser Id
        $sessionUtil = SessionUtility::getInstance();
        $userId = $sessionUtil->getUserId();

        $searchKey = [];
        $searchKey[0]['conditions'][] = array('id' => $userId);
        $searchKey[0]['conditions'][] = array('pin' => $propertyValue);
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['type'] = 'synapUser';

        $resp = $this->serviceQue->executeQue("ws_oml_read", $searchKey);

        if (count($resp['data']['response'])) {
            $signatureValue = $resp['data']['response'][0]['signature'];
        } else {
            throw new SynapExceptions(SynapExceptionConstants::PIN_DID_NOT_MATCH,400);
        }

        $event->setArgument('propertyValue', $signatureValue);
    }

}
