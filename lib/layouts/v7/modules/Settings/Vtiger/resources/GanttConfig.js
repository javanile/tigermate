/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger.Class('Settings_Vtiger_GanttConfig_Js', {}, {
	init: function () {
		this.addComponents();
	},

	addComponents: function () {
		this.addModuleSpecificComponent('Index', app.getModuleName, app.getParentModuleName());
	},

	saveSettings: function (form) {
		var aDeferred = jQuery.Deferred();
		var data = form.serializeFormData();
		var updatedFields = {};

		jQuery.each(data, function (key, value) {
			updatedFields[key] = value;
		});

		app.request.post({
			data: {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				action: 'GanttConfigSaveAjax',
				updatedFields: JSON.stringify(updatedFields)
			}
		}).then(function (err, data) {
			if (err === null) {
				aDeferred.resolve(data);
			} else {
				aDeferred.reject(err);
			}
		}, function (error) {
			aDeferred.reject(error);
		});

		return aDeferred.promise();
	},

	loadContents: function (url) {
		var aDeferred = jQuery.Deferred();
		app.request.post({url: url}).then(function (err, data) {
			aDeferred.resolve(data);
		});
		return aDeferred.promise();
	},

	registerEditViewEvents: function () {
		var thisInstance = this;
		var form = jQuery('#GanttConfigForm');
		var detailUrl = form.data('detailUrl');

		form.vtValidate({
			submitHandler: function (rawForm) {
				var editForm = jQuery(rawForm);
				thisInstance.saveSettings(editForm).then(function () {
					thisInstance.loadContents(detailUrl).then(function (data) {
						jQuery('.settingsPageDiv').html(data);
						thisInstance.registerDetailViewEvents();
						app.helper.showSuccessNotification({message: app.vtranslate('JS_GANTT_CONFIGURATION_SAVED')});
					});
				});
			}
		});

		form.on('submit', function (e) {
			e.preventDefault();
			return false;
		});

		form.find('.cancelLink').off('click').on('click', function () {
			thisInstance.loadContents(detailUrl).then(function (data) {
				jQuery('.settingsPageDiv').html(data);
				thisInstance.registerDetailViewEvents();
			});
		});

		vtUtils.showSelect2ElementView(form.find('.select2-container'));
	},

	registerDetailViewEvents: function () {
		var thisInstance = this;
		var container = jQuery('#GanttConfigDetails');
		container.find('.editButton').off('click').on('click', function () {
			var url = jQuery(this).data('url');
			thisInstance.loadContents(url).then(function (data) {
				jQuery('.settingsPageDiv').html(data);
				thisInstance.registerEditViewEvents();
			});
		});
	},

	registerEvents: function () {
		if (jQuery('#GanttConfigDetails').length > 0) {
			this.registerDetailViewEvents();
		} else {
			this.registerEditViewEvents();
		}
	}
});

jQuery(document).ready(function () {
	if (jQuery('#GanttConfigDetails, #GanttConfigForm').length > 0) {
		var ganttConfigInstance = new Settings_Vtiger_GanttConfig_Js();
		ganttConfigInstance.registerEvents();
	}
});
