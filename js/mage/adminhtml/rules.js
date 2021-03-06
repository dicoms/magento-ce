/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

var VarienRulesForm = new Class.create();
VarienRulesForm.prototype = {
    initialize : function(parent, newChildUrl){
        this.newChildUrl  = newChildUrl;
        this.shownElement = null;
        this.updateElement = null;
        this.chooserSelectedItems = $H({});

        var elems = $(parent).getElementsByClassName('rule-param');

    	for (var i=0; i<elems.length; i++) {
            this.initParam(elems[i]);
        }
    },

    initParam: function (container) {
    	container.rulesObject = this;

        var label = Element.down(container, '.label');
        if (label) {
		    Event.observe(label, 'click', this.showParamInputField.bind(this, container));
        }

		var elem = Element.down(container, '.element');
		if (elem) {
		    var trig = elem.down('.rule-chooser-trigger');
		    if (trig) {
                Event.observe(trig, 'click', this.toggleChooser.bind(this, container));
		    }

		    var apply = elem.down('.rule-param-apply');
		    if (apply) {
		        Event.observe(apply, 'click', this.hideParamInputField.bind(this, container));
		    } else {
    		    elem = elem.down();
    		    Event.observe(elem, 'change', this.hideParamInputField.bind(this, container));
    		    Event.observe(elem, 'blur', this.hideParamInputField.bind(this, container));
		    }
		}

		var remove = Element.down(container, '.rule-param-remove');
		if (remove) {
		    Event.observe(remove, 'click', this.removeRuleEntry.bind(this, container));
		}
    },

    showChooserElement: function (chooser) {
	    this.chooserSelectedItems = $H({});
	    var values = this.updateElement.value.split(','), s='';
	    for (i=0; i<values.length; i++) {
	        s = values[i].strip();
	        if (s!='') {
	           this.chooserSelectedItems[s] = 1;
	        }
	    }
	    new Ajax.Updater(chooser, chooser.getAttribute('url'), {
            evalScripts: true,
            parameters: { 'selected[]':this.chooserSelectedItems.keys() },
            onSuccess: this._processSuccess.bind(this) && this.showChooserLoaded.bind(this, chooser),
            onFailure: this._processFailure.bind(this)
	    });
    },

    showChooserLoaded: function(chooser, transport) {
        chooser.style.display = 'block';
    },

    showChooser: function (container, event) {
    	var chooser = container.up('li').down('.rule-chooser');
    	if (!chooser) {
    	    return;
    	}

        this.showChooserElement(chooser);
    },

    hideChooser: function (container, event) {
    	var chooser = container.up('li').down('.rule-chooser');
    	if (!chooser) {
    	    return;
    	}
        chooser.style.display = 'none';
    },

    toggleChooser: function (container, event) {
    	var chooser = container.up('li').down('.rule-chooser');
    	if (!chooser) {
    	    return;
    	}
    	if (chooser.style.display=='block') {
    	    chooser.style.display = 'none';
    	    this.cleanChooser(container, event);
    	} else {
    	    this.showChooserElement(chooser);
    	}
    },

    cleanChooser: function (container, event) {
    	var chooser = container.up('li').down('.rule-chooser');
    	if (!chooser) {
    	    return;
    	}
    	chooser.innerHTML = '';
    },

    showParamInputField: function (container, event) {
        if (this.shownElement) {
            this.hideParamInputField(this.shownElement, event);
        }

    	Element.addClassName(container, 'rule-param-edit');
    	var elemContainer = Element.down(container, '.element');

    	var elem = Element.down(elemContainer, 'input.input-text');
    	if (elem) {
    	    elem.focus();
        	if (elem && elem.id && elem.id.match(/:value$/)) {
        	    this.updateElement = elem;
                //this.showChooser(container, event);
        	}

    	}

    	var elem = Element.down(elemContainer, 'select');
    	if (elem) {
    	   elem.focus();
    	   // trying to emulate enter to open dropdown
//    	   if (document.createEventObject) {
//        	   var event = document.createEventObject();
//        	   event.altKey = true;
//    	       event.keyCode = 40;
//    	       elem.fireEvent("onkeydown", evt);
//    	   } else {
//    	       var event = document.createEvent("Events");
//    	       event.altKey = true;
//    	       event.keyCode = 40;
//    	       elem.dispatchEvent(event);
//    	   }
    	}

    	this.shownElement = container;
    },

    hideParamInputField: function (container, event) {
    	Element.removeClassName(container, 'rule-param-edit');
    	var label = Element.down(container, '.label'), elem;

    	if (!container.hasClassName('rule-param-new-child')) {
        	elem = Element.down(container, 'select');
        	if (elem && elem.selectedIndex>=0) {
        	    var str = elem.options[elem.selectedIndex].text;
        		label.innerHTML = str!='' ? str : '...';
        	}

        	elem = Element.down(container, 'input.input-text');
        	if (elem) {
        	    var str = elem.value.replace(/(^\s+|\s+$)/g, '');
        	    elem.value = str;
        	    if (str=='') {
        	        str = '...';
        	    } else if (str.length>30) {
        	        str = str.substr(0, 30)+'...';
        	    }
        		label.innerHTML = str;
        	}
    	} else {
    	    elem = Element.down(container, 'select');

    	    if (elem.value) {
    	        this.addRuleNewChild(elem);
    	    }

        	elem.value = '';
    	}

    	if (elem && elem.id && elem.id.match(/:value$/)) {
            this.hideChooser(container, event);
            this.updateElement = null;
    	}

    	this.shownElement = null;
    },

    addRuleNewChild: function (elem) {
        var parent_id = elem.id.replace(/^.*:(.*):.*$/, '$1');
        var children_ul = $(elem.id.replace(/[^:]*$/, 'children'));
        var max_id = 0, i;
        var children_inputs = Selector.findChildElements(children_ul, $A(['input[type=hidden]']));
        if (children_inputs.length) {
            children_inputs.each(function(el){
                if (el.id.match(/:type$/)) {
                    i = 1*el.id.replace(/^.*:.*([0-9]+):.*$/, '$1');
                    max_id = i > max_id ? i : max_id;
                }
            });
        }
        var new_id = parent_id+'.'+(max_id+1);
        var new_type = elem.value;
        var new_elem = document.createElement('LI');
        new_elem.className = 'rule-param-wait';
        new_elem.innerHTML = Translator.translate('Please wait, loading...');
        children_ul.insertBefore(new_elem, $(elem).up('li'));

        new Ajax.Updater(new_elem, this.newChildUrl, {
            evalScripts: true,
            parameters: { type:new_type.replace('/','-'), id:new_id },
            onComplete: this.onAddNewChildComplete.bind(this, new_elem),
            onSuccess: this._processSuccess.bind(this),
            onFailure: this._processFailure.bind(this)
        });
    },

    _processSuccess : function(transport) {
        var response = transport.responseText.evalJSON();
        if (response.ajaxExpired && response.ajaxRedirect) {
            alert(Translator.translate('Your session has been expired, you will be relogged in now.'));
            location.href = response.ajaxRedirect;
        }
        return true;
    },

    _processFailure : function(transport) {
        location.href = BASE_URL;
    },

    onAddNewChildComplete: function (new_elem) {
        $(new_elem).removeClassName('rule-param-wait');
        var elems = new_elem.getElementsByClassName('rule-param');
        for (var i=0; i<elems.length; i++) {
            this.initParam(elems[i]);
        }
    },

    removeRuleEntry: function (container, event) {
        var li = Element.up(container, 'li');
        li.parentNode.removeChild(li);
    },

    chooserGridInit: function (grid) {
        //grid.reloadParams = {'selected[]':this.chooserSelectedItems.keys()};
    },

    chooserGridRowInit: function (grid, row) {
        if (!grid.reloadParams) {
            grid.reloadParams = {'selected[]':this.chooserSelectedItems.keys()};
        }
    },

    chooserGridRowClick: function (grid, event) {
        var trElement = Event.findElement(event, 'tr');
        var isInput = Event.element(event).tagName == 'INPUT';
        if (trElement) {
            var checkbox = Element.getElementsBySelector(trElement, 'input');
            if (checkbox[0]) {
                var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                grid.setCheckboxChecked(checkbox[0], checked);

            }
        }
    },

    chooserGridCheckboxCheck: function (grid, element, checked) {
        if (checked) {
            if (!element.up('th')) {
                this.chooserSelectedItems[element.value]=1;
            }
        } else {
            this.chooserSelectedItems.remove(element.value);
        }
        grid.reloadParams = {'selected[]':this.chooserSelectedItems.keys()};
        this.updateElement.value = this.chooserSelectedItems.keys().join(', ');
    }
}
