<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Tactical overview controller
 * Requires authentication
 *
 *  op5, and the op5 logo are trademarks, servicemarks, registered servicemarks
 *  or registered trademarks of op5 AB.
 *  All other trademarks, servicemarks, registered trademarks, and registered
 *  servicemarks mentioned herein may be the property of their respective owner(s).
 *  The information contained herein is provided AS IS with NO WARRANTY OF ANY
 *  KIND, INCLUDING THE WARRANTY OF DESIGN, MERCHANTABILITY, AND FITNESS FOR A
 *  PARTICULAR PURPOSE.
 */
class Pnp_Controller extends Authenticated_Controller {
	public function index($host=false, $srv=false)
	{
		$host = $this->input->get('host', $host);
		$srv = $this->input->get('srv', $srv);

		if(!$host) {
			$host = '.pnp-internal';
		}

		$target_link = pnp::url($host, $srv);
		$this->template->content = '<iframe src="'.$target_link.'" style="width: 100%; height: 600px" frameborder="0" id="iframe"></iframe>';
		$this->template->title = _('Reporting » Graphs');
		$this->template->disable_refresh = true;
		$this->template->js[] = $this->add_path('/js/iframe-adjust.js');
		$this->template->js[] = $this->add_path('/js/pnp.js');
	}
}
