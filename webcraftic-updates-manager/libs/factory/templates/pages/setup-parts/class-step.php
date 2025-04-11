<?php

namespace WBCR\Factory_Templates_137\Pages;

// Exit if accessed directly
use JetBrains\PhpStorm\NoReturn;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract class Step
 *
 * Represents an individual step in the setup process, managing navigation
 * between steps and their related functionality.
 *
 * @package WBCR\Factory_Templates_137\Pages
 * @author  Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 23.07.2020, Webcraftic
 * @version 1.0
 */
abstract class Step
{

    /**
     * Step identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * Identifier for the previous step.
     *
     * @var string|false
     */
    protected $prev_id = false;

    /**
     * Identifier for the next step.
     *
     * @var string|false
     */
    protected $next_id = false;

    /**
     * The setup page associated with this step.
     *
     * @var \WBCR\Factory_Templates_137\Pages\Setup
     */
    protected Setup $page;

    /**
     * Plugin instance related to the setup process.
     *
     * @var \Wbcr_Factory483_Plugin
     */
    protected \Wbcr_Factory483_Plugin $plugin;

    /**
     * Step constructor.
     *
     * Initializes the step with the setup page it belongs to.
     *
     * @param \WBCR\Factory_Templates_137\Pages\Setup $page The setup page instance.
     */
    public function __construct(\WBCR\Factory_Templates_137\Pages\Setup $page)
    {
        $this->page = $page;
        $this->plugin = $page->plugin;
        // Disabled form handler for now.
        //$this->form_handler();
    }

    /**
     * Gets the ID of the current step.
     *
     * @return string The step identifier.
     * @throws \Exception If the step ID is not defined.
     */
    public function get_id(): string
    {
        if (empty($this->id)) {
            throw new \Exception('Step ID setting is required for the {' . static::class . '} class!');
        }

        return $this->id;
    }

    /**
     * Gets the ID of the next step.
     *
     * @return string|false The identifier of the next step, or false if not defined.
     */
    public function get_next_id(): string
    {
        return $this->next_id;
    }

    /**
     * Enqueues assets (JS and CSS) required for the page.
     *
     * Override this method to include the step-specific styles and scripts.
     *
     * @param \Wbcr_Factory483_ScriptList $scripts An array of script handles to enqueue.
     * @param \Wbcr_Factory483_StyleList $styles An array of style handles to enqueue.
     *
     * @return void
     * @since 1.0.0
     * @see FactoryPages483_AdminPage
     */
    public function assets(\Wbcr_Factory483_ScriptList $scripts, \Wbcr_Factory483_StyleList $styles)
    {
        // nothing
    }

    /**
     * Proceeds to the next step or skips over the current step.
     *
     * @param bool $skip Whether to skip the current step (default: false).
     *
     * @return void Redirects the user to the next step's URL.
     * @throws \Exception
     */
    protected function continue_step($skip = false): void
    {
        $next_id = $this->get_next_id();
        if (!$next_id) {
            $next_id = $this->get_id();
        }
        wp_safe_redirect($this->page->getActionUrl($next_id));
        die();
    }

    /**
     * Skips the current step and continues to the next step.
     *
     * @return void
     * @throws \Exception
     */
    protected function skip_step(): void
    {
        $this->continue_step(true);
    }

    /**
     * Gets the title of the step.
     *
     * @return string The step's title.
     */
    abstract public function get_title(): string;

    /**
     * Outputs the HTML content of the step.
     *
     * This method must be implemented in child classes to render
     * step-specific HTML content.
     *
     * @return void
     */
    abstract public function html(): void;

}