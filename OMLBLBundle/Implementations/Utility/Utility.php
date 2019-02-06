<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Utility;

use SynapEssentials\OMLBundle\Services\ServiceQue;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utility
 *
 * @author Sourabh Grover
 */
class Utility {

    public function checkPatientEligibiltyForDrFirst($patientId) 
    {
        $checkPropArr = array('firstName', 'lastName', 'dob', 'ethnicity', 'gender', 'preferredLanguage');
        $configurator = Configurator::getInstance();
        $serviceContainer = $configurator->getServiceContainer();
        $serviceQue = ServiceQue::getInstance($serviceContainer);

        $searchKey = [];
        $searchKey[0]['objectId'] = $patientId;
        $searchKey[0]['child'][0]['type'] = 'patientTelephone';
        $searchKey[0]['child'][0]['conditions'][0] = array(array('isPrimary' => true));
        $searchKey[0]['outKey'] = 'response';
        //Get patient Data
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $data = $resp['data']['response'];
        $return = true;
        if (!empty($data)) {
            if (!empty($data['patientTelephone'])) {
                foreach ($checkPropArr as $prop) {
                    if (empty($data[$prop])) {
                        $return = false;
                        break;
                    }
                }
            } else {
                $return = false;
            }
        } else {
            $return = false;
        }
       
           
       
        return $return;
    }

    public function isPatientCreatedForDrFirst($patientId) {

        $configurator = Configurator::getInstance();
        $serviceContainer = $configurator->getServiceContainer();
        $serviceQue = ServiceQue::getInstance($serviceContainer);

        $searchKey = [];
        $searchKey[0]['objectId'] = $patientId;
        $searchKey[0]['outKey'] = 'response';
        //Get patient Data
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        if ($resp['data']['response']['isCreatedDrFirst']) {
            return true;
        } else {
            return false;
        }
    }

}
