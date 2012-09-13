<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Model for host objects
 */
class Host_Model extends Model {

	public $state_filter = false; /**< value of current_state to filter for */
	public $sort_field ='';/**< Field to sort on */
	public $sort_order='ASC'; /**< ASC/DESC */
	public $service_filter = false; /**< Bitmask of service states to get */
	public $serviceprops = false; /**< A bitmask of service flags as defined in the nagstat helper */
	public $hostprops = false; /**< A bitmask of host flags as defined in the nagstat helper */
	public $num_per_page = false; /**< Number of results per page */
	public $offset = false; /**< Number of results to skip before getting rows */
	public $count = false; /**< Skip getting any results, only count the number of matches */

	/**
	*
	*	Fetch host info filtered on specific field and value
	*/
	public function get_where($field=false, $value=false, $limit=false, $exact=false)
	{
		if (empty($field) || empty($value)) {
			return false;
		}
		$ls   = Livestatus::instance();
		$op   = $exact ? '=' : '~~';
		$data = $ls->getHosts(array('filter' => array($field => array($op => $value)), 'limit' => $limit));
		return count($data)>0 ? $data : false;
	}

	/**
	*	Search through several fields for a specific value
	*/
	public function search($value=false, $limit=false, $xtra_query = false)
	{
		if (empty($value)) return false;
		$ls   = Livestatus::instance();
		$data = $ls->getHosts(array('filter' => array('-or' => array(array('alias' 	   => array('~~' => $value)),
									     array('name'  	   => array('~~' => $value)),
									     array('display_name'  => array('~~' => $value)),
									     array('address'  	   => array('~~' => $value)),
									     array('plugin_output' => array('~~' => $value))
									)),
						    'limit'  => $limit));
		return count($data)>0 ? $data : false;
	}

	/**
	*	Fetch parents for a specific host
	*/
	public function get_parents($host_name=false)
	{
throw new Exception('deprecated');
/* TODO: deprecate */
		if (empty($host_name))
			return false;

		$sql_join = '';
		$sql_where = '';
		if (!$this->auth->view_hosts_root) {
			$sql_join = "INNER JOIN contact_access ON contact_access.host=host_parents.host ";
			$sql_where = "contact_access.contact=".$this->auth->id." AND ";
		}

		$sql = "SELECT host.* ".
		"FROM host ".
		"INNER JOIN host_parents ON host_parents.parents = host.id ".$sql_join.
		"INNER JOIN host child ON host_parents.host = child.id ".
		"WHERE ".$sql_where.
		"child.host_name =".$this->db->escape($host_name).
		" ORDER BY host.host_name";

		$result = $this->query($this->db, $sql);
		return $result;
	}

	/**
	*	Wrapper to set internal sort_field value
	*/
	public function set_sort_field($val)
	{
/* TODO: implement */
		$this->sort_field = trim($val);
	}

	/**
	*	Wrapper to set and validate internal sort_order value
	*/
	public function set_sort_order($val)
	{
/* TODO: implement */
		$val = trim($val);
		$val = strtoupper($val);
		switch ($val) {
			case 'ASC':
				$this->sort_order = $val;
				break;
			case 'DESC':
				$this->sort_order = $val;
				break;
			default: $this->sort_order = 'ASC';
		}
	}

	/**
	* Public method to set the private host_list variable.
	* This is because we always want to validate authorization
	* etc for all hosts passed in.
	*/
	public function set_host_list($host_names=false)
	{
throw new Exception('deprecated');
/* TODO: deprecate */
		$this->host_list = false;
		if (is_string($host_names) && strtolower($host_names) === 'all') {
			$this->host_list = 'all';
			return;
		}
		if (empty($host_names)) {
			return false;
		}

		if (!is_array($host_names))
			$host_names = array($host_names);
		foreach ($host_names as $idx => $hn)
			$host_names[$idx] = $this->db->escape(trim($hn));
		$host_names = implode(',', $host_names);

		if (!$this->show_services) {
			$ca = '';
			if (!$this->auth->view_hosts_root) {
				$ca = ' INNER JOIN contact_access ca ON host.id = ca.host AND ca.contact = '.$this->auth->id;
			}
			$query = "SELECT host.id FROM host $ca WHERE host.host_name IN ($host_names)";
			$res = $this->db->query($query);
			foreach ($res as $row) {
				$this->host_list[$row->id] = $row->id;
			}
		} else {
			$ca = '';
			if (!$this->auth->view_hosts_root) {
				$ca = 'INNER JOIN service ON host.host_name = service.host_name INNER JOIN contact_access ca ON service.id = ca.service AND ca.contact= '.$this->auth->id;
			}
			$query = "SELECT host.id FROM host $ca WHERE host.host_name IN ($host_names)";
			$res = $this->db->query($query);
			foreach ($res as $row) {
				$this->host_list[$row->id] = $row->id;
			}
		}
	}

	/**
	 * Fetch status data for a subset of hosts
	 * (and their related services if show_services is set to true).
	 */
	public function get_host_status()
	{
throw new Exception('deprecated');
/* TODO: deprecate */
		if (!empty($this->host_list) && $this->host_list !== 'all')
			$host_str = implode(', ', $this->host_list);

		$filter_sql = '';
		$filter_host_sql = false;
		$filter_service_sql = false;
		$where_str = false;
		$from = 'host ';

		if (!empty($this->state_filter)) {
			$bits = db::bitmask_to_string($this->state_filter);
			$filter_host_sql = " AND %scurrent_state IN ($bits) ";
		}
		if ($this->service_filter!==false && !empty($this->service_filter)) {
			$bits = db::bitmask_to_string($this->service_filter);
			$filter_service_sql = " AND %scurrent_state IN ($bits) ";
		}

		if (!$this->show_services) {
			$where = 'WHERE 1=1 ';

			if (!empty($filter_host_sql)) {
				$filter_sql .= sprintf($filter_host_sql, 'host.');
			}
			if (!empty($filter_service_sql)) {
				$filter_sql .= sprintf($filter_service_sql, 'service.');
			}

			# this should never happen but added just to be on the safe side
			if ($this->serviceprops != false) {
				$from .= 'INNER JOIN service ON service.host_name=host.host_name ';
			}

			$serviceprops_sql = $this->build_service_props_query($this->serviceprops, 'service.', 'host.');
			$hostprops_sql = $this->build_host_props_query($this->hostprops, 'host.');

			# remove possible table aliases just to be on the safe side here
			# should never happen but if users copy/paste an URL from a service
			# listing (changing status/service to status/host) we might run into trouble
			$this->sort_field = str_replace('s.', '',$this->sort_field);
			$this->sort_field = str_replace('h.', '',$this->sort_field);

			$this->sort_field = empty($this->sort_field) ? 'host_name' : $this->sort_field;

			# when we have a valid host_list, i.e not 'all'
			# then we should filter on these hosts
			if ($this->host_list !== 'all' && !empty($host_str)) {
				$where .= empty($where) ?  "" : " AND ";
				$where .= "host.id IN(".$host_str.") ";
			}
			else if (!$this->auth->view_hosts_root) {
				$from .= ', contact_access ca ';
				$where = ' WHERE ca.contact='.$this->auth->id.' AND ca.service IS NULL AND ca.host=host.id ';
			}

			# only host listing
			$sql = "SELECT ".
					"host.instance_id AS host_instance_id, ".
					"host.id AS host_id, ".
					"host.host_name, ".
					"host.address, ".
					"host.alias, ".
					"host.current_state, ".
					"host.last_check, ".
					"host.next_check, ".
					"host.should_be_scheduled, ".
					"host.notes_url, ".
					"host.notes, ".
					"host.notifications_enabled, ".
					"host.active_checks_enabled, ".
					"host.icon_image, ".
					"host.icon_image_alt, ".
					"host.is_flapping, ".
					"host.action_url, ".
					"(UNIX_TIMESTAMP() - "."host.last_state_change) AS duration, ".
					"UNIX_TIMESTAMP() AS cur_time, ".
					"host.current_attempt, ".
					"host.max_check_attempts, ".
					"host.problem_has_been_acknowledged, ".
					"host.scheduled_downtime_depth, ".
					"host.output, ".
					"host.long_output, ".
					"host.display_name ".
				"FROM ".$from.$where.
					$filter_sql.$hostprops_sql.$serviceprops_sql;

			$order = " ORDER BY ".$this->sort_field." ".$this->sort_order;
			$sql .= $order;
		} else {
			$from .= ', service';
			$where = '';
			if (!$this->auth->view_hosts_root || !$this->auth->view_services_root) {
				$from .= ', contact_access ca ';

				# match authorized services against service.host_name
				$where = ' WHERE ca.contact='.$this->auth->id.' AND service.host_name=host.host_name'.
					' AND ca.host IS NULL AND ca.service=service.id ';
			} else {
				$where = ' WHERE service.host_name=host.host_name ';
			}

			if (!empty($filter_host_sql)) {
				$filter_sql .= sprintf($filter_host_sql, 'host.');
			}
			if (!empty($filter_service_sql)) {
				$filter_sql .= sprintf($filter_service_sql, 'service.');
			}

			$serviceprops_sql = $this->build_service_props_query($this->serviceprops, 'service.', 'host.');
			$hostprops_sql = $this->build_host_props_query($this->hostprops, 'host.');

			if (empty($this->sort_field)) {
				$this->sort_field = 'host_name, service_description';
			} else {
				$this->sort_field = str_replace('s.', '',$this->sort_field);
				$this->sort_field = str_replace('h.', '',$this->sort_field);
			}

			# build list of fields to fetch
			$field_list = "host.id AS host_id,".
					"host.instance_id AS host_instance_id,".
					"host.host_name,".
					"host.address,".
					"host.alias,".
					"host.current_state AS host_state,".
					"host.problem_has_been_acknowledged AS hostproblem_is_acknowledged,".
					"host.scheduled_downtime_depth AS hostscheduled_downtime_depth,".
					"host.notifications_enabled AS host_notifications_enabled,".
					"host.active_checks_enabled AS host_active_checks_enabled,".
					"host.action_url AS host_action_url,".
					"host.icon_image AS host_icon_image,".
					"host.icon_image_alt AS host_icon_image_alt,".
					"host.is_flapping AS host_is_flapping,".
					"host.notes_url AS host_notes_url,".
					"host.notes AS host_notes,".
					"host.display_name AS host_display_name,".
					"service.id AS service_id,".
					"service.instance_id AS service_instance_id,".
					"service.service_description,".
					"service.current_state,".
					"service.last_check,".
					"service.next_check,".
					"service.should_be_scheduled,".
					"service.notifications_enabled,".
					"service.active_checks_enabled,".
					"service.action_url,".
					"service.notes_url,".
					"service.notes,".
					"service.icon_image,".
					"service.icon_image_alt,".
					"service.passive_checks_enabled,".
					"service.problem_has_been_acknowledged,".
					"(service.scheduled_downtime_depth + host.scheduled_downtime_depth) AS scheduled_downtime_depth,".
					"service.is_flapping as service_is_flapping,".
					"(UNIX_TIMESTAMP() - service.last_state_change) AS duration, UNIX_TIMESTAMP() AS cur_time,".
					"service.current_attempt,".
					"service.max_check_attempts,".
					"service.output,".
					"service.long_output,".
					"service.display_name";

			# when we have a valid host_list, i.e not 'all'
			# then we should filter on these hosts
			if ($this->host_list !== 'all' && !empty($host_str)) {
				$where .= empty($where) ?  "" : " AND ";
				$where .= "host.id IN(".$host_str.") ";
			}
			$sql = "SELECT ".$field_list." FROM ".
					$from.$where.
					$filter_sql.$hostprops_sql.$serviceprops_sql;

			$sql .= " ORDER BY ".$this->sort_field." ".$this->sort_order;
		}
		if (!empty($this->num_per_page) && $this->offset !== false) {
			$sql .= ' LIMIT '.$this->num_per_page.' OFFSET '.$this->offset;
		}

		if ($this->count) {
			$sql = "SELECT COUNT(1) AS cnt FROM $from$where$filter_sql$hostprops_sql$serviceprops_sql";
		}

		$result = $this->query($this->db,$sql);
		if ($this->count === true) {
			return $result[0]->cnt;
		}
		$rc = array();
		foreach( $result as $row )
		{
			$rc[] = $row;
		}
		unset($result);
		return $rc;
	}

	/**
	*	Build a string to be used in a livestatus query to filter on different service properties
	*/
	public static function build_service_livestatus_props($serviceprops=false)
	{
throw new Exception('implement');
/* TODO: implement */
		if (empty($serviceprops))
			return false;
		$ret = array();
		if ($serviceprops & nagstat::SERVICE_SCHEDULED_DOWNTIME)
			$ret[] = "Filter: scheduled_downtime_depth > 0\nFilter: host_scheduled_downtime_depth > 0\nOr: 2";
		if ($serviceprops & nagstat::SERVICE_NO_SCHEDULED_DOWNTIME)
			$ret[] = "Filter: scheduled_downtime_depth = 0\nFilter: host_scheduled_downtime_depth = 0";
		if ($serviceprops & nagstat::SERVICE_STATE_ACKNOWLEDGED)
			$ret[] = 'Filter: acknowledged != 0';
		if ($serviceprops & nagstat::SERVICE_STATE_UNACKNOWLEDGED)
			$ret[] = 'Filter: acknowledged = 0';
		if ($serviceprops & nagstat::SERVICE_CHECKS_DISABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret[] = "Filter: active_checks_enabled = 0\nFilter: passive_checks_enabled = 0\nOr: 2";
			else
				$ret[] = 'Filter: active_checks_enabled = 0';
		}
		if ($serviceprops & nagstat::SERVICE_CHECKS_ENABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret[] = "Filter: active_checks_enabled = 1\nFilter: passive_checks_enabled = 1\nOr: 2";
			else
				$ret[] = 'Filter: active_checks_enabled = 1';
		}
		if ($serviceprops & nagstat::SERVICE_EVENT_HANDLER_DISABLED)
			$ret[] = 'Filter: event_handler_enabled = 0';
		if ($serviceprops & nagstat::SERVICE_EVENT_HANDLER_ENABLED)
			$ret[] = 'Filter: event_handler_enabled = 1';
		if ($serviceprops & nagstat::SERVICE_FLAP_DETECTION_DISABLED)
			$ret[] = 'Filter: flap_detection_enabled = 0';
		if ($serviceprops & nagstat::SERVICE_FLAP_DETECTION_ENABLED)
			$ret[] = 'Filter: flap_detection_enabled = 1';
		if ($serviceprops & nagstat::SERVICE_IS_FLAPPING)
			$ret[] = 'Filter: is_flapping = 1';
		if ($serviceprops & nagstat::SERVICE_IS_NOT_FLAPPING)
			$ret[] = 'Filter: is_flapping = 0';
		if ($serviceprops & nagstat::SERVICE_NOTIFICATIONS_DISABLED)
			$ret[] = 'Filter: notifications_enabled = 0';
		if ($serviceprops & nagstat::SERVICE_NOTIFICATIONS_ENABLED)
			$ret[] = 'Filter: notifications_enabled = 1';
		if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECKS_DISABLED)
			$ret[] = 'Filter: passive_checks_enabled = 0';
		if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECKS_ENABLED)
			$ret[] = 'Filter: passive_checks_enabled = 1';
		if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECK)
			$ret[] = 'Filter: check_type = 0';
		if ($serviceprops & nagstat::SERVICE_ACTIVE_CHECK)
			$ret[] = 'Filter: check_type > 0';
		if ($serviceprops & nagstat::SERVICE_HARD_STATE)
			$ret[] = 'Filter: state_type = 1';
		if ($serviceprops & nagstat::SERVICE_SOFT_STATE)
			$ret[] = 'Filter: state_type = 0';

		return implode("\n", $ret);
	}

	/**
	*	Build a string to be used in a sql query to filter on different service properties
	*/
	public static function build_service_props_query($serviceprops=false, $table_alias='', $host_table_alias='')
	{
throw new Exception('implement');
/* TODO: implement */
		if (empty($serviceprops))
			return false;
		$ret_str = false;
		if ($serviceprops & nagstat::SERVICE_SCHEDULED_DOWNTIME)
			$ret_str .= ' AND ('.$table_alias.'scheduled_downtime_depth + '.$host_table_alias.'scheduled_downtime_depth)>0 ';
		if ($serviceprops & nagstat::SERVICE_NO_SCHEDULED_DOWNTIME)
			$ret_str .= ' AND ('.$table_alias.'scheduled_downtime_depth + '.$host_table_alias.'scheduled_downtime_depth)<=0 ';
		if ($serviceprops & nagstat::SERVICE_STATE_ACKNOWLEDGED)
			$ret_str .= ' AND '.$table_alias.'problem_has_been_acknowledged!=0 ';
		if ($serviceprops & nagstat::SERVICE_STATE_UNACKNOWLEDGED)
			$ret_str .= ' AND '.$table_alias.'problem_has_been_acknowledged=0 ';
		if ($serviceprops & nagstat::SERVICE_CHECKS_DISABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret_str .= ' AND ('.$table_alias.'active_checks_enabled=0 AND .'.$table_alias.'passive_checks_enabled=0) ';
			else
				$ret_str .= ' AND '.$table_alias.'active_checks_enabled=0 ';
		}
		if ($serviceprops & nagstat::SERVICE_CHECKS_ENABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret_str .= ' AND ('.$table_alias.'active_checks_enabled=1 OR '.$table_alias.'passive_checks_enabled=1) ';
			else
				$ret_str .= ' AND '.$table_alias.'active_checks_enabled=1 ';
		}
		if ($serviceprops & nagstat::SERVICE_EVENT_HANDLER_DISABLED)
			$ret_str .= ' AND '.$table_alias.'event_handler_enabled=0 ';
		if ($serviceprops & nagstat::SERVICE_EVENT_HANDLER_ENABLED)
			$ret_str .= ' AND '.$table_alias.'event_handler_enabled=1 ';
		if ($serviceprops & nagstat::SERVICE_FLAP_DETECTION_DISABLED)
			$ret_str .= ' AND '.$table_alias.'flap_detection_enabled=0 ';
		if ($serviceprops & nagstat::SERVICE_FLAP_DETECTION_ENABLED)
			$ret_str .= ' AND '.$table_alias.'flap_detection_enabled=1 ';
		if ($serviceprops & nagstat::SERVICE_IS_FLAPPING)
			$ret_str .= ' AND '.$table_alias.'is_flapping=1 ';
		if ($serviceprops & nagstat::SERVICE_IS_NOT_FLAPPING)
			$ret_str .= ' AND '.$table_alias.'is_flapping=0 ';
		if ($serviceprops & nagstat::SERVICE_NOTIFICATIONS_DISABLED)
			$ret_str .= ' AND '.$table_alias.'notifications_enabled=0 ';
		if ($serviceprops & nagstat::SERVICE_NOTIFICATIONS_ENABLED)
			$ret_str .= ' AND '.$table_alias.'notifications_enabled=1 ';
		if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECKS_DISABLED)
			$ret_str .= ' AND '.$table_alias.'passive_checks_enabled=0 ';
		if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECKS_ENABLED)
			$ret_str .= ' AND '.$table_alias.'passive_checks_enabled=1 ';
		if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECK)
			$ret_str .= ' AND '.$table_alias.'check_type='.Current_status_Model::SERVICE_CHECK_PASSIVE.' ';
		if ($serviceprops & nagstat::SERVICE_ACTIVE_CHECK)
			$ret_str .= ' AND '.$table_alias.'check_type='.Current_status_Model::SERVICE_CHECK_ACTIVE.' ';
		if ($serviceprops & nagstat::SERVICE_HARD_STATE)
			$ret_str .= ' AND '.$table_alias.'state_type=1 ';
		if ($serviceprops & nagstat::SERVICE_SOFT_STATE)
			$ret_str .= ' AND '.$table_alias.'state_type=0 ';

		return $ret_str;
	}

	/**
	*	Build a string to be used in a livestatus query to filter on different host properties
	*/
	public static function build_host_livestatus_props($hostprops)
	{
throw new Exception('implement');
/* TODO: implement */
		if (empty($hostprops))
			return false;
		$ret = array();
		if ($hostprops & nagstat::HOST_SCHEDULED_DOWNTIME)
			$ret[] = 'Filter: scheduled_downtime_depth > 0';
		if ($hostprops & nagstat::HOST_NO_SCHEDULED_DOWNTIME)
			$ret[] = 'Filter: scheduled_downtime_depth = 0';
		if ($hostprops & nagstat::HOST_STATE_ACKNOWLEDGED)
			$ret[] = 'Filter: acknowledged = 1';
		if ($hostprops & nagstat::HOST_STATE_UNACKNOWLEDGED)
			$ret[] = 'Filter: acknowledged = 0';
		if ($hostprops & nagstat::HOST_CHECKS_DISABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret[] = "Filter: active_checks_enabled = 0\nFilter: passive_checks_enabled = 0";
			else
				$ret[] = 'Filter: active_checks_enabled = 0';
		}
		if ($hostprops & nagstat::HOST_CHECKS_ENABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret[] = "Filter: active_checks_enabled = 1\nFilter: passive_checks_enabled = 1\nOr: 2";
			else
				$ret[] = 'Filter: active_checks_enabled = 1';
		}
		if ($hostprops & nagstat::HOST_EVENT_HANDLER_DISABLED)
			$ret[] = 'Filter: event_handler_enabled = 0';
		if ($hostprops & nagstat::HOST_EVENT_HANDLER_ENABLED)
			$ret[] = 'Filter: event_handler_enabled = 1';
		if ($hostprops & nagstat::HOST_FLAP_DETECTION_DISABLED)
			$ret[] = 'Filter: flap_detection_enabled = 0';
		if ($hostprops & nagstat::HOST_FLAP_DETECTION_ENABLED)
			$ret[] = 'Filter: flap_detection_enabled = 1';
		if ($hostprops & nagstat::HOST_IS_FLAPPING)
			$ret[] = 'Filter: is_flapping = 1';
		if ($hostprops & nagstat::HOST_IS_NOT_FLAPPING)
			$ret[] = 'Filter: is_flapping = 0';
		if ($hostprops & nagstat::HOST_NOTIFICATIONS_DISABLED)
			$ret[] = 'Filter: notifications_enabled = 0';
		if ($hostprops & nagstat::HOST_NOTIFICATIONS_ENABLED)
			$ret[] = 'Filter: notifications_enabled = 1';
		if ($hostprops & nagstat::HOST_PASSIVE_CHECKS_DISABLED)
			$ret[] = 'Filter: passive_checks_enabled = 0';
		if ($hostprops & nagstat::HOST_PASSIVE_CHECKS_ENABLED)
			$ret[] = 'Filter: passive_checks_enabled = 1';
		if ($hostprops & nagstat::HOST_PASSIVE_CHECK)
			$ret[] = 'Filter: check_type > 0';
		if ($hostprops & nagstat::HOST_ACTIVE_CHECK)
			$ret[] = 'Filter: check_type = 0';
		if ($hostprops & nagstat::HOST_HARD_STATE)
			$ret[] = 'Filter: state_type = 1';
		if ($hostprops & nagstat::HOST_SOFT_STATE)
			$ret[] = 'Filter: state_type = 0';

		return implode("\n", $ret);
	}

	/**
	*	Build a string to be used in a sql query to filter on different host properties
	*/
	public static function build_host_props_query($hostprops=false, $table_alias='')
	{
throw new Exception('implement');
/* TODO: implement */
		if (empty($hostprops))
			return false;
		$ret_str = false;
		if ($hostprops & nagstat::HOST_SCHEDULED_DOWNTIME)
			$ret_str .= ' AND '.$table_alias.'scheduled_downtime_depth>0 ';
		if ($hostprops & nagstat::HOST_NO_SCHEDULED_DOWNTIME)
			$ret_str .= ' AND '.$table_alias.'scheduled_downtime_depth<=0 ';
		if ($hostprops & nagstat::HOST_STATE_ACKNOWLEDGED)
			$ret_str .= ' AND '.$table_alias.'problem_has_been_acknowledged=1 ';
		if ($hostprops & nagstat::HOST_STATE_UNACKNOWLEDGED)
			$ret_str .= ' AND '.$table_alias.'problem_has_been_acknowledged=0 ';
		if ($hostprops & nagstat::HOST_CHECKS_DISABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret_str .= ' AND ('.$table_alias.'active_checks_enabled=0 AND .'.$table_alias.'passive_checks_enabled=0) ';
			else
				$ret_str .= ' AND '.$table_alias.'active_checks_enabled=0 ';
		}
		if ($hostprops & nagstat::HOST_CHECKS_ENABLED) {
			if (config::get('checks.show_passive_as_active', '*'))
				$ret_str .= ' AND ('.$table_alias.'active_checks_enabled=1 OR '.$table_alias.'passive_checks_enabled=1) ';
			else
				$ret_str .= ' AND '.$table_alias.'active_checks_enabled=1 ';
		}
		if ($hostprops & nagstat::HOST_EVENT_HANDLER_DISABLED)
			$ret_str .= ' AND '.$table_alias.'event_handler_enabled=0 ';
		if ($hostprops & nagstat::HOST_EVENT_HANDLER_ENABLED)
			$ret_str .= ' AND '.$table_alias.'event_handler_enabled=1 ';
		if ($hostprops & nagstat::HOST_FLAP_DETECTION_DISABLED)
			$ret_str .= ' AND '.$table_alias.'flap_detection_enabled=0 ';
		if ($hostprops & nagstat::HOST_FLAP_DETECTION_ENABLED)
			$ret_str .= ' AND '.$table_alias.'flap_detection_enabled=1 ';
		if ($hostprops & nagstat::HOST_IS_FLAPPING)
			$ret_str .= ' AND '.$table_alias.'is_flapping=1 ';
		if ($hostprops & nagstat::HOST_IS_NOT_FLAPPING)
			$ret_str .= ' AND '.$table_alias.'is_flapping=0 ';
		if ($hostprops & nagstat::HOST_NOTIFICATIONS_DISABLED)
			$ret_str .= ' AND '.$table_alias.'notifications_enabled=0 ';
		if ($hostprops & nagstat::HOST_NOTIFICATIONS_ENABLED)
			$ret_str .= ' AND '.$table_alias.'notifications_enabled=1 ';
		if ($hostprops & nagstat::HOST_PASSIVE_CHECKS_DISABLED)
			$ret_str .= ' AND '.$table_alias.'passive_checks_enabled=0 ';
		if ($hostprops & nagstat::HOST_PASSIVE_CHECKS_ENABLED)
			$ret_str .= ' AND '.$table_alias.'passive_checks_enabled=1 ';
		if ($hostprops & nagstat::HOST_PASSIVE_CHECK)
			$ret_str .= ' AND '.$table_alias.'check_type='.nagstat::HOST_CHECK_PASSIVE.' ';
		if ($hostprops & nagstat::HOST_ACTIVE_CHECK)
			$ret_str .= ' AND '.$table_alias.'check_type='.nagstat::HOST_CHECK_ACTIVE.' ';
		if ($hostprops & nagstat::HOST_HARD_STATE)
			$ret_str .= ' AND '.$table_alias.'state_type=1 ';
		if ($hostprops & nagstat::HOST_SOFT_STATE)
			$ret_str .= ' AND '.$table_alias.'state_type=0 ';

		return $ret_str;
	}

	/**
	 * Fetch status for single object (host/service)
	 * Will fetch status for both host and service if
	 * both params are present.
	 */
	public static function object_status($host_name=false, $service_description=false)
	{
		$ls   = Livestatus::instance();
		if(empty($service_description)) {
			$data = $ls->getHosts(array('filter' => array('name' => $host_name)));
		} else {
			$data = $ls->getServices(array('filter' => array('host_name' => $host_name, 'description' => $service_description)));
		}
		return count($data)>0 ? $data : false;
	}

	/**
	*	Fetch performance data for checks (active/passive)
	*/
	public function performance_data($checks_state=1)
	{
throw new Exception('deprecated');
/* TODO: deprecate, use stats model instead */
		# only allow 0/1
		$checks_state = $checks_state==1 ? 1 : 0;
		$active_passive = $checks_state == 1 ? 'active' : 'passive';
		$auth = Nagios_auth_Model::instance();
		if ($auth->view_hosts_root) {
			$ca = '';
		} else {
			$ca = " INNER JOIN contact_access ca ON ca.$this->table = $this->table.id AND ca.contact = $auth->id ";
		}

		$extra_sql = "";
		if ($checks_state == 1) {
			# fields only needed for active checks
			$extra_sql = ", SUM(execution_time) AS exec_time, MIN(execution_time) AS min_exec_time, ".
				"MAX(execution_time) AS max_exec_time, ".
				"MIN(latency) AS min_latency, MAX(latency) AS max_latency, SUM(latency) AS sum_latency ";
		}
		$sql = "SELECT COUNT(id) AS cnt, ".
			"SUM(percent_state_change) AS tot_perc_change, ".
			"MIN(percent_state_change) AS min_perc_change, ".
			"MAX(percent_state_change) AS max_perc_change ".
			$extra_sql .
			"FROM ".$this->table." ".$ca.
			"WHERE active_checks_enabled=".$checks_state." ";

		$result = $this->query($this->db,$sql);
		if (count($result)) {
			foreach ($result as $row) {
				if ($checks_state == 1) { # active checks
					$this->total_active_host_checks = !is_null($row->cnt) ? $row->cnt : 0;
					$this->total_host_execution_time = !is_null($row->exec_time) ? $row->exec_time : 0;
					$this->min_host_execution_time = !is_null($row->min_exec_time) ? $row->min_exec_time : 0;
					$this->max_host_execution_time = !is_null($row->max_exec_time) ? $row->max_exec_time : 0;
					$this->total_host_percent_change_a =  !is_null($row->tot_perc_change) ? $row->tot_perc_change : 0;
					$this->min_host_percent_change_a = !is_null($row->min_perc_change) ? $row->min_perc_change : 0;
					$this->max_host_percent_change_a = !is_null($row->max_perc_change) ? $row->max_perc_change : 0;
					$this->total_host_latency = !is_null($row->sum_latency) ? $row->sum_latency : 0;
					$this->min_host_latency = !is_null($row->min_latency) ? $row->min_latency : 0;
					$this->max_host_latency = !is_null($row->max_latency) ? $row->max_latency : 0;
				} else{
					$this->total_passive_host_checks = !is_null($row->cnt) ? $row->cnt : 0;
					$this->total_host_percent_change_b =  !is_null($row->tot_perc_change) ? $row->tot_perc_change : 0;
					$this->min_host_percent_change_b = !is_null($row->min_perc_change) ? $row->min_perc_change : 0;
					$this->max_host_percent_change_b = !is_null($row->max_perc_change) ? $row->max_perc_change : 0;
				}
			}
		}
		unset($sql);

		$this->compute_last_check($checks_state, 60);			# checks_1min
		$this->compute_last_check($checks_state, 300);			# checks_5min
		$this->compute_last_check($checks_state, 900);			# checks_15min
		$this->compute_last_check($checks_state, 3600);			# checks_1hour
		$this->compute_last_check($checks_state, false, true);	# checks_start
		$this->compute_last_check($checks_state, false, false);	# checks_ever


	}

	/**
	*	Compute how many checks made in a specific time frame
	* 	Doesn't return anything but rather sets some class variables
	* 	depending on input
	*/
	public function compute_last_check($checks_state=1, $time_arg=false, $prog_start=false)
	{
throw new Exception('deprecated');
/* TODO: deprecate, use stats model instead */
		# only allow 0/1
		$checks_state = $checks_state==1 ? 1 : 0;
		$active_passive = $checks_state == 1 ? 'active' : 'passive';
		$auth = Nagios_auth_Model::instance();
		if ($auth->view_hosts_root || $auth->authorized_for_system_information) {
			$ca = '';
			$ca_w_alias = '';
		} else {
			$ca_w_alias = " INNER JOIN contact_access ca ON ca.$this->table = t.id AND ca.contact =  $auth->id ";
			$ca = " INNER JOIN contact_access ca ON ca.$this->table = $this->table.id AND ca.contact =  $auth->id ";
		}

		$sql = false;
		$class_var = false;
		if ($prog_start !== false) {
			$sql = "SELECT COUNT(t.id) AS cnt FROM ".$this->table." t $ca_w_alias, program_status ps WHERE last_check>=ps.program_start AND t.active_checks_enabled=".$checks_state;
			$class_var = 'start';
		} else {
			$sql = "SELECT COUNT(*) AS cnt FROM ".$this->table." $ca WHERE last_check>=(UNIX_TIMESTAMP()-".(int)$time_arg.") AND active_checks_enabled=".$checks_state;
			switch ($time_arg) {
				case 60:
					$class_var = '1min';
					break;
				case 300:
					$class_var = '5min';
					break;
				case 900:
					$class_var = '15min';
					break;
				case 3600:
					$class_var = '1hour';
					break;
			}
		}

		if (empty($sql) && empty($class_var)) {
			$sql = "SELECT COUNT(*) AS cnt FROM ".$this->table." $ca WHERE last_check>0 AND active_checks_enabled=".$checks_state;
			$class_var = 'ever';
		}
		$class_var = $active_passive.'_'.$this->table.'_checks_'.$class_var;

		$result = $this->query($this->db,$sql);
		if (count($result)) {
			foreach ($result as $row) {
				$this->{$class_var} = !is_null($row->cnt) ? $row->cnt : 0;
			}
		}
	}

	/**
	 * Given a hostgroup name, return all host data for all hosts in it
	 * @param $name Hostgroup name
	 * @return false on error, otherwise database result
	 */
	public function get_hosts_for_group($name)
	{
throw new Exception('deprecated');
/* TODO: deprecate, use filter instead */
		$hostgroup_model = new Hostgroup_Model();
		return $hostgroup_model->get_hosts_for_group($name);
	}


	/**
	*	Generate all performance data needed for performance info page
	* 	Wraps calls to performance data for both active and passive checks
	*/
	public function get_performance_data()
	{
throw new Exception('deprecated');
/* TODO: deprecate, use stats model instead */
		$this->performance_data(1);	# generate active check performance data
		$this->performance_data(0);	# generate passive check performance data
	}

	/**
	*	Fetch all services for a single host
	*/
	public function get_services($host_name=false)
	{
throw new Exception('deprecated');
/* TODO: deprecate, use service model with filter instead */
		if (empty($host_name)) {
			return false;
		}
		if (!isset($this->db) || !is_object($this->db)) {
			$db = Database::instance();
		} else {
			$db = $this->db;
		}
		$auth = Nagios_auth_Model::instance();

		$sql = "SELECT service_description FROM service";
		if (!$auth->view_hosts_root && !$auth->view_services_root)
			$sql .= " INNER JOIN contact_access ON contact_access.service = service.id AND contact_access.contact = ".$auth->id;
		$sql .= " WHERE host_name = ".$db->escape($host_name)." ORDER BY service_description";

		$data = self::query($db,$sql);
		return count($data)>0 ? $data : false;
	}
}
