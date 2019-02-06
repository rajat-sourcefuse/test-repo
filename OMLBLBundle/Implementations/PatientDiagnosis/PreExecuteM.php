<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientDiagnosis;

use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

// start here saurabh agarwal add the import file @05072017
use SynapEssentials\OMLBundle\Services\ServiceQue;
use Externals\DrFirstBundle\Managers\UploadDataManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\OMLBLBundle\Implementations\Utility\Utility;
// end here saurabh agarwal add the import file @05072017

class PreExecuteM implements PreExecuteI
{
    const STATUS_COMPLETED = 'metaPatientDiagnosisStatus:resolved';

    /**
     * @description function will validate data before execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $this->executeBL($data, $serviceQue);
        
        // if status is resolved or isRuledOut then update end date
       if ( (isset($data->getData('properties')['status']) && $data->getData('properties')['status'] == self::STATUS_COMPLETED ) ||
                (isset($data->getData('properties')['isRuledOut']) &&
                $data->getData('properties')['isRuledOut'])) {
            $this->updateCloseDate($data);
        }
    }
    
    /**
     * Update end date if status resolved or isRuledOut
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     */
    private function updateCloseDate($data)
    {
        $today = new \DateTime();
        $currDate = $today->format(DateTimeUtility::DATE_FORMAT);
        $data->setData(array('endDate' => $currDate), 'properties');
    }

    /**
     * @description function will validate data before execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        // Saurabh Agarwal @05072017 start here saurabh agarwal diagons id get the patient id
        $configurator = Configurator::getInstance();
        $serviceContainer = $configurator->getServiceContainer();
        $serviceQue = ServiceQue::getInstance($serviceContainer);

        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData();
        $searchKey[0]['outKey'] = 'response';
        //Get patient Data
        $respe = $serviceQue->executeQue("ws_oml_read", $searchKey);
      
        if(isset($respe['status']['success']) && $respe['status']['success']=="ok")
        {
            // add the code dr first delete
             $utilityInstance = new Utility();
            // If pateint is eligible to be created on dr first
            if ($utilityInstance->isPatientCreatedForDrFirst($respe['data']['response']['patientId'])) {
                $result = ($data->getAll());
                $configurator = Configurator::getInstance();
                $uploadDataToDrFirst = new UploadDataManager($configurator->getServiceContainer());
               
                $uploadDataToDrFirst->submitPatientDiagnosis($result, TRUE);
            }
        }
        // end here saurabh agarwal diagons id get the patient id
        
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
        $this->executeBL($data, $serviceQue);
        if (isset($data->getData('properties')['isRuledOut']) && (!$data->getData('properties')['isRuledOut'])) {
            $updateArr = array('dateOfRuledOut' => null,
                'ruledOutReason' => null,
                'ruledOutNote' => null);
            $data->setData($updateArr, 'properties');
        }
        
        // if status is resolved or isRuledOut then update end date
        if ((isset($data->getData('properties')['status']) &&
                $data->getData('properties')['status'] == self::STATUS_COMPLETED) ||
                (isset($data->getData('properties')['isRuledOut']) &&
                        $data->getData('properties')['isRuledOut'])) {
            $this->updateCloseDate($data);
        }
    }

    private function executeBL($data, $serviceQue)
    {
        // for ruled out
        if (isset($data->getData('properties')['isRuledOut'])) {
            if (!($data->getData('properties')['isRuledOut'])) {
                if (isset($data->getData('properties')['dateOfRuledOut']) ||
                        isset($data->getData('properties')['ruledOutReason']) ||
                        isset($data->getData('properties')['ruledOutNote'])) {
                    throw new SynapExceptions(SynapExceptionConstants::RULEOUT_DATA_SHOULD_NOT_COME,400);
                }
            }
        } else {
            if (isset($data->getData('properties')['dateOfRuledOut']) ||
                    isset($data->getData('properties')['ruledOutReason']) ||
                    isset($data->getData('properties')['ruledOutNote'])) {
                throw new SynapExceptions(SynapExceptionConstants::RULEOUT_DATA_SHOULD_NOT_COME,400);
            }
        }
    }

    /**
     * Validating start date and end date. Startdate should be less than enddate
     * 
     * @param type $startDate
     * @param type $endDate
     * @param type $msg
     * @throws SynapExceptions
     */
    private function validateDates($startDate, $endDate, $msg)
    {
        $startDateO = DateTimeUtility::convertDateObject($startDate);
        $endDateO = DateTimeUtility::convertDateObject($endDate);

        // start date should be less than close/end date
        if ($startDateO > $endDateO) {
            throw new SynapExceptions($msg,400);
        }
    }

}
