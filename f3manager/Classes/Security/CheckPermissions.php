<?php
namespace LumIT\F3manager\Security;

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

use SplFileInfo;

/**
 * Utility functions to check permissions
 *
 * @package LumIT\F3manager\Security
 */
class CheckPermissions extends \BeechIt\FalSecuredownload\Security\CheckPermissions {
    /**
     * @var array check folder write permission cache
     */
    protected $checkFolderWritePermissionCache = array();

    protected $forbiddenFileTypes = array("htaccess");


    /**
     * Check file change access for given FeGroups combination
     *
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @param bool|array $userFeGroups FALSE = no login, array() fe groups of user
     * @return bool
     */
    public function checkFileChangeAccess($file, $userFeGroups) {

        if ($this->checkFolderWritePermission($file->getParentFolder(), $userFeGroups)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Check if given FeGroups have enough rights to write to given folder
     *
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     * @param bool|array $userFeGroups FALSE = no login, array() is the groups of the user
     * @return bool
     */
    public function checkFolderWritePermission($folder, $userFeGroups) {
        $cacheIdentifier = sha1(
            $folder->getHashedIdentifier() .
            serialize($userFeGroups)
        );

        if (!isset($this->checkFolderWritePermissionCache[$cacheIdentifier])) {
            $this->checkFolderWritePermissionCache[$cacheIdentifier] = TRUE;

            // fetch folder permissions record
            $folderRecord = $this->utilityService->getFolderRecord($folder);
            if ($folderRecord) {
                if (!$this->matchFeGroupsWithFeUser($folderRecord['fe_groups_write'], $userFeGroups)) {
                    $this->checkFolderWritePermissionCache[$cacheIdentifier] = FALSE;
                }
            }
        }
        return $this->checkFolderWritePermissionCache[$cacheIdentifier];
    }

    /**
     * Check if the file extension of the given file is in the list of forbidden file types
     *
     * @param String $fileName
     * @return bool
     */
    public function fileMatchesForbiddenFileType($fileName) {
        $extension = new SplFileInfo($fileName);

        if(in_array($extension->getExtension(), $this->forbiddenFileTypes)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}