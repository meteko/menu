<?php
namespace Meteko\Menu;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;

/**
 * Simple Menu that can be rendered with Fluid
 */
class Menu {

	/**
	 * @var ActionRequest
	 */
	protected $actionRequest;

	/**
	 * @var MenuItem[]
	 */
	protected $menuItems = [];

	/**
	 * @param ActionRequest $actionRequest
	 * @param array $configuration in the format ['label' => 'A', 'targetPackageKey' => 'Some.Package', ..., 'subMenuItems' => ['label' => A.1', ...], ...]
	 */
	public function __construct(ActionRequest $actionRequest, array $configuration = NULL) {
		$this->actionRequest = $actionRequest;
		if ($configuration !== NULL && isset($configuration['menuItems'])) {
			$this->menuItems = $this->createMenuItems($configuration['menuItems']);
			$this->setActiveMenuItems();
		}
	}

	/**
	 * Recursively creates menu and sub menu items corresponding to the given $configuration
	 *
	 * @param array $menuItemsConfiguration
	 * @return MenuItem[]
	 */
	protected function createMenuItems(array $menuItemsConfiguration) {
		$menuItems = [];
		foreach ($menuItemsConfiguration as $menuItemConfiguration) {
			$label = isset($menuItemConfiguration['label']) ? $menuItemConfiguration['label'] : NULL;
			$targetPackageKey = isset($menuItemConfiguration['package']) ? $menuItemConfiguration['package'] : NULL;
			$targetControllerName = isset($menuItemConfiguration['controller']) ? $menuItemConfiguration['controller'] : NULL;
			$targetActionName = isset($menuItemConfiguration['action']) ? $menuItemConfiguration['action'] : 'index';
			$targetActionArguments = isset($menuItemConfiguration['arguments']) ? $menuItemConfiguration['arguments'] : [];
			$icon = isset($menuItemConfiguration['icon']) ? $menuItemConfiguration['icon'] : NULL;
			$badge = isset($menuItemConfiguration['badge']) ? $menuItemConfiguration['badge'] : NULL;
			$menuItem = new MenuItem($label, $targetPackageKey, $targetControllerName, $targetActionName, $targetActionArguments, $icon, $badge);
			if (isset($menuItemConfiguration['menuItems'])) {
				$menuItem->setSubMenuItems($this->createMenuItems($menuItemConfiguration['menuItems']));
			}
			$menuItems[] = $menuItem;
		}
		return $menuItems;
	}

	/**
	 * @return MenuItem[]
	 */
	public function getMenuItems() {
		return $this->menuItems;
	}

	/**
	 * Adds a menu item to the end of this menu
	 *
	 * @param MenuItem $menuItem
	 * @return Menu the current instance to enable method chaining
	 */
	public function addMenuItem(MenuItem $menuItem) {
		$this->menuItems[] = $menuItem;

		return $this;
	}

	/**
	 * Recursively activates all menu items that match the given actionRequest
	 *
	 * @return void
	 */
	public function setActiveMenuItems() {
		foreach ($this->menuItems as $menuItem) {
			$menuItem->activateForRequest($this->actionRequest);
		}
	}
}