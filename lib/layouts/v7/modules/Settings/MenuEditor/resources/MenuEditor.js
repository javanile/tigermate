/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
jQuery.Class('Settings_Menu_Editor_Js', {}, {

	getContainer : function() {
		return jQuery('#listViewContent');
	},

	registerAddActions : function(container) {
		var thisInstance = this;
		container.off('click.menuEditorAddActions', '.menuEditorAddItem');
		container.on('click.menuEditorAddActions', '.menuEditorAddItem', function(e) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			var element = jQuery(e.currentTarget);
			var params;
			var callback;

			if (element.hasClass('menuEditorCustomLinkAddItem')) {
				params = {
					module: app.getModuleName(),
					parent: app.getParentModuleName(),
					view: 'EditAjax',
					mode: 'showCustomLinkForm',
					appname: element.data('appname'),
					formMode: 'create'
				};
				callback = function(modal) {
					thisInstance.registerCustomLinkFormEvents(modal);
				};
			} else {
				params = {
					module: app.getModuleName(),
					parent: app.getParentModuleName(),
					view: 'EditAjax',
					mode: 'showAddModule',
					appname: element.data('appname')
				};
				callback = function(modal) {
					thisInstance.registerAddModulePreSaveEvents(modal);
				};
			}

			app.helper.showProgress();
			app.request.post({data: params}).then(function(err, data) {
				app.helper.hideProgress();
				app.helper.showModal(data, {cb: function(modal) {
					callback(modal);
				}});
			});
		});
	},

	registerCustomLinkFormEvents : function(modal) {
		modal.find('[name="saveButton"]').on('click', function(e) {
			e.preventDefault();
			var container = modal.find('.customLinkFormContainer');
			var formMode = container.find('[name="formMode"]').val();
			var label = jQuery.trim(container.find('[name="label"]').val());
			var linkUrl = jQuery.trim(container.find('[name="linkurl"]').val());
			if (!label || !linkUrl) {
				app.helper.showErrorNotification({message: app.vtranslate('JS_CUSTOM_LINK_FIELDS_REQUIRED')});
				return false;
			}

			var params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				action: 'SaveAjax',
				mode: (formMode === 'edit') ? 'updateCustomLink' : 'addCustomLink',
				appname: container.find('[name="appname"]').val(),
				label: label,
				linkurl: linkUrl
			};
			if (formMode === 'edit') {
				params.customLinkId = container.find('[name="customLinkId"]').val();
			}

			app.helper.showProgress();
			app.request.post({data: params}).then(function(err, data) {
				app.helper.hideProgress();
				if (err === null) {
					app.helper.showSuccessNotification({
						message: app.vtranslate((formMode === 'edit') ? 'JS_CUSTOM_LINK_UPDATED_SUCCESS' : 'JS_CUSTOM_LINK_ADDED_SUCCESS')
					});
					app.helper.hideModal();
					window.location.reload();
				}
			});
		});
	},

	registerEditCustomLink : function(container) {
		var thisInstance = this;
		container.off('click.menuEditorEditCustomLink', '.menuEditorEditCustomLink');
		container.on('click', '.menuEditorEditCustomLink', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var entry = jQuery(e.currentTarget).closest('.menuEditorEntry');
			var params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				view: 'EditAjax',
				mode: 'showCustomLinkForm',
				appname: entry.closest('.appContainer').data('appname'),
				formMode: 'edit',
				customLinkId: entry.data('custom-link-id'),
				label: entry.find('.customLinkLabelValue').val(),
				linkurl: entry.find('.customLinkUrlValue').val()
			};
			app.helper.showProgress();
			app.request.post({data: params}).then(function(err, data) {
				app.helper.hideProgress();
				app.helper.showModal(data, {cb: function(modal) {
					thisInstance.registerCustomLinkFormEvents(modal);
				}});
			});
		});
	},

	setSaveButtonState : function(container) {
		var appname = container.find('#appname').val();
		if(!container.find('.modulesContainer[data-appname='+appname+']').find('.addModule').length) {
			container.find('[type="submit"]').attr('disabled','disabled');
		} else {
			container.find('[type="submit"]').removeAttr('disabled');
		}
	},

	registerAddModulePreSaveEvents : function(data) {
		var self = this;
		var container = data.find('.addModuleContainer');

		container.on('click', '.addModule', function(e){
			var element = jQuery(e.currentTarget);
			element.toggleClass('selectedModule');
		});

		container.on('click', '.moduleSelection li a', function(){
			var selText = $(this).text();
			var appname = $(this).data('appname');
			$(this).parents('.btn-group').find('.dropdown-toggle').html(selText+'&nbsp;&nbsp; <span class="caret"></span>');
			container.find('.modulesContainer').addClass('hide');
			container.find('.modulesContainer[data-appname='+appname+']').removeClass('hide')
			.find('.addModule').removeClass('selectedModule');
			container.find('#appname').val(appname);
			self.setSaveButtonState(container);
		});

		self.setSaveButtonState(container);

		container.find('[type="submit"]').on('click', function(e) {
			var modulesContainer = container.find('.modulesContainer').not('.hide');
			var modules = modulesContainer.find('.addModule');
			var selectedModules = modules.filter('.selectedModule');
			if(!selectedModules.length) {
				app.helper.showAlertNotification({
					'message' : app.vtranslate('JS_PLEASE_SELECT_A_MODULE')
				});
			} else {
				jQuery(this).attr('disabled','disabled');
				var appname = container.find('#appname').val();
				var sourceModules = [];
				selectedModules.each(function(i, element) {
					var selectedModule = jQuery(element);
					sourceModules.push(selectedModule.data('module'));
				});

				if(sourceModules.length) {
					var params = {
						module: app.getModuleName(),
						parent: app.getParentModuleName(), 
						sourceModules: sourceModules,
						appname: appname,
						action: 'SaveAjax',
						mode: 'addModule'
					};
					app.helper.showProgress();
					app.request.post({data: params}).then(function(err, data) {
						app.helper.showSuccessNotification({message: app.vtranslate('JS_MODULE_ADD_SUCCESS')});
						app.helper.hideProgress();
						window.location.reload();
					});

					app.helper.hideModal();
				}
			}  
		});
	},

	registerRemoveModule : function(container) {
		container.off('click.menuEditorRemoveItem', '.menuEditorRemoveItem');
		container.on('click', '.menuEditorRemoveItem', function(e) {
			var element = jQuery(e.currentTarget);
			var parent = element.closest('.modules');
			var isCustomLink = parent.data('entry-type') === 'customLink';
			var params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				action: 'SaveAjax',
				mode: isCustomLink ? 'removeCustomLink' : 'removeModule',
				appname: parent.closest('.appContainer').data('appname')
			}
			if (isCustomLink) {
				params.customLinkId = parent.data('custom-link-id');
			} else {
				params.sourceModule = parent.data('module');
			}

			app.helper.showProgress();
			app.request.post({data: params}).then(function(err, data){
				app.helper.hideProgress();
				element.closest('.modules').fadeOut(500, function(){ 
					app.helper.showSuccessNotification({message: app.vtranslate('JS_MODULE_REMOVED')});
					jQuery(this).remove(); 
				});
			});
		});
	},

	registerSortModule : function(container) {
		var sortableElement = container.find('.sortable');
		var thisInstance = this;
		var stopSorting = false;
		var move = false;
		sortableElement.sortable({
			items: '.menuEditorEntry',
			'revert' : true,
			receive: function (event, ui) {
				move = true;
				if (jQuery(ui.item).hasClass("noConnect")) {
					stopSorting = true;
					jQuery(ui.sender).sortable("cancel");
				}
			},
			over : function(event, ui){
				stopSorting = false;
			},
			stop: function(e, ui) {
				var element = jQuery(ui.item);
				var parent = element.closest('.sortable');
				parent.find('.menuEditorAddItem, .menuEditorCustomLinkAddItem').appendTo(parent);
				var appname = parent.data('appname');
				var moduleSequenceArray = {};
				var customLinkSequenceArray = {};
				jQuery.each(parent.find('.menuEditorEntry'), function(i, element) {
					var currentElement = jQuery(element);
					var sequence = i + 1;
					if (currentElement.data('entry-type') === 'customLink') {
						customLinkSequenceArray[currentElement.data('custom-link-id')] = sequence;
					} else {
						moduleSequenceArray[currentElement.data('module')] = sequence;
					}
				});
				var moved = move;
				if(move) {
					move = false;
				}
				if(!stopSorting) {
					thisInstance.saveSequence(moduleSequenceArray, customLinkSequenceArray, appname, moved);
				} else {
					if(!element.hasClass('noConnect')) {
						thisInstance.saveSequence(moduleSequenceArray, customLinkSequenceArray, appname);
					} else {
						app.helper.showErrorNotification({message: app.vtranslate('JS_MODULE_NOT_DRAGGABLE')});
					}
				}
			}
		});
		sortableElement.disableSelection();
	},

	saveSequence : function(moduleSequenceArray, customLinkSequenceArray, appname, move) {
		var params = {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			action: 'SaveAjax',
			mode: 'saveSequence',
			sequence: JSON.stringify(moduleSequenceArray),
			customLinkSequence: JSON.stringify(customLinkSequenceArray),
			appname: appname
		}

		app.helper.showProgress();
		app.request.post({data: params}).then(function(err, data){
			if(move) {
				app.helper.showSuccessNotification({message: app.vtranslate('JS_MODULE_MOVED_SUCCESSFULLY')});
			} else {
				app.helper.showSuccessNotification({message: app.vtranslate('JS_MODULE_SEQUENCE_SAVED')})
			}
			app.helper.hideProgress();
			app.event.trigger('POST.MENU.MOVE', params);
		});
	},

	registerEvents : function() {
		var container = this.getContainer();
		this.registerAddActions(container);
		this.registerEditCustomLink(container);
		this.registerRemoveModule(container);
		this.registerSortModule(container);
		var instance = new Settings_Vtiger_Index_Js();
		instance.registerBasicSettingsEvents();
	}
});

window.onload = function() {
	var settingMenuEditorInstance = new Settings_Menu_Editor_Js();
	settingMenuEditorInstance.registerEvents();
};
