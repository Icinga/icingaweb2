// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

define(['jquery'], function($) {
    "use strict";

    var ATTR_MODIFIED = 'data-icinga-form-modified';

    var isAutoSubmitInput = function(el) {
        return $(el).attr('data-icinga-autosubmit') == 'true';
    }

    var getFormObject = function(targetForm) {
        var form = $(targetForm);

        form.isModified = function() {
            return form.attr(ATTR_MODIFIED) == 'true';
        }
        form.setModificationFlag = function() {
            form.attr(ATTR_MODIFIED, true);
        }
        form.clearModificationFlag = function() {
            form.attr(ATTR_MODIFIED, false);
        }
        return form;
    }

    var registerChangeDetection = function(form) {
        form.change(function(changed) {
            if (isAutoSubmitInput(changed.target)) {
                form.clearModificationFlag();
                form.submit();
            } else {
                form.setModificationFlag();
            }
        });

        form.submit(form.clearModificationFlag);
        window.addEventListener('beforeunload', function() {
            if (form.isModified()) {
                return 'All unsaved changes will be lost when leaving this page';
            }
        })
    }

    return function(targetForm) {
        var form = getFormObject(targetForm);
        registerChangeDetection(form);
    }
});