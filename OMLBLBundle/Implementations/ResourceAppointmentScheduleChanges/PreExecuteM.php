<?php

namespace SynapEssentials\OMLBLBundle\Implementations\ResourceAppointmentScheduleChanges;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

class PreExecuteM implements PreExecuteI
{
    private $serviceQue;
    /**
    * Function will validate data before execute create
    * 
    * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
    * @param type $data
    * @param ServiceQueI $serviceQue
    * @throws SynapExceptions
    */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    { 
        
        $this->serviceQue = $serviceQue;
        if(isset($data->getData('properties')['startTime']) && isset($data->getData('properties')['endTime'])){
            if(strtotime($data->getData('properties')['startTime']) >= strtotime($data->getData('properties')['endTime'])){
                throw new SynapExceptions(SynapExceptionConstants::END_TIME_ERROR,400);
            }     
        }
       
        $fetchRecord = [];
        $fetchRecord[0]['objectId'] = $data->getData('parent');
        $fetchRecord[0]['outKey'] = 'response'; 
        $fetchData = $this->serviceQue->executeQue("ws_oml_read", $fetchRecord);
        if(isset($fetchData['data']['response']['newDate']) && ($fetchData['data']['response']['newDate'] != '')){
            $validResourceDate = $fetchData['data']['response']['newDate'];
        }else{
            $validResourceDate = $fetchData['data']['response']['date'];
        }
       
        /* Check while inserting.  */
        $fetchRecord2 = [];
        $fetchRecord2[0]['objectId'] = $data->getData('parent');
        $fetchRecord2[0]['outKey'] = 'response'; 
        $checkIfValid = $this->serviceQue->executeQue("ws_oml_read", $fetchRecord2);
      
        if(isset($data->getData('properties')['date'])){
            $validDate = ((strtotime($data->getData('properties')['date']) == strtotime($validResourceDate)))? 'Y':'N';
            if($validDate == 'N')
            throw new SynapExceptions(SynapExceptionConstants::NOT_VALID_RESOURCE_DATE,400);
        }
        
        /* START: Check in table resourceAppointmentSchedule to validate resource is not previously assigned. */ 
        if(!isset($data->getData('properties')['resourceAppointmentScheduleId'])){
            $fetchResources = [];
            $fetchResources[0]['type'] = 'resourceAppointmentSchedule';
            $fetchResources[0]['conditions'][] = array('appointmentScheduleId'=> $checkIfValid['data']['response']['appointmentScheduleId']);
            $fetchResources[0]['conditions'][] = array('resourceId'=> $data->getData('properties')['resourceId']);
            $fetchResources[0]['outKey'] = 'response'; 
            $fetchResourcesData = $this->serviceQue->executeQue("ws_oml_read", $fetchResources);
            if(isset($fetchResourcesData['data']['response'][0])){
               throw new SynapExceptions(SynapExceptionConstants::RESOURCE_ALREADY_ASSIGNED,400); 
            }
        }
        /* END: Check in table resourceAppointmentSchedule to validate resource is not previously assigned. */ 
        
        /* START: Check in table resourceAppointmentScheduleChanges to validate resource is not previously assigned. */ 
        $fetchResourcesChanges = [];
        $fetchResourcesChanges[0]['type'] = 'resourceAppointmentScheduleChanges';
        $fetchResourcesChanges[0]['conditions'][] = array('recurringScheduleChangesId'=> $fetchData['data']['response']['id']);
        $fetchResourcesChanges[0]['conditions'][] = array('resourceId'=> $data->getData('properties')['resourceId']);
        $fetchResourcesChanges[0]['conditions'][] = array('delete'=>array('ne' => 1));
        $fetchResourcesChanges[0]['outKey'] = 'response'; 
        $fetchResourcesChangesData = $this->serviceQue->executeQue("ws_oml_read", $fetchResourcesChanges);
        if(isset($fetchResourcesChangesData['data']['response'][0])){
           throw new SynapExceptions(SynapExceptionConstants::RESOURCE_ALREADY_ASSIGNED,400); 
        }
        /* END: Check in table resourceAppointmentScheduleChanges to validate resource is not previously assigned. */ 
        
        if(isset($data->getData('properties')['startTime'])){
            $validStartTime = ((strtotime($data->getData('properties')['startTime']) >= strtotime($checkIfValid['data']['response']['appointmentScheduleIdStartTime'])) && (strtotime($data->getData('properties')['startTime']) <= strtotime($checkIfValid['data']['response']['appointmentScheduleIdEndTime'])))? 'Y':'N';
            if($validStartTime == 'N')
            throw new SynapExceptions(SynapExceptionConstants::NOT_VALID_START_TIME);
        }
        if(isset($data->getData('properties')['endTime'])){
            $validEndTime = ((strtotime($data->getData('properties')['endTime']) <= strtotime($checkIfValid['data']['response']['appointmentScheduleIdEndTime'])) && (strtotime($data->getData('properties')['endTime']) >= strtotime($checkIfValid['data']['response']['appointmentScheduleIdStartTime'])))? 'Y':'N';
            if($validEndTime == 'N')
            throw new SynapExceptions(SynapExceptionConstants::NOT_VALID_END_TIME,400);            
        }
     
        return true;
    }
    
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $this->serviceQue = $serviceQue;
        if(isset($data->getData('properties')['startTime']) && isset($data->getData('properties')['endTime'])){
            if(strtotime($data->getData('properties')['startTime']) >= strtotime($data->getData('properties')['endTime'])){
                throw new SynapExceptions(SynapExceptionConstants::END_TIME_ERROR,400);
            }     
        }
        $fetchRecord = [];
        $fetchRecord[0]['objectId'] = $data->getData('conditions')['object'][0];
        $fetchRecord[0]['outKey'] = 'response'; 
        $fetchData = $this->serviceQue->executeQue("ws_oml_read", $fetchRecord);
        
        $fetchRecord2 = [];
        $fetchRecord2[0]['objectId'] = $fetchData['data']['response']['recurringScheduleChangesId'];
        $fetchRecord2[0]['outKey'] = 'response';
        $fetchData2 = $this->serviceQue->executeQue("ws_oml_read", $fetchRecord2);

        $fetchRecord3 = [];
        $fetchRecord3[0]['objectId'] = $fetchData2['data']['response']['appointmentScheduleId'];
        $fetchRecord3[0]['outKey'] = 'response';
        $checkIfValid = $this->serviceQue->executeQue("ws_oml_read", $fetchRecord3);
         
        if(isset($data->getData('properties')['startTime'])){
            $validStartTime = ((strtotime($data->getData('properties')['startTime']) >= strtotime($checkIfValid['data']['response']['startTime'])) && (strtotime($data->getData('properties')['startTime']) <= strtotime($checkIfValid['data']['response']['endTime'])))? 'Y':'N';
            if($validStartTime == 'N')
            throw new SynapExceptions(SynapExceptionConstants::NOT_VALID_START_TIME,400);
        }
        if(isset($data->getData('properties')['endTime'])){
            $validEndTime = ((strtotime($data->getData('properties')['endTime']) <= strtotime($checkIfValid['data']['response']['endTime'])) && (strtotime($data->getData('properties')['endTime']) >= strtotime($checkIfValid['data']['response']['startTime'])))? 'Y':'N';
            if($validEndTime == 'N')
            throw new SynapExceptions(SynapExceptionConstants::NOT_VALID_END_TIME,400);            
        }
        return true;
        
    }
    
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
}