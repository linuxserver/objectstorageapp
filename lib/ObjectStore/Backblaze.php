<?php
/*
'objectstore' => array(
        'class' => 'OCA\\ObjectStorageBackblaze\\ObjectStore\\Backblaze',
        'arguments' => array(
                'clientId' => 'username',
                'applicationId' => 'Secr3tPaSSWoRdt7',
                // the container to store the data in
                'bucket' => 'nextcloud',
        ),
),
 */

namespace OCA\ObjectStorageApp\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;
use \OCA\ObjectStorageApp\Service\BackblazeService;
use \OCP\AppFramework\App;

class Backblaze extends App implements IObjectStore {

	protected $client;

	public function __construct(array $params=array()) {
		parent::__construct('objectstorageapp', $params);

        $container = $this->getContainer();


        $container->registerService('BackblazeService', function($c) {
            return new BackblazeService(
                $c->query('Config'),
                $c->query('AppName')
            );
        });

        $container->registerService('Config', function($c) {
            return $c->query('ServerContainer')->getConfig();
        });

        //var_dump($container);



		$this->params = $params;
		$config = $container->BackblazeService;
		$this->client = $config->getAppValue('auth');
		
	}

	protected function init() {
		// Is it authorised
		if ($this->client) {
			return;
		}

		// not authorised so lets get authorised
		$this->authenticate();

		// Set the container object

	}

	protected function authenticate() {
		$client = new \GuzzleHttp\Client();
		$response = $client->get('https://api.backblazeb2.com/b2api/v1/b2_authorize_account', [
		    'auth' => [
		        $this->params['accountId'], 
		        $this->params['applicationId']
		    ]
		]);
		if($response->getStatusCode() === 200) {
			$details = json_decode($response->getBody());
			\Service()->setAppValue('auth', $details);
			$this->client = $details;
		}
	}

	protected function getObject($urn) {
		$client = new \GuzzleHttp\Client();
		$response = $client->get('https://api.backblazeb2.com/b2api/v1/b2_authorize_account', [
		    'auth' => [
		        $this->params['accountId'], 
		        $this->params['applicationId']
		    ]
		]);
		if($response->getStatusCode() === 200) {
			$this->client = json_decode($response->getBody());
		}
		
	}


	/**
	 * @return string the container name where objects are stored
	 */
	public function getStorageId() {
		return $this->params['storageid'];
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function writeObject($urn, $stream) {
		echo 'write - '.var_dump($urn).' - '.$stream;
		//$this->init();
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function readObject($urn) {
		echo 'read - '.var_dump($urn);
		$this->init();
		$object = $this->getObject($urn);
		$stream = '';
		return $stream;
	}

	/**
	 * @param string $urn Unified Resource Name
	 * @return void
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function deleteObject($urn) {
		echo 'delete';
		//$this->init();
	}

	public function deleteContainer($recursive = false) {
		echo 'deletecontainer';
		//$this->init();
	}	

}
