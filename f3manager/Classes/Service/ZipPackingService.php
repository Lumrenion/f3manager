<?php
namespace LumIT\F3manager\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Philipp Seßner <philipp.sessner@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
use TYPO3\CMS\Core\FormProtection\Exception;
use ZipArchive;

/**
 * FileTreeController
 *
 * @package LumIT\F3manager\Service
 */
class ZipPackingService {

    protected $FeUserGroups;
    /** @var $rootFolder \TYPO3\CMS\Core\Resource\Folder */
    protected $rootFolder;
    protected $finishedZipFile;
    /** @var $checkPermissionsService \LumIT\F3manager\Security\CheckPermissions */
    protected $checkPermissionsService;
    /** @var ZipArchive */
    protected $zipArchive;
    protected $listOfFiles;


    /**
     * @param $FeUserGroups The User-Groups of the User
     * @param $downloadFolder
     */
    function __construct($FeUserGroups, $downloadFolder, $listOfFiles=array()) {
        $this->FeUserGroups = $FeUserGroups;
        $this->rootFolder = $downloadFolder;
        $this->listOfFiles = $listOfFiles;
        $this->checkPermissionsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('LumIT\\F3manager\\Security\\CheckPermissions');
    }

    /**
     * returns the ZIP-File containing the folder with all by the FE-User accessible Files and SubFolders
     *
     * @return string Path to the finished ZIP-File
     */
    public function getZip() {
        $this->finishedZipFile = tempnam(sys_get_temp_dir(), 'f3man');
        $this->zipArchive = new ZipArchive();
        $zipOpenCode = $this->zipArchive->open($this->finishedZipFile, ZipArchive::CREATE);
        if ($zipOpenCode !== TRUE) throw new Exception('Unable to create a temp file for zip creation.(' . $zipOpenCode . ')', 1367131215);

        if ($this->checkPermissionsService->checkFolderRootLineAccess($this->rootFolder, $this->FeUserGroups)) {

            if(empty($this->listOfFiles)) {
                $this->packFilesAndSubFolders($this->rootFolder, "");
            } else {
                $this->packFiles();
            }

        }

        if ($this->zipArchive->close() !== TRUE) throw new Exception('Unable to generate the zip file', 1367131456);

        return $this->finishedZipFile;
    }

    /**
     * returns the file name of newly created ZIP-File
     *
     * @return string
     */
    public function getFileName() {
        $name = $this->rootFolder->getName();
        if($name == "") {
            $name = "Ablage";
        }
        return $name."-".date("Y-m-d").".zip";
    }

    /**
     * Packs the folder and it's SubFolders into a ZIP
     *
     * @param $folder \TYPO3\CMS\Core\Resource\Folder the requested folder to pack
     * @param $folderLocalPath string for recursion, the path inside the requested folder
     */
    private function packFilesAndSubFolders($folder, $folderLocalPath) {
        $this->addFilesToZip($folder->getFiles(), $folderLocalPath);

        foreach($folder->getSubfolders() as $subFolder) {
            if($this->checkPermissionsService->checkFolderRootLineAccess($subFolder,$this->FeUserGroups)) {
                $this->packFilesAndSubFolders($subFolder,$folderLocalPath.$subFolder->getName()."/");
            }
        }
    }

    /**
     * Packs the listOfFiles into the ZIP
     */
    private function packFiles() {
        $this->addFilesToZip($this->listOfFiles);
    }

    /**
     * @param $arrayOfFiles
     * @param $filesPrefix string a prefix for all files (for example a local folder path)
     */
    private function addFilesToZip($arrayOfFiles, $filesPrefix="") {
        /** @var $file \TYPO3\CMS\Core\Resource\File */
        foreach($arrayOfFiles as $file) {
            if($this->checkPermissionsService->checkFileAccess($file,$this->FeUserGroups)) {
                //$absFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file->getStorage()->getConfiguration()["basePath"].$file->getIdentifier());
                $absFileName = $file->getForLocalProcessing();
                $this->zipArchive->addFile($absFileName, $filesPrefix.$file->getName());
            }
        }
    }
}

?>