<?php

namespace SynapEssentials\OMLBLBundle\EventListener;

use SynapEssentials\OMLBundle\Metadata\ObjectMetadataFactory;
use SynapEssentials\OMLBundle\Utilities\OtherUtility;
use SynapEssentials\OMLBundle\Utilities\ContextDataUtility;

/**
 * BListener is for calling the appropriate BL class for the pre and post
 * execution of the services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
class BLListener
{

    private $serviceContainer;
    private $entityManager;
    private $doctorine;
    private $eventDispatcher;

    const OBJECT_TYPE = 'objectType';
    const EVENT_TYPE = 'eventType';
    const SERVICE_TYPE = 'serviceType';
    const SERVICE_QUE_O = 'serviceQueO';
    const CALLER = 'caller';

    /**
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param \Symfony\Component\DependencyInjection\Container $serviceContainer
     */
    public function __construct(\Symfony\Component\DependencyInjection\Container $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
        $this->doctorine = $this->serviceContainer->get("doctrine");
        $this->entityManager = $this->doctorine->getManager();
        $this->eventDispatcher = $this->serviceContainer->get('event_dispatcher');
    }

    /**
     * Function will call the appropriate implemetaions on the
     * basis of subject array
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $event
     */
    public function onNotify($event)
    {
        //step1 get arguments from $event and fecthing class name and
        //function name from the $event data
        $args = $event->getArguments();
        $subArr = $event->getSubject();
        $data = $args[0]->getData();

        // If worklistId has been passed with the request and its not workList object itself then set the generatePDF flag true.
        // It will be used for encounter print. A SUC will generate pdf's based on this flag.
        if (!empty($data['workListId'])) {
            if (!empty($data['objectType'] && $data['objectType'] != 'workList')) {
                $serviceQueO = $subArr[self::SERVICE_QUE_O];
                $objectData['conditions']['object'][0] = $data['workListId'];
                $objectData['properties']['generatePDF'] = 1;
                $serviceQueO->executeQue('ws_oml_update', $objectData);
            }
        }
        // Check if event type is preexecute and service is Create
        if ($subArr[self::EVENT_TYPE] == 'preExecute' && $subArr[self::SERVICE_TYPE] == 'create') {
            // Check if execution Id is passed with request
            if (!empty($data['executionId'])) {
                $objType = $data['objectType'];
                $objMDF = ObjectMetadataFactory::getInstance($this->entityManager);
                //Get properties from metadata for the object being created.
                $objMD = $objMDF->getObjectMetadata($objType);
                $properties = $objMD->getProperties();
                // Check if a property exist with name patientEncounterId
                foreach ($properties as $property) {
                    if ($property->getPropertyName() == 'patientEncounterId') {
                        // Check if patientEncounterId has not been passed with the request
                        if (empty($data['properties']['patientEncounterId'])) {
                            // Check PatientEncounterId exist
                            $contextUtil = ContextDataUtility::getInstance();
                            $patientEncounterId = $contextUtil->getEncounterId();
                            if (!empty($patientEncounterId)) {
                                $updateArr = array("patientEncounterId" => $patientEncounterId);
                                // If patientEncounterId exists then pass it along with the request
                                $args[0]->setData($updateArr, 'properties');
                            }
                        }
                        break;
                    }
                }
            }
        }

        $className = ucfirst($subArr[self::EVENT_TYPE]) . 'M';
        $funcName = $subArr[self::EVENT_TYPE]
                . ucfirst($subArr[self::SERVICE_TYPE]);

        $namespace_var = "SynapEssentials\OMLBLBundle\Implementations\\"
                . ucfirst($subArr[self::OBJECT_TYPE]) . "\\" . $className;
        $path = "../src/SynapEssentials/OMLBLBundle/Implementations/"
                . ucfirst($subArr[self::OBJECT_TYPE]) . '/' . $className . ".php";

        //step2 if class exists then proceed to call the function otherwise
        //no need to do anything
        if (file_exists($path)) {
            $clsObj = new $namespace_var();
            $serviceQueO = $subArr[self::SERVICE_QUE_O];
            $clsObj->$funcName($args[0], $serviceQueO, $subArr[self::CALLER]);
        }
        //step3 no need to add else statement becuase we only care if class exists
    }
}
