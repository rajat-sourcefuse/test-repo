<?php

/**
 * test comment
 * php version 5.4.* +
 *
 *
 */

namespace SynapEssentials\TransactionManagerBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use Symfony\Component\HttpFoundation\Response;
use SynapEssentials\WorkFlowBundle\Managers\ExecutionManager;
use SynapEssentials\AuthenticationBundle\Utilities\LoginUtility;
use SynapEssentials\AuthenticationBundle\Implementation\TokenService;
use function GuzzleHttp\json_encode;

class TransactionManager extends Event
{

    private $serviceContainer;
    private $doctorine;
    private $entityManager;
    private $eventDispatcher;
    private $WFEMO;
    private $transactionStarted;


    /**
     * Short_description:test description.
     *
     * @param type $container something
     *
     */
    public function __construct($container)
    {
        // echo "<pre>";
        // \Doctrine\Common\Util\Debug::dump($container->get('request_stack' )->getCurrentRequest());
        $this->transactionStarted = false;
        $this->serviceContainer = $container;
        $this->doctorine = $this->serviceContainer->get("doctrine");
        $this->entityManager = $this->doctorine->getManager();
        $this->eventDispatcher = $this->serviceContainer->get('event_dispatcher');
    }

    /**
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {

        // Initialize configurator.
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return true;
        }

        $configurator = Configurator::getInstance($this->serviceContainer);
        if ($event->isMasterRequest()) {
            //begin transaction
            $request = $this->serviceContainer->get('request_stack')->getCurrentRequest();

            $getData = $request->get('_route_params');


            //the line was commented by Sourav Bhargava to allow audti logging for get calls

            if (!$this->transactionStarted) {
                $this->entityManager->getConnection()->beginTransaction();
                //commented by Sourav temporarily dated 8june 18
                $configurator->getWFDb()->beginTransaction();

                $this->transactionStarted = true;
            }
            $request = $this->serviceContainer->get('request_stack')->getCurrentRequest();

            $loginUtility = LoginUtility::getInstance();

            $loginUtility->validateAuthentication($request->getPathInfo());


            //dispach ACL EVENT
            $this->eventDispatcher->dispatch('tm.acl', $event);
            //creating WorkFlow ManagerExecution Object
            if ($this->WFEMO == null) {

                $this->WFEMO = ExecutionManager::getInstance($this->serviceContainer);
            }
        }
    }

    /**
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $response = $event->getResponse();
            // Add headers for CORS
            #$response->headers->set('Access-Control-Allow-Origin', 'aperio-core-ehr-dev.aperiohealth.com');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE,OPTIONS');
            #$response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
            $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept, X-Custom-Auth, password, username, dwt-md5,token');
            $response->headers->set('Access-Control-Allow-Crendential','true');
            $response->headers->set('Access-Control-Expose-Headers', 'token');
            $event->setResponse($response);
            return true;
        }

        if ($this->WFEMO == null) {

            $this->WFEMO = ExecutionManager::getInstance($this->serviceContainer);
        }

        if ($event->isMasterRequest()) {
            $this->WFEMO->endExecution($event);
            if ($this->transactionStarted) {
                $this->eventDispatcher->dispatch('tm.preCommit', $event);

                $this->entityManager->getConnection()->commit();
                $configurator = Configurator::getInstance($this->serviceContainer);
                //commented by Sourav temporarily dated 8june 18
                $configurator->getWFDb()->commit();
                //post commit event added, listeners hearing this event are
                //expected to not commit to db
                $this->eventDispatcher->dispatch('tm.postCommit', $event);
                $this->eventDispatcher->dispatch('tm.accessLogResponse', $event);
            }
        }
        $tokenService = TokenService::getInstance();

        $headers = (array)$this->serviceContainer->get('request_stack')->getCurrentRequest()->headers->all();

        if (isset($headers['responsepayload'])) {
            $rpl = json_decode($headers['responsepayload'][0], true);

            $content = json_decode($event->getResponse()->getContent(), true);
            $content['data'] = array_merge($content['data'], $rpl);
            $event->getResponse()->setContent(json_encode($content));
        }

        #$response->headers->set('Access-Control-Allow-Origin', 'aperio-core-ehr-dev.aperiohealth.com');
        $event->getResponse()->headers->add(array('Access-Control-Allow-Methods'=> 'GET, POST, PUT, DELETE,OPTIONS'));
        #$response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
        $event->getResponse()->headers->add(array('Access-Control-Allow-Headers'=> 'origin, content-type, accept, X-Custom-Auth, password, username, dwt-md5,token'));
        $event->getResponse()->headers->add(array('Access-Control-Allow-Crendential'=>'true'));
        $event->getResponse()->headers->add(array('Access-Control-Expose-Headers'=> 'token'));
        $event->getResponse()->headers->add(array('token' => $tokenService->getToken()));
    }

    /**
     * @author sourav Bhargava
     * one stop exception handling for complete synap API
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $response = $event->getResponse();
            // Add headers for CORS
            #$response->headers->set('Access-Control-Allow-Origin', 'aperio-core-ehr-dev.aperiohealth.com');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE,OPTIONS');
            #$response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
            $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept, X-Custom-Auth, password, username, dwt-md5,token');
            $response->headers->set('Access-Control-Allow-Crendential','true');
            $response->headers->set('Access-Control-Expose-Headers', 'token');
            $event->setResponse($response);
            return true;
        }
        $configurator = Configurator::getInstance($this->serviceContainer);
        $ex = $event->getException();

        // echo $ex->getFile();
        // echo $ex->getLine();
        // echo $ex->getMessage();
        // echo $ex->getTraceAsString();
        // exit;


        //master request code commented by Sourav since it misses rollback on subbsequent request
        //if ($event->isMasterRequest()) {
        if ($this->transactionStarted) {
            $this->eventDispatcher->dispatch('tm.rollback', $event);
            $this->entityManager->getConnection()->rollback();
            //commented by Sourav temporarily dated 8june 18
            $configurator->getWFDb()->rollback();
        }
        //}


        if ($ex->getCode() != 401) {
            $response = new Response();
            if ($ex instanceof SynapExceptions) {
                $responseData = $ex->getErrorCode();


                $response->headers->set('X-Status-Code', $ex->getStatusCode());
                //
            } else {

                $response->headers->set('X-Status-Code', 400);
                //

                $responseData = $ex->getMessage();
            }
            $responseData = json_encode(
                array('status' =>
                array('success' => false, 'message' => $responseData), 'clientVersion' => $configurator->getParameter('client_version'))
            );

            $response->setContent($responseData);

            $response->headers->set('Access-Control-Allow-Credentials', "true");
            $response->headers->set('Content-Type', 'application/json');
            $this->eventDispatcher->dispatch('tm.accessLogExceptionResponse', $event);
            $event->setResponse($response);
        } else {
            $responseData = $ex->getMessage();

            $responseData = json_encode(
                array('status' =>
                array('success' => false, 'message' => $responseData), 'clientVersion' => $configurator->getParameter('client_version'))
            );
            $response = new Response();
            $response->setContent($responseData);
            $response->setStatusCode(401);
            $response->headers->set('Access-Control-Allow-Credentials', "true");
            $response->headers->set('Content-Type', 'application/json');
            $this->eventDispatcher->dispatch('tm.accessLogExceptionResponse', $event);
            $event->setResponse($response);
        }
    }
}
