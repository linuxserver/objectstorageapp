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

namespace OCA\ObjectStorageBackblaze\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;

class Backblaze implements IObjectStore {

}
