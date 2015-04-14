.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _installation:

Installation
============

#. **Install ext:fal_securedownload**

   Download and install fal_securedownload through extension manager or clone from https://github.com/beechit/fal_securedownload.git in typo3conf/ext/

#. **Follow installation instructions of fal_securedownload**

   http://docs.typo3.org/typo3cms/extensions/fal_securedownload/Installation/Index.html

#. **Install ext:f3manager**

Download and install t3manager through extension manager

#. **Configure the extension**

   Set the maximum file upload size and the maximum zip-file upload size

   .. figure:: ../Images/configure-ext.png
      ::width:
      ::alt: Configuration in the Extension Manager

      **Image 2:** Configuration in the Extension Manager

#. **Configure the extension in the Filelist**

   In the Filelist where you can choose Permissions for Folders you will notice a new field to configure the write permissions

   The write permissions control if the User can upload files to a folder, create/rename/delete folders and rename/delete files inside the folders.

#. **Import Static Typoscript**

   Go to Web/Template in the Admin Panel, go to your Page in "Info/Modify"/"Edit the whole template record"/"Includes" add FAL Frontend File Manager (f3manager)

#. **Include the plugin**

    Choose the storage you created for fal_securedownload, select a folder in that storage und you are good to go.