$(document).ready(function () {
    $('[data-toggle="tooltip"]').each(function () {
        if (this.offsetWidth < this.scrollWidth) {
            $(this).tooltip({});
        } else {
            $(this).attr("title", "");
        }
    });

    $(document).click(function(event) {
            $(".ui-selected").removeClass("ui-selected");
    });


    if(!Modernizr.touch) {
        $(".f3manager_folders,.f3manager_files")
            .click(function () {
                $(".ui-selected").not($(this).find('.ui-selected')).removeClass("ui-selected");
                $('.dropdown.open .dropdown-toggle').dropdown('toggle');
            })
            .selectable()
            .each(function () {
                var _mouseStart = $(this).data('ui-selectable')['_mouseStart'];
                $(this).data('ui-selectable')['_mouseStart'] = function (e) {
                    _mouseStart.call(this, e);
                    this.helper.css({
                        "top": -1,
                        "left": -1
                    });
                };
            })
            .children(".ui-selectee")
            .each(function () {
                $(this).attr("data-href", $(this).attr("href")).removeAttr("href");
            })
            .bind("dblclick", function (e) {
                if (!(e.ctrlKey)) {
                    window.location = $(this).data("href");
                }
            });
    }

    $renameFilesModal = $("#renameFilesModal");
    $renameFileInputField = $renameFilesModal.find(".renameFileInputField").first();
    renameFileInputName = $renameFileInputField.attr("name");
    $renameFileIdField = $renameFilesModal.find(".renameFileIdField").first();
    renameFileIdName = $renameFileIdField.attr("name");
    $(".f3manager_files_rename_button").click(function(e) {
        e.preventDefault();
        var renameFilesInput = [];
        $(".f3manager_files")
            .children(".ui-selected")
            .each(function() {
                inputName = renameFileInputName.replace("f3manager_file_id_for_file_to_rename", $(this).data("fileId"));
                renameFilesInput.push("<div class='input-group'><label class='input-group-addon' for='"+inputName+"' form='renameFilesForm'>"+$(this).data("fileName")+": </label><input type='text' class='form-control' name='"+inputName+"' id='"+inputName+"' value='"+$(this).data("fileName")+"' /></div>");
            });

        $(".renameFileInputFields").html(renameFilesInput.join(""));
    });

    $(".f3manager_files_download_button").click(function(e) {
        e.preventDefault();
        var filesarray = [];
        $(".f3manager_files")
            .children(".ui-selected")
            .each(function() {
                filesarray.push($(this).data("fileId"));
            });

        window.location.href = $(this).attr("href")+"&tx_f3manager_pi1[files]="+filesarray.join();
    });

    $('.f3manager_delete_files').click(function(e) {
        e.preventDefault();
        var filesarray = [];
        $(".f3manager_files")
            .children(".ui-selected")
                .each(function() {
                    filesarray.push($(this).data("fileId"));
                });

        var filesjson = JSON.stringify(filesarray);
        $.ajax({
            async: 'true',
            url: 'index.php',
            type: 'POST',
            data: {
                eID: "f3manager",
                request: {
                    pluginName: 'pi1',
                    controller: 'FileManager',
                    action: 'deleteFiles',
                    arguments: {
                        'files': filesjson
                    },
                    dataType: 'json'
                }
            },
            //dataType: "xml",
            success: function (result) {
                if(result) {
                    console.log($.parseJSON(result));
                    $.each($.parseJSON(result), function() {
                        $(".f3manager_file[data-file-id="+this+"]").fadeOut(400,function() {$(this).remove()});
                    });
                    //$(".f3manager_files").find(".ui-selected").remove();
                } else {
                    //False was returned from PHP
                }
            },
            error: function (error) {
                console.log(error["responseText"]);
                $("footer").html(error["responseText"]);
            }
        });
    });

/*
    var selectedClass = 'ui-selected',
        files = 'f3manager_files',
        file = 'f3manager_file',
        trash = 'f3manager_trash',
        clickDelay = 600,
    // click time (milliseconds)
        lastClick, diffClick, // timestamps
        $files = $("."+files),
        $trash = $("."+trash);

    $("."+file, $files)
        .data("href", $(this).attr("href"))
        .removeAttr("href")
        // Script to deferentiate a click from a mousedown for drag event
        .bind('mousedown mouseup', function(e) {
            multiSelectBind(e, $(this));
        })
        .draggable({
            revert: 'invalid',
            containment: 'document',
            start: function(e, ui) {
                $trash.show();
                ui.helper.addClass(selectedClass);
            },

            stop: function(e, ui) {
                //$trash.hide();
                // reset group positions
                $('.' + selectedClass).css({
                    top: 0,
                    left: 0
                });
            },
            drag: function(e, ui) {

                // set selected group position to main dragged object
                // this works because the position is relative to the starting position
                $('.' + selectedClass).css({
                    top: ui.position.top,
                    left: ui.position.left
                });
            }
        });

    $files
        .selectable()
        .droppable({
            accept: '.'+trash+'.'+file,
            activeClass: "ui-state-highlight",
            drop: function(e, ui) {
                recycleFiles( $('.' + selectedClass) );
            }
        });

    $trash
        .selectable()
        .droppable({
            accept: '.'+files+' .'+file,
            activeClass: "ui-state-highlight",
            drop: function(e, ui) {
                deleteFiles( $('.' + selectedClass) );
            }
        });

    $(".testbutton").click(function() {
        $trash.find("div")
            .fadeOut(function() {
                console.log($(this));
                $(this).appendTo($files).fadeIn()
                    .bind('mousedown mouseup', function(e) {
                        multiSelectBind(e, $(this));
                    }).draggable();
            });
    });

    function recycleFiles( $items) {
        $items
            .fadeOut(function() {
                $items
                    .appendTo($files)
                    .removeClass(selectedClass)
                    .fadeIn();
            })
            .each(function() {
                $("#test").append($(this).data("test"));
            });
    }
    function deleteFiles( $items ) {
        // ui.draggable is appended by the script, so add it after
        $items
            .fadeOut(function() {
                $items
                    .appendTo($trash)
                    .removeClass(selectedClass)
                    .fadeIn();
            })
            .each(function() {
                $("#test").append($(this).data("test"));
            });
    }
    function deleteFilesReally( $items ) {

    }
    function multiSelectBind(e, $this) {
        if (e.type == "mousedown") {
            lastClick = e.timeStamp; // get mousedown time
        } else {
            diffClick = e.timeStamp - lastClick;
            if (diffClick < clickDelay) {
                if(!(e.ctrlKey && $this.siblings("."+selectedClass).length)) {
                    $('.'+selectedClass).removeClass(selectedClass);
                }
                // add selected class to group draggable objects
                $this.toggleClass(selectedClass);
            }
        }
    }
*/
});