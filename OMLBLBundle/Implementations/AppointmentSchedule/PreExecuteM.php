<?php

namespace SynapEssentials\OMLBLBundle\Implementations\AppointmentSchedule;

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
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;

class PreExecuteM implements PreExecuteI {

    private $serviceQue;

    const STATUS = 'appointmentStatus';
    const STATUS_ARRIVED = 'metaAppointmentStatus:arrived';
    const STATUS_CANCELLED = 'metaAppointmentStatus:cancelled';
    const STATUS_CANCELLED_24 = 'metaAppointmentStatus:cancelled24';
    const STATUS_CANCELLED_PROVIDER = 'metaAppointmentStatus:cancelledByProvider';
    const STATUS_RESCHEDULED = 'metaAppointmentStatus:reScheduled';
    const STATUS_COMPLETED = 'metaAppointmentStatus:completed';
    const ENCOUNTER_STATUS_INITIATED = 'metaEncounterStatus:initiated';
    const PRIMARY_COMPANY_RANK = 'metaInsuranceCompanyRank:one';
    const SECONDARY_COMPANY_RANK = 'metaInsuranceCompanyRank:two';
    const TERTIARY_COMPANY_RANK = 'metaInsuranceCompanyRank:three';
    const OPEN_ACCOUNTING_PERIOD_STATUS = 'metaAccountingPeriodStatus:open';

    /**
     * Function will validate data before execute create
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        $this->serviceQue = $serviceQue;
        $this->checkDateValidity($data);
        $this->checkAppointmentScheduleConflicts($data);
        $this->checkHolidayConflicts($data);
        $this->checkProviderSchedule($data);
        if (!empty($data->getData('properties')['duration'])) {
            $this->checkValidDuration($data);
        }
        if (!empty($data->getData('properties')['startDate']) && !empty($data->getData('properties')['startTime'])) {
            $startDate = $data->getData('properties')['startDate'];
            $startTime = $data->getData('properties')['startTime'];
            $startTimeStamp = strtotime($data->getData('properties')['startDate'] . $data->getData('properties')['startTime']);
            $data->setData(array('properties' => array('startTimestamp' => $startTimeStamp)));
        }
        /**
         * Author Kuldeep Singh, Date: 08-08-2017, JIRA Id: AP-339
         * Appointment creation/checkin : primary provider has been assigned to the payer of the patient's primary Insurance
         * 
         */
        $arrPatientId = array();
        $primaryProvider = $appointmentLocation = '';
        if (isset($data->getData('properties')['patientId'])) {
            $arrPatientId = $data->getData('properties')['patientId'];
        }
        if (isset($data->getData('properties')['primaryProvider'])) {
            $primaryProvider = $data->getData('properties')['primaryProvider'];
        }
        if (isset($data->getData('properties')['locationId'])) {
            $appointmentLocation = $data->getData('properties')['locationId'];
        }

        if (!empty($arrPatientId)) {
            $dateTimeUtility = new DateTimeUtility();
            $currDate = date($dateTimeUtility::DATE_FORMAT);
            foreach ($arrPatientId as $patientId) {
                $orgEmpPayerExisting = FALSE;
                if (!empty($primaryProvider)) {
                    $patientInsurancecData = $this->checkPatientInsurance($serviceQue, $patientId, $currDate);
                    if (isset($patientInsurancecData) && count($patientInsurancecData) > 0) {
                        $arrInsurancePayers = $this->patientInsurancePayers($patientInsurancecData);
                        if (isset($arrInsurancePayers['primaryPayer'])) {
                            $orgPayerId = $arrInsurancePayers['primaryPayer'];
                            $orgTaxEntityPayerConfigData = $this->checkOrgTaxEntityPayerConfig($serviceQue, $orgPayerId, $appointmentLocation);
//                            $orgTaxEntityPayerConfigData = $this->payerWiseOrgTaxPayerConfig($serviceQue, $appointmentLocation, $arrInsurancePayers);
                            if (count($orgTaxEntityPayerConfigData) > 0 && trim($orgTaxEntityPayerConfigData[0]['taxEntityId']) != '' && trim($orgTaxEntityPayerConfigData[0]['transmissionTunnelId']) != '') {
                                $orgPayerId = $orgTaxEntityPayerConfigData[0]['organizationPayerId'];
                                $taxEntityId = $orgTaxEntityPayerConfigData[0]['taxEntityId'];
                                $orgEmpPayerExistingData = $this->checkOrgEmpPayerTaxEntity($serviceQue, $orgPayerId, $taxEntityId, $primaryProvider, $currDate);
                                if (isset($orgEmpPayerExistingData) && count($orgEmpPayerExistingData) > 0) {
                                    $orgEmpPayerExisting = TRUE;
                                }
                            }
                            if (!$orgEmpPayerExisting) {
                                throw new SynapExceptions(SynapExceptionConstants::ATTENDING_PROVIDER_NOT_REGISTERED_WITH_PATIENT_PRIMARY_PAYER,400);
                            }
                        }
                    }
                }
            }
        }
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
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {

        $this->serviceQue = $serviceQue;
        if (!empty($data->getData('properties')['duration'])) {
            $this->checkValidDuration($data);
        }
        //Date converted to the date format in database
        $searchKey = [];
        $searchKey[0]['type'] = 'appointmentSchedule';
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $searchKey[0]['conditions'][] = array('id' => $data->getData('conditions')['object'][0]);
        $searchKey[0]['child'] = array(array('type' => 'resourceAppointmentSchedule'));
        $searchKey[0]['outKey'] = 'response';
        $appointmentData = $this->serviceQue->executeQue("ws_oml_read", $searchKey);
        
        //arrived status will only be marked as complete
        if($appointmentData['data']['response'][0]['appointmentStatus']==self::STATUS_ARRIVED && isset($data->getData('properties')['appointmentStatus']) && $data->getData('properties')['appointmentStatus']!=self::STATUS_COMPLETED){
            throw new SynapExceptions(SynapExceptionConstants::STATUS_TO_BE_MARKED_AS_COMPLETE,400);
        }
        
        if (empty($appointmentData['data']['response'])) {
            throw new SynapExceptions(SynapExceptionConstants::NOT_A_VALID_APPOINTMENT,400);
        }
        foreach ($appointmentData['data']['response'][0] as $key => $value) {
            $valuesRequired = array("startDate", "endDate", "startTime", "endTime", "provider", "id", "patientId", "parentId");
            if (in_array($key, $valuesRequired)) {
                if (empty($data->getData('properties')[$key])) {
                    $data->setData(array($key => $appointmentData['data']['response'][0][$key]), 'properties');
                    if (($key == 'startDate') || ($key == 'endDate')) {
                        $appointmentDate = $appointmentData['data']['response'][0][$key];
                        $convertedDate = DateTimeUtility::convertTimeFormat($appointmentDate, DateTimeUtility::DATE_FORMAT);
                        $data->setData(array($key => $convertedDate), 'properties');
                    }
                    if (($key == 'startTime') || ($key == 'endTime')) {
                        $appointmentTime = $appointmentData['data']['response'][0][$key];
                        $convertedTime = DateTimeUtility::convertTimeFormat($appointmentTime, DateTimeUtility::TIME_FORMAT);
                        $data->setData(array($key => $convertedTime), 'properties');
                    }
                }
            }
        }
        if (!empty($data->getData('conditions')['object'][0]) && !empty($data->getData('properties')['appointmentSchedule'][0])) {
            $data->setData(array('appointmentSchedule' => array('parentId' => $data->getData('conditions')['object'][0])), 'properties', 0);
        }
        $this->checkDateValidity($data);
        // when appointment already cancelled, then restrict to update status other than cancelled
        $this->checkCancelledAppointment($data, $appointmentData);
        //conflict(holiday) will be checked.
        $this->checkHolidayConflicts($data);
        /* Step1: Check if appointment is not overlapped */
        $this->checkAppointmentScheduleConflicts($data);
        /* Step1: Check if appointment is not overlapped */
        if (!empty($data->getData('properties')['provider'])) {
            $this->checkProviderSchedule($data);
        }
        // create encounter if status is arrived
        if (isset($data->getData('properties')[self::STATUS])) {
            $this->createEncounter($data, $serviceQue);
        }
        return true;
    }

    /**
     * Function will validate data before deletion
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
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
     * Function will check the validity of date,time and tenure.
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkDateValidity($data) {
        if (strtotime($data->getData('properties')['startDate']) < strtotime(date(DateTimeUtility::DATE_FORMAT))) {
            //throw new SynapExceptions(SynapExceptionConstants::PAST_DATE_APPOINTMENT_ERROR);
        }
        if (isset($data->getData('properties')['endDate'])) {
            /* Check if startdate and enddate are valid */
            if (strtotime($data->getData('properties')['startDate']) > strtotime($data->getData('properties')['endDate'])) {
                throw new SynapExceptions(SynapExceptionConstants::NOT_VALID_END_DATE,400);
            }
            if (strtotime($data->getData('properties')['startDate']) == strtotime($data->getData('properties')['endDate'])) {
                /* Check if starttime and endtime are valid */
                if (strtotime($data->getData('properties')['startTime']) >= strtotime($data->getData('properties')['endTime'])) {
                    throw new SynapExceptions(SynapExceptionConstants::END_TIME_ERROR,400);
                }
            }
        }
    }

    /**
     * Checked for conflicts on appointment for an asked time and date corresponding to a particular provider or patient.
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $searchDate
     * @param $searchStartTime
     * @param $searchEndTime
     * @param $provider
     * @param $objectId
     * @throws SynapExceptions
     */
    private function checkAppointmentScheduleConflicts($data) {
        $startDate = $data->getData('properties')['startDate'];
        $endDate = $data->getData('properties')['endDate'];
        $startTime = $data->getData('properties')['startTime'];
        $endTime = $data->getData('properties')['endTime'];
        $searchKey = [];
        $searchKey[0]['type'] = 'appointmentSchedule';
        $skipId = (!empty($data->getData('properties')['parentId'])) ? $data->getData('properties')['parentId'] : '';
        /* Check for overlapping conflicts for provider and patient */
        if (empty($data->getData('properties')['provider'])) {
            $searchKey[0]['conditions'][] = array('patientId' => array('IN' => $data->getData('properties')['patientId']));
        } else {
            $searchKey[0]['conditions'][] = array(
                array('provider' => array('IN' => $data->getData('properties')['provider'])),
                'OR',
                array('patientId' => array('IN' => $data->getData('properties')['patientId']))
            );
        }
        //Exclude the id of self.
        if (!empty($data->getData('properties')['id'])) {
            $searchKey[0]['conditions'][] = array('id' => array('ne' => $data->getData('properties')['id']));
        }
        //Exclude the id of self.
        if (!empty($skipId)) {
            $searchKey[0]['conditions'][] = array('id' => array('ne' => $skipId));
        } else {
            if (!empty($data->getData('properties')['id'])) {
                $searchKey[0]['conditions'][] = array('parentId' => array('ne' => $data->getData('properties')['id']));
            }
        }
        if ($startDate == $endDate) {
            /* If passed startDate and endDate are same, conflicts are checked across for records already existing for same or different startDates and endDates, but lying between the passed intervals. */
            $searchKey[0]['conditions'][] = array(
                array(
                    array('startDate' => array('ne' => $startDate)),
                    'AND',
                    array('endDate' => $startDate),
                    'AND',
                    array(
                        array('endTime' => array('ge' => $startTime)),
                        'OR',
                        array('endTime' => array('ge' => $endTime))
                    )
                ),
                'OR',
                array(
                    array('endDate' => array('ne' => $startDate)),
                    'AND',
                    array('startDate' => $startDate),
                    'AND',
                    array(
                        array('startTime' => array('le' => $startTime)),
                        'OR',
                        array('startTime' => array('le' => $endTime))
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
                                array('startTime' => array('ge' => $startTime)),
                                'AND',
                                array('startTime' => array('le' => $endTime))
                            ),
                            'OR',
                            array(
                                array('endTime' => array('ge' => $startTime)),
                                'AND',
                                array('endTime' => array('le' => $endTime))
                            )
                        ),
                        'OR',
                        array(
                            array(
                                array('startTime' => array('le' => $startTime)),
                                'AND',
                                array('endTime' => array('ge' => $startTime))
                            ),
                            'OR',
                            array(
                                array('startTime' => array('le' => $endTime)),
                                'AND',
                                array('endTime' => array('ge' => $endTime))
                            )
                        )
                    )
                )
            );
        } else {
            /* If passed startDate and endDate are not same, conflicts are checked with the requested time/date frames. */
            $searchKey[0]['conditions'][] = array(
                array(
                    array('startDate' => $startDate),
                    'AND',
                    array('startDate' => array('ne' => $endDate)),
                    'AND',
                    array(
                        array('startTime' => array('ge' => $startTime)),
                        'OR',
                        array('endTime' => array('ge' => $startTime))
                    )
                ),
                'OR',
                array(
                    array('endDate' => $endDate),
                    'AND',
                    array('startDate' => array('ne' => $startDate)),
                    'AND',
                    array(
                        array('startTime' => array('le' => $endTime)),
                        'OR',
                        array('endTime' => array('le' => $endTime))
                    )
                ),
                'OR',
                array(
                    array('startDate' => $startDate),
                    'AND',
                    array('endDate' => $endDate)
                )
            );
        }
        $searchKey[0]['outKey'] = 'response';        
        $searchKey[0]['conditions'][] = array('appointmentStatus' => array('NOTIN' => array(self::STATUS_CANCELLED, self::STATUS_CANCELLED_24, self::STATUS_CANCELLED_PROVIDER, self::STATUS_RESCHEDULED)));
        $checkAppointmentConflicts = $this->serviceQue->executeQue("ws_oml_read", $searchKey);
        if (!empty($checkAppointmentConflicts['data']['response'])) {
            $conflictingAppointments = 'A conflicting appointment exists with start date: ' . $checkAppointmentConflicts['data']['response'][0]['startDate'] . ' and end date: ' . $checkAppointmentConflicts['data']['response'][0]['endDate'] . ' at start time ' . $checkAppointmentConflicts['data']['response'][0]['startTime'] . ' and endtime ' . $checkAppointmentConflicts['data']['response'][0]['endTime'] . ' with this provider/patient';
            throw new SynapExceptions(SynapExceptionConstants::CONFLICTING_APPOINTMENTS,400, array('conflictingAppointments' => $conflictingAppointments));
        }
    }

    /**     
      * Ensure no appointment is created on a holiday.      
      *     
      * @author Neetika Pathak <neetika.pathak@sourcefuse.com>      
      * @param type $data       
      * @param $dates       
      * @throws SynapExceptions     
      */        
    private function checkHolidayConflicts($data) {        
        $sessionUtil = SessionUtility::getInstance();      
        $organizationId = $sessionUtil->getOrganizationId();       
        //Check to see if holiday exists for an organization.      
        $searchHoliday = [];       
        $searchHoliday[0]['type'] = 'organizationHoliday';     
        $searchHoliday[0]['outKey'] = 'response';      
        /* Check if appointment day is a holiday. */       
        $searchHoliday[0]['conditions'][] = array(     
         array('date' => $data->getData('properties')['startDate']),        
         'OR',      
         array('date' => $data->getData('properties')['endDate'])       
        );     
        $searchHoliday[0]['conditions'][] = array('organizationId' => $organizationId);        
        $holidayExists = $this->serviceQue->executeQue("ws_oml_read", $searchHoliday);     
        if (!empty($holidayExists['data']['response'])) {      
         throw new SynapExceptions(SynapExceptionConstants::IS_A_HOLIDAY,400, array('holidayName' => $holidayExists['data']['response'][0]['name']));       
        }      
        return true;       
    }

    /**
     * Check when appointment already cancelled, then restrict to update status other than cancelled.
     * 
     * @param type $data, $appointmentData
     * @throws SynapExceptions
     */
    private function checkCancelledAppointment($data, $appointmentData) {
        if (isset($data->getData('properties')['appointmentStatus'])) {
            $arrCancelledStatus = array(self::STATUS_CANCELLED, self::STATUS_CANCELLED_24, self::STATUS_CANCELLED_PROVIDER, self::STATUS_RESCHEDULED);
            if (in_array($appointmentData['data']['response'][0]['appointmentStatus'], $arrCancelledStatus) && (!in_array($data->getData('properties')['appointmentStatus'], $arrCancelledStatus) || $appointmentData['data']['response'][0]['appointmentStatus'] != $data->getData('properties')['appointmentStatus'])) {
                throw new SynapExceptions(SynapExceptionConstants::CANCELLED_APPOINTMENT_STATUS_CAN_NOT_UPDATE,400);
            }
        }
    }

    /**
     * Check if provider schedule exists and check if the appointment lie between these set intervals.
     * 
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkProviderSchedule($data) {
        /* Need to search only in case of individual's appointment. */
        if (isset($provider)) {
            /* Check if provider's schedule exists. */
            $date = new \DateTime($data->getData('properties')['startDate']);
            $searchDayOfWeek = $date->format(DateTimeUtility::WEEKDAY_TIME_FORMAT);
            /* Check if entry of the provider's schedule for the day exists. */
            $ifScheduleExists = $this->ifProviderScheduleExists($data->getData('properties')['provider'], $searchDayOfWeek);
            if ($ifScheduleExists == TRUE) {
                /* Check if entry of the provider's schedule for the start and end time exists. */
                $this->checkProviderScheduleConflicts($searchDayOfWeek, $data->getData('properties')['startTime'], $data->getData('properties')['endTime'], $data->getData('properties')['provider']);
            }
            /* Check if provider's schedule exists. */
        }
    }

    /**
     * Check if provider schedule exists for the day.
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param $provider
     * @param $searchDayOfWeek
     * @throws SynapExceptions
     */
    private function ifProviderScheduleExists($provider, $searchDayOfWeek) {
        //Check the day of week for date.
        $searchSchedule = [];
        $searchSchedule[0]['type'] = 'orgEmpSchedule';
        $searchSchedule[0]['objectId'] = $provider;
        $searchSchedule[0]['outKey'] = 'response';
        $searchSchedule[0]['conditions'][] = array('dayOfWeek' => 'metaDayOfWeek:' . $searchDayOfWeek);
        $providerScheduleAvailable = $this->serviceQue->executeQue("ws_oml_read", $searchSchedule);
        $doesExist = (!empty($providerScheduleAvailable['data']['response'][0])) ? true : false;
        return $doesExist;
    }

    /**
     * Check provider schedule and availability before fixing an appointment.
     *
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $searchDayOfWeek
     * @param $searchStartTime
     * @param $searchEndTime
     * @param $provider
     * @throws SynapExceptions
     */
    private function checkProviderScheduleConflicts($searchDayOfWeek, $searchStartTime, $searchEndTime, $provider) {
        $searchProviderSchedule = [];
        $searchProviderSchedule[0]['type'] = 'orgEmpSchedule';
        $searchProviderSchedule[0]['objectId'] = $provider;
        $searchProviderSchedule[0]['outKey'] = 'response';
        /* Check if entry of the provider's schedule for the day exists. */
        $searchProviderSchedule[0]['conditions'][] = array('dayOfWeek' => 'metaDayOfWeek:' . $searchDayOfWeek);
        /* Check if entry of the provider's schedule for the start and end time exists. */
        $searchProviderSchedule[0]['conditions'][] = array('startTime' => array('le' => $searchStartTime));
        $searchProviderSchedule[0]['conditions'][] = array('endTime' => array('ge' => $searchEndTime));
        $providerScheduleConflict = $this->serviceQue->executeQue("ws_oml_read", $searchProviderSchedule);
        if (!isset($providerScheduleConflict['data']['response'][0])) {
            //Provider Schedule Not available for required time.
            throw new SynapExceptions(SynapExceptionConstants::PROVIDER_SCHEDULE_NOT_AVAILABLE,400,array('startTime' => $searchStartTime, 'endTime' => $searchEndTime));
        }
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
     * create encounter if appointment status changed to arrived
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param type $serviceQue
     * @return boolean
     */
    private function createEncounter($data, $serviceQue) {
        $appointmentScheduleId = $data->getData('conditions')['object'][0];
        $searchKey[0]['objectId'] = $appointmentScheduleId;
        $searchKey[0]['outKey'] = 'response';
        $appointmentData = $serviceQue->executeQue("ws_oml_read", $searchKey);
        if ($data->getData('properties')[self::STATUS] == self::STATUS_ARRIVED &&
                $appointmentData['data']['response'][self::STATUS] != self::STATUS_ARRIVED) {

            $patientIdArr = $providerArr = [];
            $primaryProvider = $scheduledProvider = $referringProvider = '';
            $reasonForVisit = null;
            if (isset($data->getData('properties')['patientId'])) {
                $patientIdArr = $data->getData('properties')['patientId'];
            } elseif (isset($appointmentData['data']['response']['patientId'])) {
                $patientIdArr = $appointmentData['data']['response']['patientId'];
            }
            if (isset($data->getData('properties')['provider'])) {
                $providerArr = $data->getData('properties')['provider'];
            } elseif (isset($appointmentData['data']['response']['provider'])) {
                $providerArr = $appointmentData['data']['response']['provider'];
            }
            if (isset($data->getData('properties')['scheduledProvider'])) {
                $scheduledProvider = $data->getData('properties')['scheduledProvider'];
            } elseif (isset($appointmentData['data']['response']['scheduledProvider'])) {
                $scheduledProvider = $appointmentData['data']['response']['scheduledProvider'];
            }
            if (isset($data->getData('properties')['referringProvider'])) {
                $referringProvider = $data->getData('properties')['referringProvider'];
            } elseif (isset($appointmentData['data']['response']['referringProvider'])) {
                $referringProvider = $appointmentData['data']['response']['referringProvider'];
            }
            if (isset($data->getData('properties')['primaryProvider'])) {
                $primaryProvider = $data->getData('properties')['primaryProvider'];
            } elseif (isset($appointmentData['data']['response']['primaryProvider'])) {
                $primaryProvider = $appointmentData['data']['response']['primaryProvider'];
            }
            $appointmentScheduleProg = $appointmentData['data']['response']['programs'];
            if (isset($data->getData('properties')['programs'])) {
                $appointmentScheduleProg = $data->getData('properties')['programs'];
            }
            if (isset($data->getData('properties')['reasonForVisit'])) {
                $reasonForVisit = $data->getData('properties')['reasonForVisit'];
            } elseif (isset($appointmentData['data']['response']['reasonForVisit'])) {
                $reasonForVisit = $appointmentData['data']['response']['reasonForVisit'];
            }
            // date and time
            $encounterStartDate = $appointmentData['data']['response']['startDate'];
            $encounterStartTime = $appointmentData['data']['response']['startTime'];
            $encounterEndDate = $appointmentData['data']['response']['endDate'];
            $encounterEndTime = $appointmentData['data']['response']['endTime'];
            if (!empty($data->getData('properties')['startDate'])) {
                $encounterStartDate = $data->getData('properties')['startDate'];
            }
            if (!empty($data->getData('properties')['startTime'])) {
                $encounterStartTime = $data->getData('properties')['startTime'];
            }
            if (!empty($data->getData('properties')['endDate'])) {
                $encounterEndDate = $data->getData('properties')['endDate'];
            }
            if (!empty($data->getData('properties')['endTime'])) {
                $encounterEndTime = $data->getData('properties')['endTime'];
            }
            //get location from appointment schedule
            $appointmentLocation = $appointmentData['data']['response']['locationId'];
            $encounterType = !empty($data->getData('properties')['encounterType']) ? $data->getData('properties')['encounterType'] : '';
            // if patient doesn't exists then we can't add encounter
            if (!empty($patientIdArr)) {
                // creating encounter if status is arrived
                foreach ($patientIdArr as $patientId) {
                    $today = new \DateTime();
                    $objectData['properties']['appointmentScheduleId'] = $appointmentScheduleId;
                    $objectData['properties']['encounterStartDate'] = $encounterStartDate;
                    $objectData['properties']['encounterStartTime'] = $encounterStartTime;
                    //    $objectData['properties']['encounterEndDate'] = $encounterEndDate;
                    //    $objectData['properties']['encounterEndTime'] = $encounterEndTime;
                    $objectData['properties']['location'] = $appointmentLocation;
                    if (!empty($encounterType))
                        $objectData['properties']['encounterType'] = $encounterType;
                    $objectData['properties']['program'] = $appointmentScheduleProg;
                    $objectData['properties']['status'] = self::ENCOUNTER_STATUS_INITIATED;
                    if (!empty($reasonForVisit)) {
                        $objectData['properties']['reasonForVisit'] = $reasonForVisit;
                    }
                    if (!empty($providerArr)) {
                        $objectData['properties']['careTeam'] = $providerArr;
                    }

                    if (!empty($scheduledProvider)) {
                        $objectData['properties']['scheduledProvider'] = $scheduledProvider;
                    }
                    if (!empty($referringProvider)) {
                        $objectData['properties']['referringProvider'] = $referringProvider;
                    }
                    if (!empty($primaryProvider)) {
                        $objectData['properties']['attendingProvider'] = $primaryProvider;
                        $dateTimeUtility = new DateTimeUtility();
                        $currDate = date($dateTimeUtility::DATE_FORMAT);
                        /**
                         * Author Kuldeep Singh, Date: 08-08-2017, JIRA Id: AP-553, AP-339
                         * Appointment checking-in : Relevant patient insurance payer has tax entity/transmission tunnel assignments
                         * 
                         */
                        $patientInsurancecData = $this->checkPatientInsurance($serviceQue, $patientId, $currDate);
                        if (isset($patientInsurancecData) && count($patientInsurancecData) > 0) {
                            $arrInsurancePayers = $this->patientInsurancePayers($patientInsurancecData);
                            $orgTaxEntityPayerConfigData = $this->payerWiseOrgTaxPayerConfig($serviceQue, $appointmentLocation, $arrInsurancePayers);
                            if (isset($arrInsurancePayers['primaryPayer'])) {
                                if (count($orgTaxEntityPayerConfigData) > 0 && count($orgTaxEntityPayerConfigData['primaryPayer']) > 0) {

                                    $orgPayerId = $orgTaxEntityPayerConfigData['primaryPayer'][0]['organizationPayerId'];
                                    $taxEntityId = $orgTaxEntityPayerConfigData['primaryPayer'][0]['taxEntityId'];

                                    /**
                                     * Author Kuldeep Singh, Date: 23-08-2017, JIRA Id: AP-1297
                                     * check on appointment checkin for accounting periods existing with open status for primary payer
                                     * 
                                     */
                                    $this->checkValidTaxEntityAccountingPeriod($serviceQue, $taxEntityId, $currDate);

                                    $orgEmpPayerExistingData = $this->checkOrgEmpPayerTaxEntity($serviceQue, $orgPayerId, $taxEntityId, $primaryProvider, $currDate);
                                    if (isset($orgEmpPayerExistingData) && count($orgEmpPayerExistingData) > 0) {
                                        $objectData['properties']['attendingProvider'] = $primaryProvider;
                                    } else {
                                        throw new SynapExceptions(SynapExceptionConstants::ATTENDING_PROVIDER_NOT_REGISTERED_WITH_PATIENT_PRIMARY_PAYER,400);
                                    }
                                }
                            }
                        }
                    }

                    $objectData['parent'] = $patientId;
                    $objectData['objectType'] = 'patientEncounter';
                    //todo Naveen remove code duplication from pre and post execute
                    $encounterResponse = $serviceQue->executeQue('ws_oml_create', $objectData);
                    if (isset($encounterResponse['data']['id'])) {
                        $encounterId = $encounterResponse['data']['id'];
                        $encounterType = '';
                        if (!empty($data->getData('properties')['encounterType'])) {
                            $encounterType = $data->getData('properties')['encounterType'];
                        } else {
                            if (!empty($appointmentData['data']['response']['encounterType'])) {
                                $encounterType = $appointmentData['data']['response']['encounterType'];
                            }
                        }
                        $executionId = $this->triggerEncounterWorkflow($encounterId, $patientId, $encounterType, $serviceQue);
                        if (!empty($executionId)) {
                            $updateObjs['conditions']['object'][0] = $encounterId;
                            $updateObjs['properties']['executionId'] = $executionId;
                            $updateResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * todo Naveen remove code duplication from pre and post execute
     * trigger encounter workflow
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $encounterId
     * @param type $patientId
     * @return boolean
     */
    private function triggerEncounterWorkflow($encounterId, $patientId, $encounterType, $serviceQue) {
        $executionId = '';
        if (!empty($encounterType)) {
            $data = array('patientEncounterId' => $encounterId, 'patientId' => $patientId);
            
            $configurator = \SynapEssentials\TransactionManagerBundle\EventListener\Configurator::getInstance();
            $container = $configurator->getServiceContainer();
            $exeManagerObject = ExecutionManager::getInstance($container);
            // Get workflow ID
            $searchKey = [];
            $searchKey[0]['objectId'] = $encounterType;
            $searchKey[0]['outKey'] = 'response';
            $encounterConfigResponse = $serviceQue->executeQue("ws_oml_read", $searchKey);
            if (!empty($encounterConfigResponse['data']['response']['workflow'])) {
                // Start the workflow
                $executionId = $exeManagerObject->startWorkFlow($data, array('workflowId' => $encounterConfigResponse['data']['response']['workflow'], 'workflowIdName' => $encounterConfigResponse['data']['response']['workflowName']));
            }
        }
        return $executionId;
    }

    /**
     * @description function will Ensure length of appointmentSchedule must be multiple of 5
     * @author Neetika Pathak <neetika.pathak@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkValidDuration($data) {
        $isMultiple = $data->getData('properties')['duration'] % 5;
        if ($isMultiple != 0) {
            throw new SynapExceptions(SynapExceptionConstants::DURATION_INVALID,400);
        }
    }

    /**
     * @description this function fetch and return the patientInsurance details
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $patientId, $insuranceCompanyRank, $coverageDate
     * @return patientInsurance detail
     */
    private function checkPatientInsurance($serviceQue, $patientId, $coverageDate) {
        $patientInsurancecSearchKey = [];
        $patientInsurancecSearchKey[0]['type'] = 'patientInsurance';
        $patientInsurancecSearchKey[0]['conditions'][] = array('patientId' => $patientId);
//        $patientInsurancecSearchKey[0]['conditions'][] = array('insuranceCompanyRank' => self::PRIMARY_COMPANY_RANK);
        $patientInsurancecSearchKey[0]['conditions'][] = array('coverageStartDate' => array('LE' => $coverageDate));
        $patientInsurancecSearchKey[0]['conditions'][] = array(
            array('coverageEndDate' => array('GE' => $coverageDate)),
            "OR",
            array('coverageEndDate' => array('isnull' => null))
        );
        $patientInsurancecSearchKey[0]['sendNullKey'] = 1;
        $patientInsurancecSearchKey[0]['outKey'] = 'response';
        $patientInsurancecData = $serviceQue->executeQue('ws_oml_read', $patientInsurancecSearchKey);
        $patientInsuranceResponse = $patientInsurancecData['data']['response'];
        return $patientInsuranceResponse;
    }

    /**
     * @description this function fetch and return the organizationTaxEntityPayerConfig details
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $orgPayerId, $locationId
     * @return organizationTaxEntityPayerConfig detail
     */
    private function checkOrgTaxEntityPayerConfig($serviceQue, $orgPayerId, $locationId) {
        $orgTaxEntityPayerConfigSearchKey = [];
        $orgTaxEntityPayerConfigSearchKey[0]['type'] = 'organizationTaxEntityPayerConfig';
        $orgTaxEntityPayerConfigSearchKey[0]['conditions'][] = array('organizationPayerId' => $orgPayerId);
        $orgTaxEntityPayerConfigSearchKey[0]['conditions'][] = array('locationId' => $locationId);
        $orgTaxEntityPayerConfigSearchKey[0]['sendNullKey'] = 1;
        $orgTaxEntityPayerConfigSearchKey[0]['outKey'] = 'response';
        $orgTaxEntityPayerConfigData = $serviceQue->executeQue('ws_oml_read', $orgTaxEntityPayerConfigSearchKey);
        $orgTaxEntityPayerConfigResponse = $orgTaxEntityPayerConfigData['data']['response'];
        return $orgTaxEntityPayerConfigResponse;
    }

    /**
     * @description this function fetch and return the organizationEmployeePayerTaxEntity details
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $orgPayerId, $taxEntityId, $employeeId, $currentDate
     * @return organizationEmployeePayerTaxEntity detail
     */
    private function checkOrgEmpPayerTaxEntity($serviceQue, $orgPayerId, $taxEntityId, $employeeId, $currentDate) {
        $orgEmpPayerSearchKey = [];
        $orgEmpPayerSearchKey[0]['type'] = 'organizationEmployeePayerTaxEntity';
        $orgEmpPayerSearchKey[0]['conditions'][] = array('payerId' => $orgPayerId);
        $orgEmpPayerSearchKey[0]['conditions'][] = array('taxEntityId' => $taxEntityId);
        $orgEmpPayerSearchKey[0]['conditions'][] = array('employeeId' => $employeeId);
        $orgEmpPayerSearchKey[0]['conditions'][] = array('startDate' => array('LE' => $currentDate));
        $orgEmpPayerSearchKey[0]['conditions'][] = array(
            array('endDate' => array('GE' => $currentDate)),
            "OR",
            array('endDate' => array('isnull' => null))
        );
        $orgEmpPayerSearchKey[0]['sendNullKey'] = 1;
        $orgEmpPayerSearchKey[0]['outKey'] = 'response';
        $orgEmpPayerExistingData = $serviceQue->executeQue('ws_oml_read', $orgEmpPayerSearchKey);
        $orgEmpPayerExistingResponse = $orgEmpPayerExistingData['data']['response'];
        return $orgEmpPayerExistingResponse;
    }

    /**
     * @description this function array of Patient Insurance Payers
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $arrPatientInsurances
     * @return Insurnace payer array
     */
    private function patientInsurancePayers($arrPatientInsurances) {
        $arrOrgPayers = array();
        if (count($arrPatientInsurances) > 0) {
            foreach ($arrPatientInsurances as $arrPatientInsurance) {
                switch ($arrPatientInsurance['insuranceCompanyRank']) {
                    case self::PRIMARY_COMPANY_RANK:
                        $arrOrgPayers['primaryPayer'] = $arrPatientInsurance['insuranceCompanyName'];
                        break;
                    case self::SECONDARY_COMPANY_RANK:
                        $arrOrgPayers['secondaryPayer'] = $arrPatientInsurance['insuranceCompanyName'];
                        break;
                    case self::TERTIARY_COMPANY_RANK:
                        $arrOrgPayers['tertiaryPayer'] = $arrPatientInsurance['insuranceCompanyName'];
                        break;
                }
            }
        }
        return $arrOrgPayers;
    }

    /**
     * @description this function fetch and return the organizationTaxEntityPayerConfig details according to Patient Insurance Payer
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $locationId, $arrPatientInsurancec
     * @return organizationTaxEntityPayerConfig detail
     */
    private function payerWiseOrgTaxPayerConfig($serviceQue, $locationId, $arrOrgPayers) {
        $arrOrgTaxPayerConfig = array();
        if (count($arrOrgPayers) > 0) {
            foreach ($arrOrgPayers as $payer => $orgPayerId) {
                $orgTaxEntityPayerConfigData = $this->checkOrgTaxEntityPayerConfig($serviceQue, $orgPayerId, $locationId);
                $arrOrgTaxPayerConfig[$payer] = $orgTaxEntityPayerConfigData;
                if (count($orgTaxEntityPayerConfigData) <= 0) {
                    throw new SynapExceptions(SynapExceptionConstants::PATIENT_INSURANCE_ASSIGNMENT_TAX_ENTITY_TRANS_TUNNEL_NOT_EXIST, 400,array('property' => $payer));
                    break;
                }
            }
        }
        return $arrOrgTaxPayerConfig;
    }

    /**
     * @description This fucntion check accounting periods existing with open status for particular organizationTaxEntityId
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param $serviceQue, $orgTaxEntityId, $effectiveDate
     * @return true
     */
    private function checkValidTaxEntityAccountingPeriod($serviceQue, $orgTaxEntityId, $effectiveDate) {
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityAccountPeriod';
        $searchKey[0]['conditions'][] = ['organizationTaxEntityId' => $orgTaxEntityId];
        $searchKey[0]['conditions'][] = ['startDate' => ["LE" => $effectiveDate]];
        $searchKey[0]['conditions'][] = ['endDate' => ["GE" => $effectiveDate]];
        $searchKey[0]['conditions'][] = ['status' => self::OPEN_ACCOUNTING_PERIOD_STATUS];
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $respData = $resp['data']['response'];

        if (empty($respData) || empty($respData[0])) {
            throw new SynapExceptions(SynapExceptionConstants::ACCOUNTING_PERIOD_OPEN_NOT_EXISTS,400);
        }
        return TRUE;
    }

}
