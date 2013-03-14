var lsfilter_multiselect = {
	update : function(data) {
		if (data.source == 'multiselect')
			return;
		if (data.metadata.table && data.metadata.table != this.selection_table) {
			this.selection_table = data.metadata.table;
			this.selection = {};

			if (this.commands[this.selection_table]) {
				this.populate_select(this.elem_select,
						this.commands[this.selection_table]);
			} else {
				this.populate_select(this.elem_select, this.commands['other']);
			}

			this.elem_objtype.attr('value', this.selection_table);
		}
	},
	init : function() {
		var self = this; // To be able to access it from within handlers

		this.elem_select = $('#multi_action_select');
		this.elem_objtype = $('#listview_multi_action_obj_type');
		$('#multi_action_select_send').click(function() {
			self.do_send();
		});
	},

	elem_select : false,
	elem_objtype : false,

	selection : {},
	selection_table : false,

	commands : {
		'hosts' : {
			'' : _('Select action'),
			'SCHEDULE_HOST_DOWNTIME' : _('Schedule downtime'),
			'DEL_HOST_DOWNTIME' : _('Cancel Scheduled downtime'),
			'ACKNOWLEDGE_HOST_PROBLEM' : _('Acknowledge'),
			'REMOVE_HOST_ACKNOWLEDGEMENT' : _('Remove problem acknowledgement'),
			'DISABLE_HOST_NOTIFICATIONS' : _('Disable host notifications'),
			'ENABLE_HOST_NOTIFICATIONS' : _('Enable host notifications'),
			'DISABLE_HOST_SVC_NOTIFICATIONS' : _('Disable notifications for all services'),
			'DISABLE_HOST_CHECK' : _('Disable active checks'),
			'ENABLE_HOST_CHECK' : _('Enable active checks'),
			'SCHEDULE_HOST_CHECK' : _('Reschedule host checks'),
			'ADD_HOST_COMMENT' : _('Add host comment')
		},
		'services' : {
			'' : _('Select action'),
			'SCHEDULE_SVC_DOWNTIME' : _('Schedule downtime'),
			'DEL_SVC_DOWNTIME' : _('Cancel Scheduled downtime'),
			'ACKNOWLEDGE_SVC_PROBLEM' : _('Acknowledge'),
			'REMOVE_SVC_ACKNOWLEDGEMENT' : _('Remove problem acknowledgement'),
			'DISABLE_SVC_NOTIFICATIONS' : _('Disable service notifications'),
			'ENABLE_SVC_NOTIFICATIONS' : _('Enable service notifications'),
			'DISABLE_SVC_CHECK' : _('Disable active checks'),
			'ENABLE_SVC_CHECK' : _('Enable active checks'),
			'SCHEDULE_SVC_CHECK' : _('Reschedule service checks'),
			'ADD_SVC_COMMENT' : _('Add service comment')
		},
		'comments' : {
			'' : _('Select action'),
			// DEL_COMMENT has a special case in command controller to send SVC
			// and HOST
			'DEL_COMMENT' : _('Delete comments')
		},
		'other' : {
			'' : _('Table doesn\'t support multi action')
		}
	},

	populate_select : function(elem, values) {
		elem.empty();
		for ( var val in values) {
			var tag = values[val];
			elem.append($('<option />').text(tag).attr('value', val));
		}
	},

	do_send : function() {
		var action = $('#multi_action_select').attr('value');
		if (action) {
			$('#listview_multi_action_obj_action').attr('value', action);
			$('#listview_multi_action_obj_action').attr('value', action);
			$('#listview_multi_action_form').submit();
		}
	},
	box_register : function(key, value) {
		this.selection[key] = value;
	},
	box_selected : function(key) {
		if (this.selection[key])
			return true;
		return false;
	}
};