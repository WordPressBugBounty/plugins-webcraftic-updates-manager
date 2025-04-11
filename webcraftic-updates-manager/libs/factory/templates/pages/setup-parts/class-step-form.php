<?php

namespace WBCR\Factory_Templates_137\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step_Form
 *
 * Represents a form step in a setup wizard. This class contains methods for rendering
 * and processing forms and their elements dynamically, handling actions like "Continue" and "Skip."
 *
 * @package WBCR\Factory_Templates_137\Pages
 * @author  Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 23.07.2020, Webcraftic
 * @version 1.0
 */
class Step_Form extends Step
{

    /**
     * Step_Form constructor.
     *
     * Initializes the form step with the provided setup page.
     *
     * @param \WBCR\Factory_Templates_137\Pages\Setup $page The setup wizard page instance.
     */
    public function __construct(\WBCR\Factory_Templates_137\Pages\Setup $page)
    {
        parent::__construct($page);
    }

    /**
     * Gets the title of the step.
     *
     * @return string The title of the step, used for display in the wizard.
     */
    public function get_title(): string
    {
        return __('Default form', 'wbcr_factory_templates_137');
    }

    /**
     * Gets the description of the form.
     *
     * @return string A description for the form, intended to guide the user.
     */
    public function get_form_description(): string
    {
        return __('This is a sample html form, please customize the form fields, add description and title.', 'wbcr_factory_templates_137');
    }

    /**
     * Retrieves the form options.
     *
     * Override this method in child classes to define specific options.
     *
     * @return array An array of form options.
     */
    public function get_form_options(): array
    {
        return [];
    }

    /**
     * Creates and initializes the form instance with the given options.
     *
     * @param array $options The form options to include in the form.
     *
     * @return \Wbcr_FactoryForms483_Form The created form instance.
     * @throws \Exception
     */
    protected function instance_form($options): \Wbcr_FactoryForms483_Form
    {

        $form = new \Wbcr_FactoryForms483_Form([
            'scope' => rtrim($this->plugin->getPrefix(), '_'),
            'name' => $this->page->getResultId() . "-options-" . $this->get_id()
        ], $this->plugin);

        $form->setProvider(new \Wbcr_FactoryForms483_OptionsValueProvider($this->plugin));

        $form_options = [];

        $form_options[] = [
            'type' => 'form-group',
            'items' => $options,
            //'cssClass' => 'postbox'
        ];

        if (isset($form_options[0]['items']) && is_array($form_options[0]['items'])) {
            foreach ($form_options[0]['items'] as $key => $value) {

                if ($value['type'] == 'div' || $value['type'] == 'more-link') {
                    if (isset($form_options[0]['items'][$key]['items']) && !empty($form_options[0]['items'][$key]['items'])) {
                        foreach ($form_options[0]['items'][$key]['items'] as $group_key => $group_value) {
                            $form_options[0]['items'][$key]['items'][$group_key]['layout']['column-left'] = '8';
                            $form_options[0]['items'][$key]['items'][$group_key]['layout']['column-right'] = '4';
                        }

                        continue;
                    }
                }

                if (in_array($value['type'], [
                    'checkbox',
                    'textarea',
                    'integer',
                    'textbox',
                    'dropdown',
                    'list',
                    'wp-editor'
                ])) {
                    $form_options[0]['items'][$key]['layout']['column-left'] = '8';
                    $form_options[0]['items'][$key]['layout']['column-right'] = '4';
                }
            }
        }

        $form->add($form_options);
        $this->set_form_handler($form);

        return $form;
    }

    /**
     * Renders the given form as HTML.
     *
     * This method adds the form, including a nonce for security and buttons for user actions.
     *
     * @param \Wbcr_FactoryForms483_Form $form The form instance to render.
     *
     * @return void
     * @throws \Exception
     */
    protected function render_form(\Wbcr_FactoryForms483_Form $form): void
    {
        ?>
        <form method="post" id="w-factory-templates-137__setup-form-<?php echo esc_attr($this->get_id()) ?>"
              class="w-factory-templates-137__setup-form form-horizontal">
            <?php $form->html(); ?>
            <div class="w-factory-templates-137__form-buttons">
                <?php echo wp_nonce_field('wbcr_factory_templates_137_setup_wizard_nonce', 'wbcr_factory_templates_137_setup_wizard_nonce_' . esc_attr($this->get_id())); ?>
                <input type="submit" name="continue_button_<?php echo esc_attr($this->get_id()) ?>"
                       class="button-primary button button-large w-factory-templates-137__continue-button"
                       value="<?php _e('Continue', 'wbcr_factory_templates_137') ?>">
            </div>
        </form>
        <?php
    }

    /**
     * Handles the actions performed on the form (e.g., "Continue", "Skip").
     *
     * @param \Wbcr_FactoryForms483_Form $form The form instance being processed.
     *
     * @return void
     * @throws \Exception
     */
    protected function set_form_handler(\Wbcr_FactoryForms483_Form $form): void
    {
        if (isset($_POST['continue_button_' . $this->get_id()]) || isset($_POST['skip_button_' . $this->get_id()])) {
            $nonce_action = 'wbcr_factory_templates_137_setup_wizard_nonce';
            $nonce_key = $nonce_action . '_' . $this->get_id();
            $nonce_valid = isset($_POST[$nonce_key]) && wp_verify_nonce($_POST[$nonce_key], $nonce_action);

            if (!$this->plugin->current_user_can() || !$nonce_valid) {
                wp_die(__('You do not have sufficient permissions or the nonce is invalid to access this page.', 'wbcr_factory_templates_137'));
            }

            if (isset($_POST['continue_button_' . $this->get_id()])) {
                $form->save();
                do_action('wbcr/factory/clearfy/setup_wizard/saved_options');
                $this->continue_step();
            }

            if (isset($_POST['skip_button_' . $this->get_id()])) {
                $this->skip_step();
            }
        }
    }

    public function html(): void {
        $form_options = $this->get_form_options();

        if ( empty( $form_options ) ) {
            echo __( 'Html form is not configured.', 'wbcr_factory_templates_137' );

            return;
        }

        $form = $this->instance_form( $this->get_form_options() );
        ?>
        <div id="WBCR" class="wrap">
            <div class="wbcr-factory-templates-137-impressive-page-template factory-bootstrap-485 factory-fontawesome-000">
                <div class="w-factory-templates-137-setup__inner-wrap">
                    <h3><?php echo esc_attr($this->get_title()); ?></h3>
                    <p style="text-align: left;"><?php echo esc_html($this->get_form_description()); ?></p>
                </div>
                <?php $this->render_form( $form ); ?>
            </div>
        </div>
        <?php
    }
}