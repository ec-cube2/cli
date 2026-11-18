<?php

namespace Eccube2\Tests\Structure;

use Eccube2\Util\EventDispatcherUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Symfony のバージョンごとに異なる dispatch() の引数の順番を吸収できていることをテストする。
 * CI では Symfony 4.4 と 5.4 の両方で実行する。
 */
class EventDispatcherUtilTest extends TestCase
{
    public function testDispatchNotifiesListenerWithGenericEvent()
    {
        $dispatcher = new EventDispatcher();
        $received = [];
        $dispatcher->addListener('install.insert_data', function ($event, $eventName) use (&$received) {
            $received[] = [$event, $eventName];
        });

        $event = EventDispatcherUtil::dispatch($dispatcher, 'install.insert_data');

        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertCount(1, $received);
        $this->assertSame($event, $received[0][0]);
        $this->assertSame('install.insert_data', $received[0][1]);
    }

    public function testDispatchPassesGivenEvent()
    {
        $dispatcher = new EventDispatcher();
        $received = null;
        $dispatcher->addListener('install.after', function ($event) use (&$received) {
            $received = $event;
        });
        $given = new GenericEvent('subject', ['key' => 'value']);

        $event = EventDispatcherUtil::dispatch($dispatcher, 'install.after', $given);

        $this->assertSame($given, $event);
        $this->assertSame($given, $received);
    }

    public function testDispatchWithoutListenerDoesNothing()
    {
        $event = EventDispatcherUtil::dispatch(new EventDispatcher(), 'install.after');

        $this->assertInstanceOf(GenericEvent::class, $event);
    }
}
