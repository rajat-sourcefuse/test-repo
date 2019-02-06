<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientVital;

use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\OMLBundle\Utilities\FormulaUtility;
/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{

    const HEAD_CIRCUMFERENCE_REQUIRED_AGE = 5;
    const HEIGHT = 'height';
    const WEIGHT = 'weight';
    const HEIGHT_FEET = 'heightFeet';
    const HEIGHT_INCH = 'heightInch';
    const WEIGHT_POUND = 'weightPound';
    

    /**
     * function will validate data before execute create
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $patientId = $data->getData('properties')['patientId'];
        $this->executeBL($data, $serviceQue, $patientId);
        
        /*$heightFeet = !empty($data->getData('properties')[self::HEIGHT_FEET])?$data->getData('properties')[self::HEIGHT_FEET]:0;
        $heightInch = !empty($data->getData('properties')[self::HEIGHT_INCH])?$data->getData('properties')[self::HEIGHT_INCH]:0;
        $actualHeight = ($heightFeet * 12)+$heightInch;
        if($actualHeight){
            $data->setData(array(self::HEIGHT => $actualHeight), 'properties');
        }
        if(isset($data->getData('properties')[self::WEIGHT_POUND])){
            $data->setData(array(self::WEIGHT => $data->getData('properties')[self::WEIGHT_POUND]), 'properties');
            
        }*/
       
        return;
    }

    /**
     * @description function will validate data before execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    
    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    
    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * @description function will validate data before execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $patientVitalId = $data->getData('conditions')['object'][0];
        $searchKey[0]['objectId'] = $patientVitalId;
        $searchKey[0]['outKey'] = 'response';
        $vResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        $patientId = $vResp['data']['response']['patientId'];
        $this->executeBL($data, $serviceQue, $patientId);

        // if height empty then get it from db
        $heightFeet = $heightInch = $weightPound = null;
        if (!empty($data->getData('properties')[self::HEIGHT_FEET])) {
            $heightFeet = $data->getData('properties')[self::HEIGHT_FEET];
        } elseif (!empty ($vResp['data']['response'][self::HEIGHT_FEET])) {
            $heightFeet = $vResp['data']['response'][self::HEIGHT_FEET];
        }
        
        if (!empty($data->getData('properties')[self::HEIGHT_INCH])) {
            $heightInch = $data->getData('properties')[self::HEIGHT_INCH];
        } elseif (!empty ($vResp['data']['response'][self::HEIGHT_INCH])) {
            $heightInch = $vResp['data']['response'][self::HEIGHT_INCH];
        }
        
        // if weight empty then get it from db
        if (!empty($data->getData('properties')[self::WEIGHT_POUND])) {
            $weightPound = $data->getData('properties')[self::WEIGHT_POUND];
        } elseif (!empty($vResp['data']['response'][self::WEIGHT_POUND])) {
            $weightPound = $vResp['data']['response'][self::WEIGHT_POUND];
        }
        
        
    }

 

    /**
     * This is the common implementation for create and update
     * 
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function executeBL($data, $serviceQue, $patientId)
    {
        $searchKey[0]['objectId'] = $patientId;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        if (!isset($resp['data']['response'])) {
            throw new SynapExceptions(SynapExceptionConstants::PATIENT_DATA_SHOULD_CAPTURE,400);
        }
        if (!empty($resp['data']['response']['dob'])) {
            $dob = $resp['data']['response']['dob'];
            $patientAge = DateTimeUtility::calculateAge($resp['data']['response']['dob']);
            // patient age greater than 5yrs
            if ($patientAge > self::HEAD_CIRCUMFERENCE_REQUIRED_AGE) {
                if (isset($data->getData('properties')['headCircumference']) &&
                        !empty($data->getData('properties')['headCircumference'])) {
                    throw new SynapExceptions(SynapExceptionConstants::HEADCIRCUMFERENCE_SHOULD_NOT_COME,400);
                }
            }
        }
    }
}
