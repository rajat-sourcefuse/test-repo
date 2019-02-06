<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientWarning;

/**
 * BL class for preExecute of PatientWarning.
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
     * This function validates data before execute update
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
     * @param array $properties
     * @return void
     */
    private function validate($data)
    {
        $properties = $data->getData('properties');
        
        // Validate warning entered date
        if (!empty($properties['dateEntered'])) {
            $this->validateEnteredDate($properties['dateEntered']);
        }
        // Validate warning effective date
        if (!empty($properties['effectiveDate'])) {
            $this->validateEnteredDate($properties['effectiveDate']);
        }
        
        return;
    }
    
    /**
     * This function compare given date with current.
     * 
     * @param string $date date with YYYY-MM-DD
     * @return boolean
     */
    private function validateEnteredDate($date)
    {
        // date must be lesser than current date
        $today = new \DateTime();
        $enteredDate = DateTimeUtility::convertDateObject($date);
        if ($enteredDate > $today) {
            throw new SynapExceptions(SynapExceptionConstants::DATE_ENTERED_SHOULD_BE_LESS_THAN_CURRENT_DATE,400);
        }
        
        return true;
    }

}