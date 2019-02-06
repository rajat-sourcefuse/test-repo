<?php

namespace SynapEssentials\OMLBLBundle\Implementations\AppointmentScheduleAttendance;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;

class PreExecuteM implements PreExecuteI
{

    private $serviceQue;

    const STATUS = 'appointmentStatus';
    const STATUS_ARRIVED = 'metaAppointmentStatus:arrived';
    const ENCOUNTER_STATUS_INITIATED = 'metaEncounterStatus:initiated';

    /**
     * Function will validate data before execute create
     *
     * @author Dasarath Sahoo <dasarath.sahoo@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $appointmentScheduleId = $data->getData('parent');
        $searchKey = [];
        $searchKey[0]['objectId'] = $appointmentScheduleId;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
               
        $data->setData(array('properties' => array('encounterType' => $resp['data']['response']['encounterType'])));
        $data->setData(array('properties' => array('appointmentStatus' => $resp['data']['response']['metaAppointmentStatus:scheduled'])));
        return true;
    }

    /**
     * Function will validate data before executing update.
     *
     * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will validate data before deletion
     *
     * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
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
}
