<?php

/**
 * @file
 * Contains \Drupal\log\LogTypeForm.
 */

namespace Drupal\log;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for node type forms.
 */
class LogTypeForm extends BundleEntityFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add log type');
      $fields = $this->entityManager->getBaseFieldDefinitions('log');
      // Create a node with a fake bundle using the type's UUID so that we can
      // get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      $node = $this->entityManager->getStorage('log')->create(array('type' => $type->uuid()));
    }
    else {
      $form['#title'] = $this->t('Edit %label log type', array('%label' => $type->label()));
      $fields = $this->entityManager->getFieldDefinitions('log', $type->id());
      // Create a node to get the current values for workflow settings fields.
      $node = $this->entityManager->getStorage('log')->create(array('type' => $type->id()));
    }

    $form['name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this log type. This text will be displayed as part of the list on the <em>Add content</em> page. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => ['Drupal\node\Entity\LogType', 'load'],
        'source' => array('name'),
      ),
      '#description' => t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %log-add page, in which underscores will be converted into hyphens.', array(
        '%log-add' => t('Add content'),
      )),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new content</em> page.'),
    );

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array('node/drupal.content_types'),
      ),
    );

    $form['submission'] = array(
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    );
    $form['submission']['preview_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Preview before submitting'),
      '#default_value' => $type->getPreviewMode(),
      '#options' => array(
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ),
    );
    $form['submission']['help']  = array(
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => t('This text will be displayed at the top of the page when creating or editing content of this type.'),
    );
    $form['workflow'] = array(
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#group' => 'additional_settings',
    );
    $workflow_options = array(
      'status' => $node->status->value,
      'promote' => $node->promote->value,
      'sticky' => $node->sticky->value,
      'revision' => $type->isNewRevision(),
    );
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => array(
        'status' => t('Published'),
        'promote' => t('Promoted to front page'),
        'sticky' => t('Sticky at top of lists'),
        'revision' => t('Create new revision'),
      ),
      '#description' => t('Users with the <em>Administer content</em> permission will be able to override these options.'),
    );
    $form['display'] = array(
      '#type' => 'details',
      '#title' => t('Display settings'),
      '#group' => 'additional_settings',
    );
    $form['display']['display_submitted'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display author and date information'),
      '#default_value' => $type->displaySubmitted(),
      '#description' => t('Author username and publish date will be displayed.'),
    );

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save log type');
    $actions['delete']['#value'] = t('Delete log type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $type->setNewRevision($form_state->getValue(array('options', 'revision')));
    $type->set('type', trim($type->id()));
    $type->set('name', trim($type->label()));

    $status = $type->save();

    $t_args = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The content type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The content type %name has been added.', $t_args));
      $context = array_merge($t_args, array('link' => $type->link($this->t('View'), 'collection')));
      $this->logger('log')->notice('Added content type %name.', $context);
    }

    $fields = $this->entityManager->getFieldDefinitions('log', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    // Update workflow options.
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    $node = $this->entityManager->getStorage('log')->create(array('type' => $type->id()));

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->urlInfo('collection'));
  }

}
