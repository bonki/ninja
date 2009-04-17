<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Total Status widget
 *
 * @package    NINJA
 * @author     op5 AB
 * @license    GPL
 */
class Status_totals_Widget extends widget_Core {

	public function __construct()
	{
		parent::__construct();

		# needed to figure out path to widget
		$this->set_widget_name(__CLASS__, basename(__FILE__));
	}

	public function index($arguments=false, $master=false)
	{
		# required to enable us to assign the correct
		# variables to the calling controller
		$this->master_obj = $master;

		# fetch widget view path
		$view_path = $this->view_path('view');

		if (is_object($arguments[0])) {
			$current_status = $arguments[0];
			array_shift($arguments);
		} else {
			# don't accept widget to call current_status
			# and re-generate all status data
			return false;
		}

		# assign variables for our view
		$label_up = $this->translate->_('Up');
		$label_down = $this->translate->_('Down');
		$label_unreachable = $this->translate->_('Unreachable');
		$label_pending = $this->translate->_('Pending');
		$label_all_problems = $this->translate->_('All Problems');
		$label_all_types = $this->translate->_('All Types');

		$host_title = $this->translate->_('Host Status Totals');

		$host = isset($arguments[0]) ? trim($arguments[0]) : 'all';
		$host_state = isset($arguments[1]) ? $arguments[1] : nagstat::HOST_UP;
		$service_state = isset($arguments[2]) ? $arguments[2] : false;
		$target_method = $service_state === false ? 'host' : 'service';

		# Check if we are dealing with host or servicegroup
		$grouptype = false;
		$groupname = false;
		if (isset($arguments[3]) && !empty($arguments[3])) {
			$groupname = $host; # groupname is passed at same position in arguments as host
			$grouptype = trim($arguments[3]);
			$target_method = $grouptype;
			$group_info = $current_status->get_servicegroup_hoststatus($groupname);
		}

		$total_hosts = 0;
		$total_up = 0;
		$total_down = 0;
		$total_unreachable = 0;
		$total_pending = 0;
		$svc_total_ok = 0;
		$svc_total_warning = 0;
		$svc_total_critical = 0;
		$svc_total_unknown = 0;
		$svc_total_pending = 0;
		$svc_total_services = 0;
		$prev_host = false;

		if (empty($groupname) && $host != 'all') {
			# fetch info on selected host
			$host_model = new Host_Model();
			$host_info = $host_model->get_hostinfo($host);
			$total_up = $host_info->current_state == Current_status_Model::HOST_UP ? 1:0;
			$total_down = $host_info->current_state == Current_status_Model::HOST_DOWN ? 1:0;
			$total_unreachable = $host_info->current_state == Current_status_Model::HOST_UNREACHABLE ? 1:0;
			$total_pending = $host_info->current_state == Current_status_Model::HOST_PENDING ? 1:0;
			$total_hosts = 1;
			$total_problems = $total_down + $total_unreachable;

			# service data
			$service_info = $host_model->service_states($host);
			foreach ($service_info as $service) {
				switch ($service->current_state) {
					case Current_status_Model::SERVICE_OK :
						$svc_total_ok += $service->cnt;
						break;
					case Current_status_Model::SERVICE_WARNING:
						$svc_total_warning += $service->cnt;
						break;
					case Current_status_Model::SERVICE_CRITICAL:
						$svc_total_critical += $service->cnt;
						break;
					case Current_status_Model::SERVICE_UNKNOWN:
						$svc_total_unknown += $service->cnt;
						break;
					case Current_status_Model::SERVICE_PENDING :
						$svc_total_pending += $service->cnt;
						break;
				}
			}
			$svc_total_services += $service->cnt;
			$svc_total_problems = $svc_total_unknown + $svc_total_warning + $svc_total_critical;

		} elseif (!empty($groupname)) {
			foreach ($group_info as $info) {
				$service_states[$info->host_name][$info->service_state] = $info->service_state;
				if ($info->id != $prev_host) {
					switch ($info->current_state) {
						case Current_status_Model::HOST_UP:
							$total_up++;
							break;
						case Current_status_Model::HOST_DOWN:
							$total_down++;
							break;
						case Current_status_Model::HOST_UNREACHABLE:
							$total_unreachable++;
							break;
						case Current_status_Model::HOST_PENDING:
							$total_pending++;
							break;
					}
				}

				# service data
				switch ($info->service_state) {
					case Current_status_Model::SERVICE_OK :
						$svc_total_ok += $info->state_count;
						break;
					case Current_status_Model::SERVICE_WARNING:
						$svc_total_warning += $info->state_count;
						break;
					case Current_status_Model::SERVICE_CRITICAL:
						$svc_total_critical += $info->state_count;
						break;
					case Current_status_Model::SERVICE_UNKNOWN:
						$svc_total_unknown += $info->state_count;
						break;
					case Current_status_Model::SERVICE_PENDING :
						$svc_total_pending += $info->state_count;
						break;
				}
				$svc_total_services += $info->state_count;
				$prev_host = $info->id;
			}

			$total_hosts = sizeof($service_states);
			$total_problems = $total_down + $total_unreachable;

			$svc_total_problems = $svc_total_unknown + $svc_total_warning + $svc_total_critical;
		} else {
			# host data
			$total_up = $current_status->hosts_up;
			$total_down = $current_status->hosts_down;
			$total_unreachable = $current_status->hosts_unreachable;
			$total_pending = $current_status->hosts_pending;
			$total_hosts = $current_status->total_hosts;
			$total_problems = $current_status->hosts_down + $current_status->hosts_unreachable;

			# service data
			$svc_total_ok = $current_status->services_ok;
			$svc_total_warning = $current_status->services_warning;
			$svc_total_unknown = $current_status->services_unknown;
			$svc_total_critical = $current_status->services_critical;
			$svc_total_pending = $current_status->services_pending;
			$svc_total_services = $current_status->total_services;
			$svc_total_problems = $svc_total_unknown + $svc_total_warning + $svc_total_critical;
		}

		$host_header = array(
			array('th_class' =>'hostTotals', 'link_class' => 'hostTotals',  'url' => 'status/'.$target_method.'/'.$host.'/'.nagstat::HOST_UP, 'lable' => $label_up),
			array('th_class' =>'hostTotals', 'link_class' => 'hostTotals',  'url' => 'status/'.$target_method.'/'.$host.'/'.nagstat::HOST_DOWN, 'lable' => $label_down),
			array('th_class' =>'hostTotals', 'link_class' => 'hostTotals',  'url' => 'status/'.$target_method.'/'.$host.'/'.nagstat::HOST_UNREACHABLE, 'lable' => $label_unreachable),
			array('th_class' =>'hostTotals', 'link_class' => 'hostTotals',  'url' => 'status/'.$target_method.'/'.$host.'/'.nagstat::HOST_PENDING, 'lable' => $label_pending)
		);

		$svc_label_ok = $this->translate->_('Ok');
		$svc_label_warning = $this->translate->_('Warning');
		$svc_label_unknown = $this->translate->_('Unknown');
		$svc_label_critical = $this->translate->_('Critical');
		$svc_label_pending = $this->translate->_('Pending');

		$service_header = array(
			array('th_class' =>'serviceTotals', 'link_class' => 'serviceTotals',  'url' => 'status/service/'.$host.'/'.$host_state.'/'.nagstat::SERVICE_OK, 'lable' => $svc_label_ok),
			array('th_class' =>'serviceTotals', 'link_class' => 'serviceTotals',  'url' => 'status/service/'.$host.'/'.$host_state.'/'.nagstat::SERVICE_WARNING, 'lable' => $svc_label_warning),
			array('th_class' =>'serviceTotals', 'link_class' => 'serviceTotals',  'url' => 'status/service/'.$host.'/'.$host_state.'/'.nagstat::SERVICE_UNKNOWN, 'lable' => $svc_label_unknown),
			array('th_class' =>'serviceTotals', 'link_class' => 'serviceTotals',  'url' => 'status/service/'.$host.'/'.$host_state.'/'.nagstat::SERVICE_CRITICAL, 'lable' => $svc_label_critical),
			array('th_class' =>'serviceTotals', 'link_class' => 'serviceTotals',  'url' => 'status/service/'.$host.'/'.$host_state.'/'.nagstat::SERVICE_PENDING, 'lable' => $svc_label_pending)
		);

		$this->css = array('/css/status_totals');
		$this->js = array('/js/status_totals');

		# fetch widget content
		require_once($view_path);

		# call parent helper to assign all
		# variables to master controller
		return $this->fetch();

	}

	public function status()
	{
		$var = false;
		$status = new Current_status_Model;

		# fetch all available states for hosts and services
		$host_states = $status->available_states('host');
		$service_states = $status->available_states('service');

		# what host states are considered a problem?
		$host_problems = array(
			Current_status_Model::HOST_DOWN,
			Current_status_Model::HOST_UNREACHABLE
		);

		# what service states are considered a problem?
		$service_problems = array(
			Current_status_Model::SERVICE_UNKNOWN,
			Current_status_Model::SERVICE_WARNING,
			Current_status_Model::SERVICE_CRITICAL
		);

		$host = false;
		$service = false;
		$total_host_problems = 0;
		$total_service_problems = 0;
		$total_hosts = 0;
		$total_services = 0;

		$result = $status->status_totals('host');
		foreach ($result as $row) {
			$host[$row->current_state] = $row->cnt;
			$total_hosts += $row->cnt;
			if (in_array($row->current_state, $host_problems)) {
				$total_host_problems += $row->cnt;
			}
		}
		$var['total_hosts'] = $total_hosts;
		$var['total_host_problems'] = $total_host_problems;

		$result = $status->status_totals('service');
		foreach ($result as $row) {
			$service[$row->current_state] = $row->cnt;
			$total_services += $row->cnt;
			if (in_array($row->current_state, $service_problems)) {
				$total_service_problems += $row->cnt;
			}
		}
		$var['total_services'] = $total_services;
		$var['total_service_problems'] = $total_service_problems;

		foreach ($host_states as $state) {
			if (!array_key_exists($state, $host)) {
				$var['host'][] = array('state' => $status->translate_status($state, 'host'), 'cnt' => 0);
			} else {
				$var['host'][] = array('state' => $status->translate_status($state, 'host'), 'cnt' => $host[$state]);
			}
		}

		foreach ($service_states as $state) {
			if (!array_key_exists($state, $service)) {
				$var['service'][] = array('state' => $status->translate_status($state, 'service'), 'cnt' => 0);
			} else {
				$var['service'][] = array('state' => $status->translate_status($state, 'service'), 'cnt' => $service[$state]);
			}
		}

		if(request::is_ajax()) {
			$json = zend::instance('json');
			$json_str = $json->encode($var);
			echo $json_str;
			echo $this->output(); # fetch from output buffer
		} else {
			return $var;
		}

	}
}
