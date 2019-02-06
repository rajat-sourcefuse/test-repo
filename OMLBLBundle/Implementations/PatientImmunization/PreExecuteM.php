<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientImmunization;

/**
 * BL class for preExecute of patientImmunization.
 *
 * @author Vinod Vaishnav <vinod.vaishnav@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{
    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return vodi
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // validate BL
        $this->validate($data);
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
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
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
         // validate BL
        $this->validate($data);
    }
    
    /**
     * This function validates input data for BL.
     * 
     * @param OMLServiceData $data
     * @return void
     */
    private function validate($data)
    {
        $properties = $data->getData('properties');
        
        // Validate AdministeredDate
        if (!empty($properties['administeredDate'])) {
            $adminDate = $properties['administeredDate'];
            $this->validateAdministeredDate($adminDate);
        }
        
        return;
    }
    
    /**
     * This function compare given date with current date.
     * 
     * @param string $date date with dd-mm-yyyy
     * @return boolean
     */
    private function validateAdministeredDate($date)
    {
        $administeredDate = DateTimeUtility::convertDateObject($date);
        
        // AdmininsteredDate must be lesser than current date
        $today = new \DateTime();
        if ($administeredDate > $today) {
            throw new SynapExceptions(SynapExceptionConstants::DATE_ENTERED_SHOULD_BE_LESS_THAN_CURRENT_DATE,400);
        }
        
        return true;
    }

}
