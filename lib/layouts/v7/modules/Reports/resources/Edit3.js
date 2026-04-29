/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
if(typeof Reports_AdvanceFilter_Js == 'undefined') {
	Vtiger_AdvanceFilter_Js('Reports_AdvanceFilter_Js',{},{

		getFieldSpecificUi : function(fieldSelectElement) {
			var fieldModel = this.fieldModelInstance;
			if(fieldModel.getType().toLowerCase() == 'reference') {
				var conditionRow = fieldSelectElement.closest('div.conditionRow');
				var comparator = conditionRow.find('select[name="comparator"]').val();
				if(comparator != 'e' && comparator != 'n') {
					return this._super(fieldSelectElement);
				}
				var fieldInfo = fieldSelectElement.find('option:selected').data('fieldinfo') || {};
				var referenceModules = fieldModel.get('referencemodules') || fieldInfo.referencemodules || [];
				if(!referenceModules.length) {
					return this._super(fieldSelectElement);
				}
				var value = app.htmlDecode(fieldModel.getValue());
				value = value.replace(/"/g, '&quot;');
				var html = '<div class="fieldValue">'
					+ '<div class="referencefield-wrapper">'
					+ '<input name="popupReferenceModule" type="hidden" value="'+referenceModules[0]+'"/>'
					+ '<div class="input-group">'
					+ '<input class="sourceField" name="'+fieldModel.getName()+'" type="hidden" value=""/>'
					+ '<span class="clearReferenceSelectionWrapper" style="display:table;width:100%;">'
					+ '<input class="autoComplete inputElement ui-autocomplete-input textOverflowEllipsis" type="text"'
					+ ' data-fieldtype="reference"'
					+ ' name="'+fieldModel.getName()+'_display"'
					+ ' value="'+value+'" />'
					+ '<a href="#" class="clearReferenceSelection'+(value ? '' : ' hide')+'"><i class="fa fa-close p-l-8"></i></a>'
					+ '</span>'
					+ '<span class="input-group-addon relatedPopup cursorPointer textAlignCenter" title="Select">'
					+ '<i class="fa fa-search p-l-8"></i>'
					+ '</span>'
					+ '</div>'
					+ '</div>'
					+ '</div>';
				return jQuery(html);
			}
			return this._super(fieldSelectElement);
		},

		loadFieldSpecificUi : function(fieldSelect) {
			this._super(fieldSelect);
			var row = fieldSelect.closest('div.conditionRow');
			var fieldInfo = fieldSelect.find('option:selected').data('fieldinfo');
			var comparator = row.find('select[name="comparator"]').val();
			if(fieldInfo && fieldInfo.type == 'reference' && (comparator == 'e' || comparator == 'n')) {
				row.find('input.sourceField').removeAttr('data-value');
				row.find('input.autoComplete').attr('data-value', 'value').addClass('ignore-validation').removeClass('row-fluid');
			}
			this.registerReferenceFieldEvents(row);
			return this;
		},

		registerReferenceFieldEvents : function(container) {
			var editInstance = Vtiger_Edit_Js.getInstance();
			editInstance.registerAutoCompleteFields(container);
			editInstance.registerClearReferenceSelectionEvent(container);
			editInstance.referenceModulePopupRegisterEvent(container);
		}
	});
}

Reports_Edit_Js("Reports_Edit3_Js",{},{
	
	step3Container : false,
	
	advanceFilterInstance : false,
	
	init : function() {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the report step3 elements
	 * @return jQuery object
	 */
	getContainer : function() {
		return this.step3Container;
	},

	/**
	 * Function to set the report step3 container
	 * @params : element - which represents the report step3 container
	 * @return : current instance
	 */
	setContainer : function(element) {
		this.step3Container = element;
		return this;
	},
	
	/**
	 * Function  to intialize the reports step3
	 */
	initialize : function(container) {
		if(typeof container == 'undefined') {
			container = jQuery('#report_step3');
		}
		
		if(container.is('#report_step3')) {
			this.setContainer(container);
		}else{
			this.setContainer(jQuery('#report_step3'));
		}
	},
	
	calculateValues : function(){
		//handled advanced filters saved values.
		var advfilterlist = this.advanceFilterInstance.getValues();
		jQuery('#advanced_filter').val(JSON.stringify(advfilterlist));
	},
	
	registerSubmitEvent : function(){
		var thisInstance = this;
		var form = this.getContainer();
		form.submit(function(e){
			thisInstance.calculateValues();
		});
	},
	
	registerEvents : function(){
		var container = this.getContainer();
		vtUtils.applyFieldElementsView(container);
		this.advanceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance(jQuery('.filterContainer',container));
		this.registerSubmitEvent();
	}
});
	
