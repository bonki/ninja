<?php


/**
 * Describes a set of objects from livestatus
 */
class HostGroupSet_Model extends BaseHostGroupSet_Model {
	/**
	 * Return resource name of this object
	 * @return string
	 */
	public function mayi_resource() {
		return "monitor.monitoring.hostgroups";
	}
}
