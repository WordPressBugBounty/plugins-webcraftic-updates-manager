<?php

namespace WBCR\Factory_Templates_137\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class representing a custom step.
 * @author  Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 23.07.2020, Webcraftic
 * @version 1.0
 */
class Step_Custom extends Step
{

    /**
     * Gets the title of the step.
     *
     * @return string The step's title.
     */
    public function get_title(): string
    {
        return __('Custom step', 'wbcr_factory_templates_137');
    }

    /**
     * Renders the buttons for the step.
     *
     * @param bool $continue Whether to show the "Continue" button (default: true).
     * @param bool $skip Whether to show the "Skip" button (default: false).
     * @param string|null $custom_title Custom title for the "Continue" button (optional).
     * @param string $align Alignment of the buttons ('left', 'right', 'center', default: 'right').
     *
     * @return void
     * @throws \Exception
     */
    public function render_button(bool $continue = true, bool $skip = false, string $custom_title = null, string $align = 'right'): void
    {
        $this->set_button_handler();
        $button_title = !empty($custom_title) ? esc_html($custom_title) : __('Continue', 'wbcr_factory_templates_137');

        if (!$this->get_next_id()) {
            $button_title = __('Finish', 'wbcr_factory_templates_137');
        }

        if (!in_array($align, ['center', 'left', 'right'])) {
            $align = 'right';
        }

        ?>
        <form method="post" id="w-factory-templates-137__setup-form-<?php echo esc_attr($this->get_id()); ?>"
              class="form-horizontal">
            <div class="w-factory-templates-137__form-buttons" style="text-align: <?php echo esc_attr($align); ?>">
                <?php if ($skip): ?>
                    <input type="submit" name="skip_button_<?php echo esc_attr($this->get_id()); ?>"
                           class="button-primary button button-large w-factory-templates-137__skip-button"
                           value="<?php _e('Skip', 'wbcr_factory_templates_137'); ?>">
                <?php endif; ?>
                <?php if ($continue): ?>
                    <input type="submit" name="continue_button_<?php echo esc_attr($this->get_id()); ?>"
                           class="button-primary button button-large w-factory-templates-137__continue-button"
                           value="<?php echo esc_attr($button_title); ?>">
                <?php endif; ?>
            </div>
        </form>
        <?php
    }

    /**
     * Sets the handler for button actions and processes the corresponding step based on the button clicked.
     *
     * @return void
     * @throws \Exception
     */
    protected function set_button_handler(): void
    {
        if (isset($_POST['continue_button_' . $this->get_id()])) {
            $this->continue_step();
        }

        if (isset($_POST['skip_button_' . $this->get_id()])) {
            $this->skip_step();
        }
    }

    /**
     * Outputs the HTML content of the step.
     *
     * @return void
     */
    public function html(): void
    {
        /// nothing
    }

}