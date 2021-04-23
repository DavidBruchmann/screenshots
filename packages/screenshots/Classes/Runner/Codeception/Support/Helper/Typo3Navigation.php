<?php

declare(strict_types=1);
namespace TYPO3\CMS\Screenshots\Runner\Codeception\Support\Helper;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Codeception\Module;
use Codeception\Module\WebDriver;
use Facebook\WebDriver\Exception\ElementNotInteractableException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;

/**
 * Helper to support comfortable navigation of the TYPO3 backend.
 *
 * This helper contains a slightly adapted copy of class AbstractPageTree of the typo3/testing-framework package.
 * It should be integrated there ideally. Currently it differs by:
 * - adding the actions to the actor instead of providing an own class
 *   to prevent additional injections in testing classes
 * - considering the configuration param "wait" of module "WebDriver"
 *   when opening the page tree path and the page tree is not visible immediately
 *
 * This helper contains a slightly adapted copy of trait FrameSteps of the typo3/testing-framework package.
 * It should be integrated there ideally. Currently it differs by:
 * - it is a helper class, not an actor trait, and therefore can be used by other helper classes
 *
 * @see \TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractPageTree
 */
class Typo3Navigation extends Module
{
    // Selectors
    protected string $pageTreeFrameSelector = '#typo3-pagetree';
    protected string $pageTreeSelector = '#typo3-pagetree-treeContainer';
    protected string $treeItemSelector = 'g.nodes > .node';
    protected string $treeItemAnchorSelector = 'text.node-name';

    /**
     * Switch to TYPO3 backend main frame, the one with module menu and top bar.
     */
    public function switchToMainFrame(): void
    {
        $webDriver = $this->getWebDriver();
        $webDriver->waitForElementNotVisible('#nprogress', 120);
        $webDriver->switchToIFrame();
    }

    /**
     * Switch to TYPO3 backend content frame, the one with module content.
     */
    public function switchToContentFrame(): void
    {
        $webDriver = $this->getWebDriver();
        $webDriver->waitForElementNotVisible('#nprogress', 120);
        $webDriver->switchToIFrame('list_frame');
        $webDriver->waitForElementNotVisible('#nprogress', 120);
    }

    public function _isOnMainFrame(): bool
    {
        $webDriver = $this->getWebDriver();
        $contentFrame = $webDriver->_findElements("iframe[name='list_frame']");
        return count($contentFrame) > 0;
    }

    /**
     * Open the given hierarchical path in the page tree and click the last page.
     *
     * Example to open "styleguide -> elements basic" page:
     * [
     *    'styleguide TCA demo',
     *    'elements basic',
     * ]
     *
     * @param string[] $path
     * @throws \Codeception\Exception\ModuleException
     */
    public function openPageTreePath(array $path): void
    {
        $webDriver = $this->getWebDriver();
        $webDriver->switchToIFrame();
        $webDriver->seeElement(['css' => $this->pageTreeSelector]);
        $pageTree = $webDriver->_findElements(['css' => $this->pageTreeSelector])[0];
        foreach ($path as $pageName) {
            $pageTree = $this->ensureTreeNodeIsOpen($pageName, $pageTree);
        }
        $pageTree->findElement(WebDriverBy::cssSelector($this->treeItemAnchorSelector))->click();
    }

    /**
     * Search for a page node in the provided page tree and open it.
     *
     * @param string $nodeText
     * @param RemoteWebElement $pageTree
     * @return RemoteWebElement
     * @throws \Codeception\Exception\ModuleException
     */
    protected function ensureTreeNodeIsOpen(string $nodeText, RemoteWebElement $pageTree): RemoteWebElement
    {
        $webDriver = $this->getWebDriver();
        $webDriver->see($nodeText, $this->treeItemSelector);
        $node = $pageTree->findElement(WebDriverBy::xpath('//*[text()=\'' . $nodeText . '\']/..'));
        try {
            $node->findElement(WebDriverBy::cssSelector('.chevron.collapsed'))->click();
        } catch (NoSuchElementException $e) {
            // element not found so it may be already opened...
        } catch (ElementNotInteractableException $e) {
            // another possible exception if the chevron isn't there ... depends on facebook driver version
        }
        return $node;
    }

    protected function getWebDriver(): WebDriver
    {
        return $this->getModule('WebDriver');
    }
}