<?php

namespace Drupal\field_css\EventSubscriber;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Modifies component render array to add back Field CSS classes.
 */
class BlockComponentRenderArray implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray has a
    // priority of 100, and it needs to run first.
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender', 99];
    return $events;
  }

  /**
   * Builds render arrays for block plugins and sets it on the event.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $block = $event->getPlugin();
    if (!$block instanceof BlockPluginInterface) {
      return;
    }

    $build = $event->getBuild();

    // Layout Builder strips attributes from entities when it renders them
    // as components. field_css_entity_view_alter() adds any of field_css'
    // classes in a #field_css array, which guarantees only its classes
    // are added here.
    if (isset($build['content']['#field_css'])) {
      foreach ($build['content']['#field_css']['class'] as $class) {
        $build['#attributes']['class'][] = $class;
      }
    }
    $event->setBuild($build);
  }

}
