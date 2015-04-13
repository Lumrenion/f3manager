<?php
namespace LumIT\F3manager\Controller;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Philipp Seßner <philipp.sessner@gmail.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use LumIT\F3manager\Service\ZipPackingService;
use LumIT\F3manager\Service\ZipUnPackingService;
use TYPO3\CMS\Core\FormProtection\Exception;
use RuntimeException;

/**
 * FileManagerController
 */
class FileManagerController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
    /** @var $securityService \LumIT\F3manager\Security\CheckPermissions */
    protected $CheckPermissions;
    /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
    protected $resourceFactory;

    protected $extConfig;

    protected function initializeAction() {
        $this->CheckPermissions = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('LumIT\\F3manager\\Security\\CheckPermissions');
        $this->resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

        $this->extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['f3manager']);
    }

	/**
	 * action openFolder
     * Renders a Folder of a storage
     * if openFolder is set, it renders the folder, if accessible
     * else it renders the folder defined in the Settings
	 *
	 * @argument openFolder String Identifier of the Folder to open
     * @return void
	 */
	public function openFolderAction() {
        $openFolder = $this->settings['folder'];

        if ($this->request->hasArgument('openFolder')) {
            $openFolder = $this->request->getArgument('openFolder');
        }

        try {
            $folder = $this->getFolderObjectinsideVirtualRootFromCombinedIdentifier($this->settings['storage'].':'.$openFolder);
            $parentFolders = $this->getParentFoldersUntilVirtualRoot($folder);
        } catch(\TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException $exception) {
            // folder not found
            $this->addFlashMessage('The requested Folder "'.$openFolder.'" does not exist', 'Non-Existent Folder', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        } catch(\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException $exception) {
            // folder not in virtual root (which is the combined Identifier of storage and folder specified in the settings)
            $this->addFlashMessage($exception->getMessage(), 'Non-Accessible Folder', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        }

        $this->view->assignMultiple(array('folder' => $folder, 'parentFolders' => $parentFolders));
	}

	/**
	 * action createFolder
	 * Creates a new Folder inside an existing Folder and redirects to the existing Folder when finished
     *
     * @argument \TYPO3\CMS\Core\Resource\Folder folder where to create the new folder
     * @argument newFolder String the name of the new folder
	 * @return void
	 */
	public function createFolderAction() {
        if($this->request->hasArgument('folder')) {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'] . ':' . $this->request->getArgument('folder')['identifier']);

            if($this->request->hasArgument('newFolder')) {
                $newFolder = $this->request->getArgument('newFolder');
                $userFeGroups = $this->getFeUserGroups();

                if ($this->CheckPermissions->checkFolderWritePermission($folder, $userFeGroups)) {
                    $folder->createFolder($newFolder);
                } else {
                    $this->addFlashMessage("Folder '".$newFolder."' could not be created.", 'No Folder created', \TYPO3\CMS\Core\Messaging\AbstractMessage::NOTICE, FALSE);
                }
            }
            $this->redirect("openFolder",null,null,array('openFolder' => $folder->getIdentifier()));
        }
        $this->addFlashMessage("Please select a parent Folder", 'No Parent-Folder selected', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        $this->redirect("openFolder");
	}

    /**
     * action renameFolder
     * Renames an existing folder and redirects to the existing Folder when finished
     *
     * @argument \TYPO3\CMS\Core\Resource\Folder folder to rename
     * @argument newName String the new name of the folder
     * @return void
     */
    public function renameFolderAction() {
        if($this->request->hasArgument('folder')) {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'] . ':' . $this->request->getArgument('folder')['identifier']);

            if($this->request->hasArgument('newName')) {
                $newFolderName = $this->request->getArgument('newName');

                if($folder->getIdentifier() != $this->settings['folder']) {
                    $userFeGroups = $this->getFeUserGroups();

                    if ($this->CheckPermissions->checkFolderWritePermission($folder, $userFeGroups)) {
                        $folder = $folder->rename($newFolderName);
                    } else {
                        $this->addFlashMessage('Not enough permissions to rename folder "'.$folder->getIdentifier().'"', 'Folder not renameable', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
                    }
                } else {
                    $this->addFlashMessage('The Folder "'.$folder->getIdentifier().'" could not be renamed', 'Folder not renameable', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
                }

                $this->redirect("openFolder",null,null,array('openFolder' => $folder->getIdentifier()));
            }
        }

        $this->addFlashMessage("Please select a Folder to rename", 'No Folder selected', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        $this->redirect("openFolder");

    }

	/**
	 * action downloadFolder
	 *
     * @argument folder String identifier of the Folder
	 * @return void
	 */
	public function downloadFolderAction() {
        if($this->request->hasArgument('folder')) {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'] . ':' . $this->request->getArgument('folder'));

            /** @var \LumIT\F3manager\Service\ZipPackingService */
            $zipPackingService = new ZipPackingService($this->getFeUserGroups(),$folder);
            $zippedFile = $zipPackingService->getZip();


            $this->response->setHeader('Cache-control', 'public', TRUE);
            $this->response->setHeader('Content-Description', 'File transfer', TRUE);
            $this->response->setHeader('Content-Disposition', 'attachment; filename=' . $zipPackingService->getFileName(), TRUE);
            $this->response->setHeader('Content-Type', 'application/octet-stream', TRUE);
            $this->response->setHeader('Content-Transfer-Encoding', 'binary', TRUE);
            $this->response->sendHeaders();

            @readfile($zippedFile);
        }

        exit();
	}

    /**
     * action uploadZip
     * Uploads and extracts ZIP file (single) and redirects to the Folder when finished
     *
     * @argument folder where to upload the files
     * @param $_FILES ZIP-File to upload
     */
    public function uploadZipAction() {
        if ($this->request->hasArgument('folder')) {
            $destinationFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'].':'.$this->request->getArgument('folder')['identifier']);

            $userFeGroups = $this->getFeUserGroups();

            if($this->CheckPermissions->checkFolderWritePermission($destinationFolder, $userFeGroups)) {
                if($_FILES['tx_f3manager_pi1']['name']['folder']['file'][0] != '') {
                    $newFileObjects = array();
                    $storageRepository = $this->objectManager->get('TYPO3\CMS\Core\Resource\StorageRepository');
                    /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storage */
                    $storage = $storageRepository->findByUid($this->settings['storage']);

                    $fileData = array();
                    $fileData['name']     = $_FILES['tx_f3manager_pi1']['name']['folder']['file'];
                    $fileData['type']     = $_FILES['tx_f3manager_pi1']['type']['folder']['file'];
                    $fileData['tmp_name'] = $_FILES['tx_f3manager_pi1']['tmp_name']['folder']['file'];
                    $fileData['size']     = $_FILES['tx_f3manager_pi1']['size']['folder']['file'];
                    $fileData['error']    = $_FILES['tx_f3manager_pi1']['error']['folder']['file'];

                    $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed', 'application/octet-stream');
                    if(!in_array($fileData['type'], $accepted_types)) {
                        throw new RuntimeException('File not a ZIP-File.');
                    }

                    switch ($fileData['error']) {
                        case UPLOAD_ERR_OK:
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            throw new RuntimeException('No file sent.');
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            throw new RuntimeException('Exceeded filesize limit.');
                        default:
                            throw new RuntimeException('Unknown errors.');
                    }

                    // You should also check filesize here (15MB)
                    $maxFileSizeInByte = $this->toFloat($this->extConfig['maxZipUploadSize'])*1048576;
                    if ($fileData['size'] > $maxFileSizeInByte) {
                        throw new RuntimeException('Exceeded filesize limit of '. $this->extConfig['maxZipUploadSize'] . 'MB.');
                    }



                    /** @var \LumIT\F3manager\Service\ZipUnPackingService */
                    $zipUnpackingService = new ZipUnPackingService($destinationFolder, $fileData);
                    $zipUnpackingService->run($destinationFolder, $fileData);
                }
            } else {
                $this->addFlashMessage("Not enough permissions to upload File", "Permission denied", \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
            }

            $this->redirect("openFolder",null,null,array('openFolder' => $destinationFolder->getIdentifier()));


            /*
            // Dateiobjekt
            $repositoryFileObject = $storage->getFile($newFileObject->getIdentifier());

            #$newFileReference = $this->objectManager->get('TYPO3\CMS\Extbase\Domain\Model\FileReference');
            #$newFileReference->setOriginalResource($repositoryFileObject);

            return $newFileReference;
            */
        }

        $this->addFlashMessage("Please select a destination Folder", 'No Folder selected', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        $this->redirect("openFolder");
    }

	/**
	 * action deleteFolder
	 * Deletes an existing Folder recursively and redirects to the existing Folder when finished
     *
     * @argument String folder-Identifier that represents the folder inside the storage
	 * @return void
	 */
	public function deleteFolderAction() {
        if($this->request->hasArgument('folder')) {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'] . ':' . $this->request->getArgument('folder'));

            $userFeGroups = $this->getFeUserGroups();

            if(
                $folder->getIdentifier() != $this->settings['folder'] &&
                $folder != $folder->getParentFolder() &&
                $this->CheckPermissions->checkFolderWritePermission($folder->getParentFolder(), $userFeGroups) &&
                $this->CheckPermissions->checkFolderWritePermission($folder, $userFeGroups)
            ) {
                $parentFolder = $folder->getParentFolder();
                $folder->delete();

                $this->redirect("openFolder",null,null,array('openFolder' => $parentFolder->getIdentifier()));
            } else {
                $this->addFlashMessage('Not enough permissions to delete folder "'.$folder->getIdentifier().'"', 'Folder not deletable', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
            }

            $this->redirect("openFolder",null,null,array('openFolder' => $folder->getIdentifier()));
        }

        $this->addFlashMessage("Please select a Folder to delete", 'No Folder selected', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        $this->redirect("openFolder");
	}

    /**
     * action renameFiles
     *
     * @argument associative Array id => newName
     */
    public function renameFilesAction() {
        if($this->request->hasArgument('folder')) {
            $folderIdentifier = $this->request->getArgument('folder')['identifier'];
            if($this->request->hasArgument('files')) {
                $userFeGroups = $this->getFeUserGroups();
                $processedFiles = array();
                foreach($this->request->getArgument('files') as $fileId=>$newFileName) {
                    $file = $this->resourceFactory->getFileObject($fileId);
                    if ($this->CheckPermissions->checkFileChangeAccess($file, $userFeGroups)) {
                        $processedFiles[] = $file->rename($newFileName);
                    }
                }
            }
            $this->redirect("openFolder",null,null,array('openFolder' => $folderIdentifier));
        }

        $this->addFlashMessage("Please select a Folder where to rename Files in", 'No Folder selected', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        $this->redirect("openFolder");
    }

	/**
	 * action uploadFiles
	 * Uploads Files (Single and Multiple) and redirects to the Folder when finished
     *
	 * @argument folder where to upload the files
     * @param $_FILES to upload
	 */
	public function uploadFilesAction() {
        if ($this->request->hasArgument('folder')) {

            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'].':'.$this->request->getArgument('folder')['identifier']);

            $userFeGroups = $this->getFeUserGroups();

            if($this->CheckPermissions->checkFolderWritePermission($folder, $userFeGroups)) {
                if($_FILES['tx_f3manager_pi1']['name']['folder']['file'][0] != '') {
                    $newFileObjects = array();

                    foreach($_FILES['tx_f3manager_pi1']['name']['folder']['file'] as $key=>$file) {
                        // Check $_FILES['tx_f3manager_pi1']['error'] value.
                        $fileData = array();
                        $fileData['name']     = $_FILES['tx_f3manager_pi1']['name']['folder']['file'][$key];
                        $fileData['type']     = $_FILES['tx_f3manager_pi1']['type']['folder']['file'][$key];
                        $fileData['tmp_name'] = $_FILES['tx_f3manager_pi1']['tmp_name']['folder']['file'][$key];
                        $fileData['size']     = $_FILES['tx_f3manager_pi1']['size']['folder']['file'][$key];
                        $fileData['error']    = $_FILES['tx_f3manager_pi1']['error']['folder']['file'][$key];


                        switch ($fileData['error']) {
                            case UPLOAD_ERR_OK:
                                break;
                            case UPLOAD_ERR_NO_FILE:
                                throw new RuntimeException('No file sent.');
                            case UPLOAD_ERR_INI_SIZE:
                            case UPLOAD_ERR_FORM_SIZE:
                                throw new RuntimeException('Exceeded filesize limit.');
                            default:
                                throw new RuntimeException('Unknown errors.');
                        }

                        // You should also check filesize here (1GB)
                        $maxZipFileSizeInByte = $this->toFloat($this->extConfig['maxFileUploadSize'])*1048576;
                        if ($fileData['size'] > $maxZipFileSizeInByte) {
                            throw new RuntimeException('Exceeded filesize limit of '. $this->extConfig['maxFileUploadSize'] . 'MB.');
                        }

                        if($this->CheckPermissions->fileMatchesForbiddenFileType($fileData['name'])) {
                            throw new RuntimeException('Invalid file type.');
                        }

                        $newFileObjects[$key] = $folder->addFile($fileData['tmp_name'], $fileData['name'], "changeName");
                    }

                    /*
                    // Dateiobjekt
                    $repositoryFileObject = $storage->getFile($newFileObject->getIdentifier());

                    #$newFileReference = $this->objectManager->get('TYPO3\CMS\Extbase\Domain\Model\FileReference');
                    #$newFileReference->setOriginalResource($repositoryFileObject);

                    return $newFileReference;
                    */
                }
            } else {
                $this->addFlashMessage('Not enough permissions to upload files into the folder "'.$folder->getIdentifier().'"', 'Permission denied', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
            }
            $this->redirect("openFolder",null,null,array('openFolder' => $folder->getIdentifier()));
        }

        $this->addFlashMessage("Please select a destination Folder", 'No Folder selected', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR, FALSE);
        $this->redirect("openFolder",null,null,array('openFolder' => $this->settings['folder']));
	}

    /**
     * action deleteFolder
     * Deletes an existing Folder recursively and redirects to the existing Folder when finished
     *
     * @argument String folder-Identifier that represents the folder inside the storage
     * @argument String files Comma-Separated List of File-IDs to download
     * @return void
     */
    public function downloadMultipleFilesAction() {
        if($this->request->hasArgument('folder')) {
            $folderIdentifier = $this->request->getArgument('folder');
            if($this->request->hasArgument('files')) {
                $filesToDownload = $this->request->getArgument('files');

                $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->settings['storage'] . ':' . $folderIdentifier);
                $fileObjectsToDownload = array();
                foreach(explode(",",$filesToDownload) as $fileToDownload) {
                    $fileObjectsToDownload[] = $this->resourceFactory->getFileObject((int)$fileToDownload);
                }

                /** @var \LumIT\F3manager\Service\ZipPackingService */
                $zipPackingService = new ZipPackingService($this->getFeUserGroups(),$folder, $fileObjectsToDownload);
                $zippedFile = $zipPackingService->getZip();


                $this->response->setHeader('Cache-control', 'public', TRUE);
                $this->response->setHeader('Content-Description', 'File transfer', TRUE);
                $this->response->setHeader('Content-Disposition', 'attachment; filename=' . $zipPackingService->getFileName(), TRUE);
                $this->response->setHeader('Content-Type', 'application/octet-stream', TRUE);
                $this->response->setHeader('Content-Transfer-Encoding', 'binary', TRUE);
                $this->response->sendHeaders();

                @readfile($zippedFile);
            }
        }

        exit();
    }



    /************************************************************************
     * AJAX ACTIONS                                                         *
     ************************************************************************/


	/**
	 * action deleteFiles
     *
	 * @return string
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
	 */
	public function deleteFilesAction() {
        if ($this->request->hasArgument('files')) {
            $userFeGroups = $this->getFeUserGroups();

            try{
                $files = json_decode($this->request->getArgument('files'));

                $deletedFiles = array();
                foreach($files as $fileUid) {
                    /** @var $file \TYPO3\CMS\Core\Resource\File */
                    $file = $this->resourceFactory->getFileObject($fileUid);
                    if ($this->CheckPermissions->checkFileChangeAccess($file, $userFeGroups)) {
                        $file->delete();
                        array_push($deletedFiles, $file->getUid());
                    }
                }
                return json_encode($deletedFiles);
            } catch(Exception $e) {
                return json_encode($e->getMessage());
            }
        }

        return json_encode(false);
	}



    /************************************************************************
     * PROTECTED FUNCTIONS                                                  *
     ************************************************************************/


    /**
     * Get a valid, sub-folder of the in the settings specified virtual root folder of a storage
     *
     * @param $combinedIdentifier
     * @return \TYPO3\CMS\Core\Resource\Folder
     * @throws \TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function getFolderObjectinsideVirtualRootFromCombinedIdentifier($combinedIdentifier) {
        try {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
        } catch(\TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException $exception) {
            throw $exception;
        }

        $vroot = $this->settings['folder'];
        if(!(substr($folder->getIdentifier(),0,strlen($vroot)) == $vroot)) {
            throw new \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException('The requested Folder is not inside the specified Root:"'.$folder->getIdentifier().'"', 1425731341);
        }
        return $folder;
    }

    /**
     * Get all parent folders of the specified folder
     *
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     * @return array the parent folders until the virtual root
     */
    protected function getParentFoldersUntilVirtualRoot(\TYPO3\CMS\Core\Resource\FolderInterface $folder) {
        $folderIdentifiers = array();
        $path = explode('/', $folder->getIdentifier());
        $last = end(array_keys($path));
        foreach ($path AS $x => $crumb) {
            if($x != $last) {
                $folderIdentifiers[] = end($folderIdentifiers).$crumb."/";
            }
        }
        $folders = array();

        foreach($folderIdentifiers as $x => $folderIdentifier) {
            try {
                $folders[] = $this->getFolderObjectInsideVirtualRootFromCombinedIdentifier($this->settings['storage'].':'.$folderIdentifier);
            } catch(\TYPO3\CMS\Core\Exception $e) {}
        }

        return $folders;
    }

    /**
     * Determines whether the currently logged in FE user belongs to the specified usergroup
     *
     * @return boolean|array FALSE when not logged in or else $GLOBALS['TSFE']->fe_user->groupData['uid']
     */
    protected function getFeUserGroups() {
        //TODO: Code Reuse! reference to Method!
        if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE']->loginUser) {
            return FALSE;
        }
        return $GLOBALS['TSFE']->fe_user->groupData['uid'];
    }

    private function toFloat($num) {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
        );
    }

}