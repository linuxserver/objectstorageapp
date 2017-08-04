<?php
/*
'objectstore' => array(
        'class' => 'OCA\\ObjectStorageBackblaze\\ObjectStore\\Backblaze',
        'arguments' => array(
                'clientId' => 'username',
                'applicationId' => 'Secr3tPaSSWoRdt7',
                // the container to store the data in
                'bucketName' => 'nextcloud',
                'bucketId' => 'sdfsd24f22f24f2f4f',
        ),
),
 */

namespace OCA\ObjectStorageApp\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;
use OCA\ObjectStorageApp\Service\BackblazeService;
use OCP\AppFramework\App;
use OCP\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;



class Backblaze extends App implements IObjectStore {

	protected $client;
	protected $app = 'objectstorageapp';

	public function __construct(array $params=array()) {
		parent::__construct($this->app, $params);


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
		$this->client = \OCP\Config::getAppValue($this->app, 'auth');
		
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
			\OCP\Config::setAppValue($this->app, 'auth', $details);
			$this->client = $details;
		}
	}

	protected function getObject($urn) {
		$client = new \GuzzleHttp\Client();
		$response = $client->get($client->downloadUrl.'/file/'.$this->params['bucketName'].'/'.$urn, [
		    'auth' => [
		        $client->authorizationToken
		    ]
		]);
		if($response->getStatusCode() === 200) {
			return $response;
		}
		
	}

    protected function uploadObject($urn, $stream, $try=0) {
        $maxtries = 5;
        $client = new \GuzzleHttp\Client();
        // Get upload url first
        $response = $client->get($client->apiUrl.'/b2api/v1/b2_get_upload_url', [
            'json' => [
                'bucketId' => $this->params['bucketId']
            ]
        ]);
        if($response->getStatusCode() === 200) {
            $details = json_decode($response->getBody());
            $content_length = strlen($stream);
            //$content_length += 40; // Size of the SHA1 checksum, only applicable if sending checksum
            $upload = $client->request('POST', $client->apiUrl.'/b2api/v1/b2_upload_file', [
                'headers' => [
                    'Authorization'  => $details->authorizationToken,
                    'X-Bz-File-Name' => $urn,
                    'Content-Type'   => 'application/octet-stream',
                    'Content-Length' => $content_length,
                    'X-Bz-Content-Sha1' => 'do_not_verify' // Can you get the sha1 of a stream?
                ],
                'body' => $stream
            ]);
            switch($upload->getStatusCode()) {
                case 200:
                    return true;
                    break;
                default:
                    $try++;
                    if($try <= $maxtries) {
                        $this->uploadObject($urn, $stream, $try);
                    }
                    break;
            }

        }
    }


	/**
	 * @return string the container name where objects are stored
	 */
	public function getStorageId() {
		return $this->params['bucketId'];
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function writeObject($urn, $stream) {
		$this->init();
        $this->uploadObject($urn, $stream, 0);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function readObject($urn) {
		$this->init();
		$object = $this->getObject($urn);

		$objectContent = $object->getBody()->getContents();
		//$stream = $objectContent;
		$objectContent->rewind();
        $stream = Psr7\stream_for($objectContent);
		//$stream = $objectContent->getStream();
		// save the object content in the context of the stream to prevent it being gc'd until the stream is closed
		stream_context_set_option($stream, 'backblaze', 'content', $objectContent);
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
