<?php

/**
 * @file
 * Contains \Drupal\crm_core_match\Plugin\crm_core_match\field\Name.
 */

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;
use Drupal\crm_core_contact\ContactInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for evaluating name fields.
 *
 * @CrmCoreMatchFieldHandler (
 *   id = "name"
 * )
 */
class Name extends FieldHandlerBase {

  /**
   * A query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $field = $configuration['field'];
    unset($configuration['field']);
    $name = new static(
      $field,
      $container->get('entity.query')->get('crm_core_contact', 'AND'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $name->queryFactory = $container->get('entity.query');
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return array(
      'CONTAINS' => t('Contains'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function match(ContactInterface $contact, $property = 'value') {
    $field = $this->field->getName();
    $name = $contact->get($field)->{$property};
    $parts = preg_split('/[\ \,]+/', $name);
    $valid_parts = [];
    foreach ($parts as $part) {
      if (strlen($part) > 2) {
        $valid_parts[] = $part;
      }
    }
    $result = [];
    if (!empty($valid_parts)) {
      foreach ($valid_parts as $part) {
        $this->query = $this->queryFactory->get('crm_core_contact', 'AND');
        $this->query->condition('type', $contact->bundle());
        if ($contact->id()) {
          $this->query->condition('contact_id', $contact->id(), '<>');
        }

        if ($field instanceof FieldConfigInterface) {
          $field .= '.' . $property;
        }

        $this->query->condition($field, $part, 'CONTAINS');
        $ids = $this->query->execute();
        foreach ($ids as $id) {
          if (isset($result[$id])) {
            $result[$id] += 1;
          }
          else {
            $result[$id] = 1;
          }
        }
      }
    }
    arsort($result);
    return $result;
  }

}
