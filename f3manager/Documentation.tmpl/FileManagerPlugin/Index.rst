.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _file-tree-plugin:

FE File Manager
============

The f3manager offers a complete frontend interface for the file manager, which was the purpose of the extension.
For using the file manager plugin, include

    jQuery
    jQuery UI (JS is enough, css is not required)
    Bootstrap CSS and JS



Functionality
-------------

In the frontend the users can
    - Create folders
    - rename folders
    - upload and extract ZIP-Files
    - download entire folders as ZIP
    - delete folders

    - rename files
    - upload single and multiple files at once
    - download single and multiple selected files
    - delete selected files

Of course the user can browse through folders and download single files by double clicking


Templates
---------

The default templates can be found like any other extbase/fluid based extension in:

.. code-block:: ts

   f3manager/Resources/Private/Layouts
   f3manager/Resources/Private/Partials
   f3manager/Resources/Private/Templates

If you want to override these you can set

.. code-block:: ts

   plugin.tx_f3manager {
    view {
     layoutRootPaths = path/to/your/layouts/folder
     partialRootPaths = path/to/your/partials/folder
     templateRootPaths = path/to/your/templates/folder
    }