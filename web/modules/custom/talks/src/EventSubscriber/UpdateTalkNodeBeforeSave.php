<?php

declare(strict_types=1);

namespace Drupal\opdavies_talks\EventSubscriber;

use Carbon\Carbon;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\opdavies_talks\Entity\Node\Talk;
use Drupal\paragraphs\ParagraphInterface;
use Illuminate\Support\Collection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Update a talk node before it's saved.
 */
final class UpdateTalkNodeBeforeSave implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_PRE_SAVE => 'onEntityPreSave',
    ];
  }

  public function onEntityPreSave(AbstractEntityEvent $event): void {
    if ($event->getEntity()->getEntityTypeId() != 'node') {
      return;
    }

    if ($event->getEntity()->bundle() != 'talk') {
      return;
    }

    $node = $event->getEntity();
    $talk = Talk::createFromNode($node);

    $this->reorderEvents($talk);
    $this->updateCreatedDate($talk);
  }

  private function reorderEvents(Talk $talk): void {
    $events = $talk->getEvents();

    // If there is only one event, there's no need to sort them.
    if ($events->count() === 1) {
      return;
    };

    $eventsByDate = $this->sortEventsByDate($events);

    // If the original event IDs don't match the sorted event IDs, update the event field to use the sorted ones.
    if ($events->map->id() != $eventsByDate->map->id()) {
      $talk->setEvents($eventsByDate->toArray());
    }
  }

  private function sortEventsByDate(Collection $events): Collection {
    return $events
      ->sortBy(fn(ParagraphInterface $event) => $event->get('field_date')
        ->getString())
      ->values();
  }

  private function updateCreatedDate(Talk $talk): void {
    if (!$eventDate = $talk->findLatestEventDate()) {
      return;
    }

    $talkDate = Carbon::parse($eventDate)->getTimestamp();

    if ($talkDate == $talk->getCreatedTime()) {
      return;
    }

    $talk->setCreatedTime($talkDate);
  }

}
