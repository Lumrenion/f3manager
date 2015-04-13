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
use SplFileInfo;

/**
 * FileTreeController
 *
 * @package LumIT\F3manager\Service
 */
class ZipUnPackingService {

    /** @var $rootFolder \TYPO3\CMS\Core\Resource\Folder */
    protected $destinationFolder;
    protected $zipFileData;
    protected $tempDir;
    protected $forbiddenFileTypes = array("htaccess");
    /** @var $zipArchive ZipArchive */
    protected $zipArchive;
    /** @var $securityService \LumIT\F3manager\Security\CheckPermissions */
    protected $securityService;




    /**
     * Returns the Leaf-Folder where to add the File to
     * Creates folders if necessary
     *
     * @param $explodedFolderPath
     * @param $folderToMoveDeeper \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getFinalFolderInRelPath($explodedFolderPath, $folderToMoveDeeper) {
        $firstNode = array_shift($explodedFolderPath);
        if(!empty($firstNode)) {
            if(!($folderToMoveDeeper->hasFolder($firstNode))) {
                $folderToMoveDeeper->createFolder($firstNode);
            }

            return $this->getFinalFolderInRelPath($explodedFolderPath, $folderToMoveDeeper->getSubfolder($firstNode));
        } else {
            return $folderToMoveDeeper;
        }

    }



    function __construct($destinationFolder, $zipFileData) {
        $this->securityService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('LumIT\\F3manager\\Security\\CheckPermissions');
        $this->destinationFolder = $destinationFolder;
        $this->zipFileData = $zipFileData;
    }

    /**
     * Extracts the given ZIP-File to a random temporary directory
     */
    protected function unzipArchive() {
        $zip = new ZipArchive;
        if ($zip->open($this->zipFileData["tmp_name"]) === TRUE) {
            $zip->extractTo($this->tempDir);
            $zip->close();
        } else {
            throw new Exception('Error while trying to extract a zip archive using the PHP module ZipArchive', 1294159795);
        }
    }

    /**
     * Returns a List of Files in the directory
     * Each String consists of the absolute Path and the file name of the file
     *
     * @param $directory String The Directory where to search for files in
     * @param array $entries Array of Strings which consists of files of possible previous iterations (for recursion)
     * @return array all files in the directory
     */
    protected function getFileListOfExtractedFiles($directory, &$entries=array()) {
        $dirHandle = opendir($directory);

        if (!$dirHandle) throw new Exception('Directory ' . $directory . ' could not be opened', 1287246092);

        while(($dirEntry = readdir($dirHandle)) != FALSE) {
            if (!($dirEntry == '.' || $dirEntry == '..')) {
                if (!is_dir($directory.$dirEntry)) {
                    if (!($this->securityService->fileMatchesForbiddenFileType($dirEntry))) {
                        $entries[] = $directory . $dirEntry;
                    }
                } elseif (is_dir($directory.$dirEntry)) {
                    $this->getFileListOfExtractedFiles($directory.$dirEntry."/", $entries);
                }
            }
        }
        closedir($dirHandle);
        return $entries;
    }

    /**
     * Adds all files in the $files-Array ti the destinationFolder recursively
     *
     * @param $files Array of Strings that consists of the absolute path and file name of the files to add
     */
    protected function addFilesToDirRecursively($files) {

        foreach($files as $file) {
            $fileRelPath = $file;
            if (substr($file, 0, strlen($this->tempDir)) == $this->tempDir) {
                $fileRelPath = substr($file, strlen($this->tempDir));
            }
            $relFilePath = explode("/", $fileRelPath);
            $fileName = array_pop($relFilePath);

            /** @var $finalDestinationForFile \TYPO3\CMS\Core\Resource\Folder */
            $finalDestinationForFile = $this->getFinalFolderInRelPath($relFilePath, $this->destinationFolder);

            $finalDestinationForFile->addFile($file, $fileName, "changeName");
        }
    }

    /**
     * Run the whole Extraction Process
     */
    public function run() {
        $temp = tempnam(sys_get_temp_dir(), 'f3man');
        if(file_exists($temp)) { unlink($temp); }
        mkdir($temp);
        $this->tempDir = $temp."/";
        if (is_dir($this->tempDir)) {
            $this->unzipArchive();
            $fileList = $this->getFileListOfExtractedFiles($this->tempDir);

            $this->addFilesToDirRecursively($fileList);
        } else {
            throw new Exception('Error while trying to create a new temporary folder', 1427924316);
        }
    }
}
?>