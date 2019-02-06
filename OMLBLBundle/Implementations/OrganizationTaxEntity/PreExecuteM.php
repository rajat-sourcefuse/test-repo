<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationTaxEntity;

/**
 * BL class for preExecute of organizationTaxEntity.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;

class PreExecuteM implements PreExecuteI {

    /**
     * This function will execute just before the creation of organization tax entity 
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        //Manish Kumar commented this code as divisions are not more reuired in organizationTaxEntity
        //$this->chkDivisionAssignment($data, $serviceQue);
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * This function will execute just before the updation of tax entity
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        //Manish Kumar commented this code as divisions are not more reuired in organizationTaxEntity
        //$this->chkDivisionAssignment($data, $serviceQue);
        return true;
    }
}
