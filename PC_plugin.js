//config
Ext.ns('PC.plugin.timeline');
PC.plugin.timeline.Name = 'pc_timeline';
PC.plugin.timeline.API = PC.global.BASE_URL +'admin/api/plugin/pc-timeline/';
//localization
PC.utils.localize('mod.'+ PC.plugin.timeline.Name, {
	en: {
		no_date: 'Without date'
	},
	lt: {
		no_date: 'Be datos'
	},
	ru: {
		no_date: 'Без даты'
    }
});
PC.plugin.timeline.controllers = ['pc_timeline'];
//register hooks
PC.plugin.timeline.Register = function(ctrl){
	PC.plugin.timeline.controllers.push(ctrl);
}
PC.plugin.timeline.enabledDates = [];
PC.plugin.timeline.Refresh_enabled_dates = function(picker, ctrl, pid, success_callback){
	Ext.Ajax.request({
		url: PC.plugin.timeline.API +'get-calendar-enabled-dates/',
		method: 'POST',
		params: {
			controller: ctrl,
			pid: pid
		},
		success: function(result){
			var json_result = Ext.util.JSON.decode(result.responseText);
			if (json_result.success) {
				PC.plugin.timeline.enabledDates[picker.id] = [];
				var enabledDates = PC.plugin.timeline.enabledDates[picker.id];
				Ext.iterate(json_result.data, function(data){
					enabledDates.push(data.date);
				});
				picker.setDisabledDates(['('+ enabledDates.join('|') +')']);
			}
			if (typeof success_callback == 'function') success_callback();
		}
	});
}
PC.plugin.timeline.Refresh_status_element = function(statusEl, childs){
	if (childs) statusEl.show();
	else statusEl.hide();
	return true;
};
PC.hooks.Register('tree.load', function(params){
	var ln = PC.i18n.mod[PC.plugin.timeline.Name];
	var tree = params.tree;
	var n = params.node;
	var ctrl = n.attributes.controller;
	if (PC.plugin.timeline.controllers.has(ctrl) === false) return false;
	//if (n.attributes.controller != PC.plugin.timeline.Name) return false;
	//create anchor element for datepicker
	var pickerId =  n.ui.elNode.id +'-datepicker';
	//do not render another copy of datepicker for this node if there is already renedered one
	if (Ext.get(pickerId)) return false;
	var pickerEl = document.createElement('li');
	pickerEl.setAttribute('id', pickerId);
	pickerEl.style.marginBottom = '2px';
	//insert anchor to the right place in the dom
	Ext.get(n.getUI().ctNode).insertFirst(pickerEl); //Ext.get(selNode.getUI().elNode.nextSibling).insertFirst
	//create datepicker and render to its anchor
	setTimeout(function(){
		//render datepicker
		n.datepicker = new Ext.DatePickerProfis({
			renderTo: pickerEl,
			format: 'Y-m-d',
			cls: 'x-profis-datepicker',
			handler: function(picker, date, callback){
				var formattedDate = date.format('Y-m-d');
				var statusEl = Ext.get(pickerId +'-status');
				statusEl.update('<img style="vertical-align:-2px;margin-right:3px" src="images/calendar.gif" alt="" />'+ formattedDate);
				tree.addLoaderParam(PC.plugin.timeline.Name, 'date', formattedDate);
				n.reload();
				n.attributes.dateFilter = formattedDate;
				if (typeof callback == 'function') callback();
			},
			listeners: {
				afterrender: function(picker){
					picker.el.dom.style.marginBottom = '5px';
					//get list of dates that has news
					PC.plugin.timeline.Refresh_enabled_dates(picker, ctrl, n.attributes.id);
					//create filter status element
					var filterStatusEl = document.createElement('div');
					filterStatusEl.setAttribute('id', pickerId +'-status');
					filterStatusEl.innerHTML = '<img style="vertical-align:-2px;margin-right:3px" src="images/calendar.gif" alt="" />'+ ln.no_date;
					pickerEl.appendChild(filterStatusEl);
					var filterStatusExtEl = Ext.get(filterStatusEl);
					filterStatusExtEl.setVisibilityMode(Ext.Element.DISPLAY);
					if (!n.childNodes.length) filterStatusExtEl.hide();
					/*//create 'remove date filter' button
					Ext.get(pickerEl).child('.x-date-bottom');*/
				}
			}
		});
		n.addListener('append', function(tree, n, appended, index){
			PC.plugin.timeline.Refresh_status_element(
				Ext.get(pickerId +'-status'),
				n.childNodes.length
			);
		});
		n.addListener('remove', function(tree, n, removed){
			PC.plugin.timeline.Refresh_status_element(
				Ext.get(pickerId +'-status'),
				n.childNodes.length
			);
		});
		pickerEl.style.marginLeft = n.getDepth()*16 +'px';
		pickerEl.style.marginTop = '5px';
		n.addListener('move', function(tree, node, oldParent, newParent, index){
			//n.datepicker.setPosition(n.getDepth()*16+16, 5);
			pickerEl.style.marginLeft = n.getDepth()*16 +'px';
			pickerEl.style.marginTop = '5px';
		});
	}, 70);
});
PC.hooks.Register('page.save', function(params){
	/*remove datefield if page type was changed or removed
	if (params.data.controller == PC.plugin.timeline.Name {
		//...
		return true;
	}*/
	var parentNode = params.tree.getNodeById(params.data.idp);
	if (!parentNode) return false;
	if (PC.plugin.timeline.controllers.has(parentNode.attributes.controller) === false) return false;
	var pageDate = new Date(params.data.date*1000).format('Y-m-d');
	if (pageDate != parentNode.attributes.dateFilter) {
		PC.plugin.timeline.Refresh_enabled_dates(parentNode.datepicker, parentNode.attributes.controller, parentNode.attributes.id, function(){
			//remove node from this date
			params.tree.getNodeById(params.data.id).remove();
			var date = new Date(pageDate);
			parentNode.datepicker.setValue(date);
			parentNode.datepicker.handler(parentNode.datepicker, date, function(){
				var afterExpand = function(){
					var node = params.tree.getNodeById(params.data.id);
					if (node) {
						node.select();
						if (params.create_mode) node.fireEvent('click', node);
					}
				}
				if (!parentNode.expanded) parentNode.expand(false, true, afterExpand);
				else afterExpand();
			});
		});
	}
});
PC.hooks.Register('tree.drop', function(params){
	var drop = params.dropEvent;
	var active_controllers = PC.plugin.timeline.controllers;
	if (drop.point == 'append' && active_controllers.has(drop.target.attributes.controller)!==false) {
		var target = drop.target;
	}
	else if (drop.point != 'append' && active_controllers.has(drop.target.parentNode.attributes.controller)!==false) {
		var target = drop.target.parentNode;
	}
	if (!target) return false;
	//ok, prepare data and continue
	var tree = drop.tree;
	var node = drop.dropNode;
	if (node.attributes.date != target.attributes.dateFilter) {
		Ext.Ajax.request({
			url: 'ajax.page.php?action=update',
			params: {
				data: Ext.util.JSON.encode({
					id: node.attributes.id,
					date: target.attributes.dateFilter
				})
			},
			method: 'POST',
			callback: function(options, success, r) {
				if (success && r.responseText) {
					var data = Ext.util.JSON.decode(r.responseText);
					if (data.success) return;
				}
				Ext.MessageBox.show({
					title: PC.i18n.error,
					msg: PC.i18n.msg.error.data.save,
					buttons: Ext.MessageBox.OK,
					icon: Ext.MessageBox.ERROR
				});
			}
		});
	}
	return true;
});
PC.hooks.Register('dialog.multilnedit.beforerender', function(params){
	if (PC.plugin.timeline.controllers.has(params.node.parentNode.attributes.controller) === false) return false;
	if (!params.create_mode) return false;
	//insert additional date field
	params.dialog.insert(params.dialog.items.length, {
		xtype: 'datefield',
		fieldLabel: PC.i18n.date,
		ref: '../_date',
		anchor: '100%',
		format: 'Y-m-d',
		value: new Date(),
		listeners: {
			specialkey: function(fld, e) {
				if (e.getKey() == e.ENTER) {
					dialog.Save();
				}
			}
		},
		_fld: 'date',
		getFieldValue: function(){
			var time = params.dialog._time.getValue().split(':');
			var timestamp = this.getValue();
			timestamp.setHours(time[0], time[1]);
			return timestamp.getTime()/1000;
		}
	});
	params.dialog.insert(params.dialog.items.length, {
		xtype: 'timefield',
		fieldLabel: PC.i18n.time,
		ref: '_time',
		anchor: '100%',
		format: 'H:i',
		increment: 60,
		value: new Date(),
		listeners: {
			specialkey: function(fld, e) {
				if (e.getKey() == e.ENTER) {
					dialog.Save();
				}
			}
		}/*,
		getFieldValue: function(){
			return this.getValue();
		}*/
	});
	//force inserted field not to be empty
	//---
});
PC.hooks.Register('tree.containercontextmenu', function(params){
	var targetEl = Ext.get(params.event.getTarget());
	if (targetEl) {
		if (/-datepicker$/.test(targetEl.id)) {
			var datePickerEl = targetEl.dom;
		}
		else var datePickerEl = targetEl.findParent('[id$="-datepicker"]');
		if (datePickerEl !== null) {
			//prevent showing default containercontextmenu
			params.defaults.show_menu = false;
			//change tree.current_node & show custom menu
			var newCurrentNodeId = datePickerEl.parentNode.previousSibling.getAttribute('ext:tree-node-id');
			if (newCurrentNodeId) {
				var newCurrentNode = PC.tree.component.getNodeById(newCurrentNodeId);
				if (newCurrentNode) {
					PC.tree.menus.current_node = newCurrentNode;
					var menu = new Ext.menu.Menu({
						items: [PC.tree.actions.CreateSubpage]
					});
					menu.showAt(params.event.getXY());
				}
			}
		}
	}
});