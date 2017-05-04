<?php
namespace Meteko\Menu;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\Arrays;

/**
 * Simple Menu that can be rendered with Fluid
 */
class MenuItem {

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var string
	 */
	protected $label = '';

	/**
	 * @var boolean
	 */
	protected $active = FALSE;

	/**
	 * @var string
	 */
	protected $targetPackageKey;

	/**
	 * @var string
	 */
	protected $targetControllerName;

	/**
	 * @var string
	 */
	protected $targetActionName;

	/**
	 * @var array
	 */
	protected $targetActionArguments = [];

	/**
	 * @var string
	 */
	protected $icon;

	/**
	 * @var string
	 */
	protected $badge;

	/**
	 * @var MenuItem[]
	 */
	protected $subMenuItems = [];

	/**
	 * @param string $label label of the menu item - if omitted, the resulting menu item is considered a separator
	 * @param string $targetPackageKey key of the target package - if omitted, the resulting menu item is considered a menu header
	 * @param string $targetControllerName name of the target controller
	 * @param string $targetActionName the target action name
	 * @param array $targetActionArguments the target action arguments
	 * @param string $icon an optional icon identifier (to be used from the fluid template)
	 * @param string $badge an optional badge text (to be used from the fluid template)
	 */
	public function __construct($label, $targetPackageKey = NULL, $targetControllerName = NULL, $targetActionName = NULL, array $targetActionArguments = [], $icon = NULL, $badge = NULL) {
		$this->label = $label;
		$this->targetPackageKey = $targetPackageKey;
		$this->targetControllerName = $targetControllerName;
		$this->targetActionName = $targetActionName;
		$this->targetActionArguments = $targetActionArguments;
		$this->icon = $icon;
		$this->badge = $badge;
	}

	/**
	 * Sets the menu item (and it's sub items) active if it matches the given $actionRequest:
	 *
	 * If the menu item has sub menu items, matching package and controller is enough to mark the item active.
	 * Otherwise the action name has to match as well (unless that is not defined).
	 *
	 * @param ActionRequest $actionRequest
	 * @return void
	 */
	public function activateForRequest(ActionRequest $actionRequest) {
		if ($this->isHeader() || $this->isSeparator()) {
			return;
		}
		if (!$this->matchesRequest($actionRequest)) {
			return;
		}
		$this->active = TRUE;
		foreach ($this->subMenuItems as $subMenuItem) {
			$subMenuItem->activateForRequest($actionRequest);
		}
	}

	/**
	 * returns TRUE if the given $request points to the controller/action specified in this menu item
	 *
	 * @param ActionRequest $actionRequest
	 * @return boolean TRUE if this menuItem is active for the given $request, otherwise FALSE
	 */
	protected function matchesRequest(ActionRequest $actionRequest) {
		if ($this->hasSubMenuItems() || $this->targetActionName === NULL) {
			return $this->targetPackageKey === $actionRequest->getControllerPackageKey() && $this->targetControllerName === $actionRequest->getControllerName();
		}

		if ($this->targetPackageKey !== $actionRequest->getControllerPackageKey()) {
			return FALSE;
		}
		if ($this->targetControllerName !== $actionRequest->getControllerName()) {
			return FALSE;
		}

		// for items with sub items a matching controller is enough to turn it active
		if ($this->hasSubMenuItems() || $this->targetActionName === NULL) {
			return TRUE;
		}

		if ($this->targetActionName !== $actionRequest->getControllerActionName()) {
			return FALSE;
		}

		$targetActionArguments = $this->targetActionArguments;
		$targetActionArguments = $this->persistenceManager->convertObjectsToIdentityArrays($targetActionArguments);
		Arrays::sortKeysRecursively($targetActionArguments);
		$requestArguments = $actionRequest->getArguments();
		Arrays::sortKeysRecursively($requestArguments);

		return ($targetActionArguments === $requestArguments);
	}

	/**
	 * @return boolean TRUE if this menu item is just a header (i.e. no target package set)
	 */
	public function isHeader() {
		return $this->targetPackageKey === NULL && $this->label !== '';
	}

	/**
	 * @return boolean TRUE if this menu item is just a separator (i.e. no label set)
	 */
	public function isSeparator() {
		return $this->label === '';
	}

	/**
	 * @return MenuItem[]
	 */
	public function getSubMenuItems() {
		return $this->subMenuItems;
	}

	/**
	 * @param MenuItem[] $subMenuItems
	 * @return void
	 */
	public function setSubMenuItems(array $subMenuItems) {
		$this->subMenuItems = $subMenuItems;
	}

	/**
	 * @param MenuItem $subMenuItem
	 * @return MenuItem the current instance to enable method chaining
	 */
	public function addSubMenuItem(MenuItem $subMenuItem) {
		$this->subMenuItems[] = $subMenuItem;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function hasSubMenuItems() {
		return $this->subMenuItems !== [];
	}

	/**
	 * @return string
	 */
	public function getTargetPackageKey() {
		return $this->targetPackageKey;
	}

	/**
	 * @return string
	 */
	public function getTargetControllerName() {
		return $this->targetControllerName;
	}

	/**
	 * @return string
	 */
	public function getTargetActionName() {
		return $this->targetActionName;
	}

	/**
	 * @return array
	 */
	public function getTargetActionArguments() {
		return $this->targetActionArguments;
	}

	/**
	 * @return boolean
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getBadge() {
		return $this->badge;
	}

	/**
	 * @return string
	 */
	public function getIcon() {
		return $this->icon;
	}

}