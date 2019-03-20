<?php
namespace ProcessWire;

require_once __DIR__ . "/classes/Router.php";

class RestApi extends WireData implements Module {

	public function init() {
		$this->addHookBefore('ProcessPageView::execute', $this, 'checkIfApiRequest');

    	// Let endpoint fall back to 'api' if not set:
		if(!$this->endpoint) $this->endpoint = 'api';
	}

	public function ready () {
	}

	public function checkIfApiRequest(HookEvent $event) {
		$url = $this->sanitizer->url($this->input->url);

    	// support / in endpoint url:
		$endpoint = str_replace("/", "\/", $this->endpoint);

		$regex = '/^\/'.$endpoint.'\/?.*/m';
		preg_match($regex, $url, $matches);

		if($matches) {
			$router = new Router();
			$router->go();
			$event->replace = true;
		}
	}

	public function ___install() {
		$apiPath = "{$this->config->paths->site}api";
		$routesPath = "{$this->config->paths->site}api/Routes.php";
		$examplesPath = "{$this->config->paths->site}api/Example.php";

		if (!file_exists($apiPath)) {
			$this->files->mkdir("{$this->config->paths->site}api");
			$this->message("$this->className: Created api directory: $apiPath");
		}

		if (!file_exists($routesPath)) {
			$this->files->copy(__DIR__ . "/apiTemplate/Routes.php", $routesPath);
			$this->message("$this->className: Created Routes.php in: $routesPath");
		}

		if (!file_exists($examplesPath)) {
			$this->files->copy(__DIR__ . "/apiTemplate/Example.php", $examplesPath);
			$this->message("$this->className: Created Example.php in: $examplesPath");
		}
	}

	public function ___uninstall() {
		$this->message("$this->className: You need to remove the site/api folder yourself if you're not planning on using it anymore");
	}

	public function ___upgrade($fromVersion, $toVersion) {
    	// set authMethod to jwt if it was used before on upgrade to 0.0.3:
		if(version_compare($fromVersion, "0.0.3") === -1) {
			if($this->useJwtAuth) {
				$data = $this->modules->getConfig($this->className);
				$data['authMethod'] = 'jwt';
				$this->modules->saveConfig($this->className, $data);
				$this->message("$this->className: Automatically set Auth Method to 'JWT' since you used JWT Auth before");
			}
		}
	}
}
