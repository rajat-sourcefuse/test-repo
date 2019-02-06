<?php

namespace SynapEssentials\OMLBLBundle\Implementations\ResourceAppointmentSchedule;

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
       $appointmentId = $data->getData('parent');
       $resourceId = $data->getData('properties')['resourceId'];
       $searchStartDate = $data->getData('properties')['startDate'];
       $searchEndDate = $data->getData('properties')['endDate'];
       $searchStartTime = $data->getData('properties')['startTime'];
       $searchEndTime = $data->getData('properties')['endTime'];
       $this->checkAvailability($resourceId,$searchStartDate,$searchEndDate,$searchStartTime,$searchEndTime,'');
      
       return true;
    }
    
    /**
    * Function will validate data before executing update.
    * 
    * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
    * @param type $data
    * @param ServiceQueI $serviceQue
    * @throws SynapExceptions
    */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $this->serviceQue = $serviceQue;
        $resourceAppointmentScheduleId = $data->getData('conditions')['object'][0];
        $resourceId = $data->getData('properties')['resourceId'];
        $searchStartDate = $data->getData('properties')['startDate'];
        $searchEndDate = $data->getData('properties')['endDate'];
        $searchStartTime = $data->getData('properties')['startTime'];
        $searchEndTime = $data->getData('properties')['endTime'];
       
        $this->checkAvailability($resourceId,$searchStartDate,$searchEndDate,$searchStartTime,$searchEndTime,$resourceAppointmentScheduleId);
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
    
    /*Function: Check availability of resource.*/
    /**
    * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
    * @param type $data
    * @param ServiceQueI $serviceQue
    * @throws SynapExceptions
    */
    private function checkAvailability($resourceId,$startDate,$endDate,$startTime,$endTime,$update)
    {
       
        $checkAvailability = [];
        $checkAvailability[0]['type'] = 'resourceAppointmentSchedule';
        $checkAvailability[0]['outKey'] = 'response'; 
        $checkAvailability[0]['conditions'][] = array('resourceId'=> $resourceId);
        if($update){
          $checkAvailability[0]['conditions'][] = array('id'=>array('ne' => $update));  
        }
       
        if($startDate == $endDate){
            /* If passed startDate and endDate are same, conflicts are checked across for records already existing for same or different startDates and endDates, but lying between the passed intervals.*/           
            $checkAvailability[0]['conditions'][] = 
                array(
                    array(
                        array('startDate' => array('ne'=>$startDate)),
                        'AND',
                        array('endDate' => $startDate),
                        'AND',
                        array(
                            array('endTime' => array('ge'=>$startTime)),
                            'OR',
                            array('endTime' => array('ge'=>$endTime))
                        )
                    ),
                    'OR',
                    array(
                       array('endDate' => array('ne'=>$startDate)),
                        'AND',
                        array('startDate' => $startDate),
                        'AND',
                        array(
                            array('startTime' => array('le'=>$startTime)),
                            'OR',
                            array('startTime' => array('le'=>$endTime))
                        ) 
                    ),
                    'OR',
                    array(
                        array('startDate' => $startDate),
                        'AND',
                        array('endDate' => $startDate),
                        'AND',
                        array(
                            array(
                                array(
                                    array('startTime'=> array('ge'=>$startTime)),
                                    'AND',
                                    array('startTime'=> array('le'=>$endTime))
                                ),
                                'OR',
                                array(
                                    array('endTime'=> array('ge'=>$startTime)),
                                    'AND',
                                    array('endTime'=> array('le'=>$endTime))
                                )
                            ),
                            'OR',
                            array(
                                array(
                                    array('startTime'=> array('le'=>$startTime)),
                                    'AND',
                                    array('endTime'=> array('ge'=>$startTime))
                                ),
                                'OR',
                                array(
                                    array('startTime'=> array('le'=>$endTime)),
                                    'AND',
                                    array('endTime'=> array('ge'=>$endTime))
                                )
                            )    
                        )
                    )
                );
        }else{
            /* If passed startDate and endDate are not same, conflicts are checked with the requested time/date frames.*/
            $checkAvailability[0]['conditions'][] = 
                array(
                    array(
                        array('startDate'=> $startDate),
                        'AND',
                        array('startDate'=> array('ne'=>$endDate)),
                        'AND',
                        array(
                            array('startTime' => array('ge'=>$startTime)),
                            'OR',
                            array('endTime' => array('ge'=>$startTime))
                        )
                    ),
                    'OR',
                    array(
                        array('endDate'=> $endDate),
                        'AND',
                        array('startDate'=> array('ne'=>$startDate)),
                        'AND',
                        array(
                            array('startTime' => array('le'=>$endTime)),
                            'OR',
                            array('endTime' => array('le'=>$endTime))
                        )
                    ),
                    'OR',
                    array(
                        array('startDate'=> $startDate),
                        'AND',
                        array('endDate'=> $endDate)
                    )
                );
        }
        
        //Check for start and end time overlap
        $checkIfAvailable = $this->serviceQue->executeQue("ws_oml_read", $checkAvailability);
        
        if(isset($checkIfAvailable['data']['response'][0])){
            throw new SynapExceptions(SynapExceptionConstants::RESOURCE_NOT_AVAILABLE,
           400, array('resourceName' =>  $checkIfAvailable['data']['response'][0]['resourceIdName'] ,'startTime' => $checkIfAvailable['data']['response'][0]['startTime'],'endTime' =>$checkIfAvailable['data']['response'][0]['endTime'],'startDate' => $checkIfAvailable['data']['response'][0]['startDate'],'endDate' =>$checkIfAvailable['data']['response'][0]['endDate']));                  
        }
    }
    
}