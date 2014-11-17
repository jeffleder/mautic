<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\FormBundle\EventListener;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CalendarSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class CalendarSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CalendarEvents::CALENDAR_ON_GENERATE => array('onCalendarGenerate', 0)
        );
    }

    /**
     * Adds events to the calendar
     *
     * @param CalendarGeneratorEvent $event
     *
     * @return void
     * @todo   This method is only a model and should be removed when actual data is being populated
     */
    public function onCalendarGenerate(CalendarGeneratorEvent $event)
    {
        $dates = $event->getDates();

        $query = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
        $query->select('fs.referer AS title, fs.date_submitted AS start')
            ->from(MAUTIC_TABLE_PREFIX . 'form_submissions', 'fs')
            ->where($query->expr()->andX(
                $query->expr()->gte('fs.date_submitted', ':start'),
                $query->expr()->lte('fs.date_submitted', ':end')
            ))
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(5);

        $results = $query->execute()->fetchAll();

        // We need to convert the date to a ISO8601 compliant string
        foreach ($results as &$object) {
            $date = new DateTimeHelper($object['start']);
            $object['start'] = $date->toLocalString(\DateTime::ISO8601);
        }

        $event->addEvents($results);
    }
}